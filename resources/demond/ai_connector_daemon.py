import os
import subprocess
import requests
import time
import sys
import signal
import argparse
import struct
import pyaudio
import wave # Import wave module for saving WAV files
import base64
from urllib.parse import quote
import datetime
import sys

# Redirection de stderr vers dev/null au niveau systeme
#devnull = os.open(os.devnull, os.O_WRONLY)
#os.dup2(devnull, 2)

try:
    import pvporcupine
    PORCUPINE_AVAILABLE = True
except ImportError:
    PORCUPINE_AVAILABLE = False
    log("Avertissement : Picovoice Porcupine n est pas installe.")

import os
import sys
from ctypes import *

def log(message, level="INFO"):
    # On récupère la date
    now = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    
    # ATTENTION : Utilise bien PRINT ici, pas LOG !
    print(f"[{now}][{level}] {message}")
    
    # On force l'écriture
    sys.stdout.flush()

# On definit un gestionnaire d erreur vide pour ALSA
ERROR_HANDLER_FUNC = CFUNCTYPE(None, c_char_p, c_int, c_char_p, c_int, c_char_p)
def py_error_handler(filename, line, function, err, fmt):
    pass
c_error_handler = ERROR_HANDLER_FUNC(py_error_handler)

try:
    asound = cdll.LoadLibrary('libasound.so.2')
    asound.snd_lib_error_set_handler(c_error_handler)
except:
    pass # On ignore si la librairie n'est pas trouvée

# --- Configuration par défaut ---
JEEDOM_URL = "http://127.0.0.1/core/api/jeeApi.php"
WHISPER_PATH = "/var/www/html/plugins/ai_connector/resources/whisper.cpp/whisper-cli"
MODEL_PATH = "/var/www/html/plugins/ai_connector/resources/whisper.cpp/models/ggml-tiny.bin"
TEMP_WAVE = "/tmp/ai_voice.wav"
PID_FILE = '/tmp/jeedom/ai_connector/daemon.pid'
# --- Porcupine config ---
PICOVOICE_SAMPLE_RATE = 16000 # Porcupine's required sample rate
PICOVOICE_FRAME_LENGTH = 512 # Porcupine's required frame length
PICOVOICE_CHANNELS = 1 # Porcupine's required number of channels

def play_notification_sound():
    sound_path = "/var/www/html/plugins/ai_connector/resources/notification.wav"
    if not os.path.exists(sound_path):
        return

    try:
        import wave
        wf = wave.open(sound_path, 'rb')
        p = pyaudio.PyAudio()
        
        # --- RECHERCHE DYNAMIQUE DE LA CARTE ---
        device_index = None
        for i in range(p.get_device_count()):
            dev = p.get_device_info_by_index(i)
            # On cherche Headphones (Jack) ou bcm2835
            if "Headphones" in dev['name'] or "bcm2835" in dev['name']:
                # On vérifie si ce périphérique accepte au moins 1 canal de sortie
                if dev['maxOutputChannels'] > 0:
                    device_index = i
                    break

        # Si non trouvé, on prend le périphérique par défaut
        if device_index is None:
            device_index = p.get_default_output_device_info()['index']

        stream = p.open(
            format=p.get_format_from_width(wf.getsampwidth()),
            channels=wf.getnchannels(),
            rate=wf.getframerate(),
            output=True,
            output_device_index=device_index
        )
        
        data = wf.readframes(1024)
        while data:
            stream.write(data)
            data = wf.readframes(1024)
            
        stream.stop_stream()
        stream.close()
        p.terminate()
    except Exception as e:
        # On log l'erreur proprement pour Jeedom sans faire planter le démon
        log(f"Erreur Audio : {str(e)}")
        
def sigterm_handler(signum, frame):
    """Gère le signal d'arrêt de Jeedom."""
    log("Signal d'arrêt reçu. Nettoyage...")
    try:
        if os.path.exists(PID_FILE):
            os.remove(PID_FILE)
    except OSError as e:
        log(f"Erreur lors de la suppression du fichier PID : {e}")
    sys.exit(0)

def send_to_jeedom(text, api_key, cmd_id):
    """Envoie le texte transcit à Jeedom."""
    clean_text = text.strip().replace('"', '').replace("'", "")
    forbidden_words = ["[blank]", "vostfr", "sous-titres", "merci d'avoir", "étiez en train"]
    
    if len(clean_text) < 4 or any(w in clean_text.lower() for w in forbidden_words):
        return
        
    log(f"Envoi à Jeedom : {clean_text}")
    encoded_text = quote(clean_text)
    url = f"{JEEDOM_URL}?apikey={api_key}&type=cmd&id={cmd_id}&message={encoded_text}"
    
    try:
        r = requests.get(url, timeout=5)
        r.raise_for_status()
    except requests.exceptions.RequestException as e:
        log(f"Erreur d'envoi Jeedom : {e}")

def transcribe_and_send(api_key, cmd_id, stt_engine="whisper", google_api_key="", stt_language="fr-FR"):
    """Transcrit l'audio et envoie le texte à Jeedom."""
    if not os.path.exists(TEMP_WAVE):
        log("Erreur : Le fichier audio n'a pas été créé pour la transcription.")
        return False
        
    log("Démon AI Multi-Connect : Transcription audio...")
    try:
        if stt_engine == "google" and google_api_key:
            # Use Google STT
            with wave.open(TEMP_WAVE, "rb") as wf:
                audio_content = wf.readframes(wf.getnframes())
            log(f"Audio data length: {len(audio_content)} bytes")
            # Check for silence
            if len(audio_content) > 0:
                samples = struct.unpack('<' + 'h' * (len(audio_content) // 2), audio_content)
                max_amplitude = max(abs(s) for s in samples)
                log(f"Max audio amplitude: {max_amplitude}")
                if max_amplitude < 1000:
                    log("Audio appears to be silent or very quiet")
            audio_base64 = base64.b64encode(audio_content).decode('utf-8')
            
            url = f"https://speech.googleapis.com/v1/speech:recognize?key={google_api_key}"
            data = {
                "config": {
                    "encoding": "LINEAR16",
                    "sampleRateHertz": 16000,
                    "languageCode": stt_language
                },
                "audio": {
                    "content": audio_base64
                }
            }
            response = requests.post(url, json=data, timeout=30)
            response.raise_for_status()
            result = response.json()
            log(f"Google STT response: {result}")
            text = result.get('results', [{}])[0].get('alternatives', [{}])[0].get('transcript', '')
        else:
            # Use Whisper
            cmd = [WHISPER_PATH, "-m", MODEL_PATH, "-f", TEMP_WAVE, "-nt", "-l", stt_language]
            
            # Capture stdout for transcription, stderr for diagnostics
            process = subprocess.Popen(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
            stdout, stderr = process.communicate()
            
            if process.returncode != 0:
                log(f"Erreur fatale whisper (code {process.returncode}) : {stderr}")
                return False # On sort proprement sans faire planter le python
            
            text = stdout.strip() # Only take stdout for the transcription

            # Log any stderr from whisper-cli separately
            if stderr:
                log(f"Whisper CLI stderr: {stderr.strip()}")

        log(f"Démon AI Multi-Connect : Texte transcrit : '{text}'")
        if text:
            send_to_jeedom(text, api_key, cmd_id)
        return True
    except Exception as e:
        log(f"Erreur de transcription : {e}")
    return False

def listen_periodic(device_id, api_key, cmd_id, stt_engine="whisper", google_api_key="", stt_language="fr-FR"):
    """Boucle principale d'écoute et de transcription périodique."""
    log(f"Démon AI Multi-Connect démarré en mode périodique sur hw:{device_id},0")
    
    while True:
        if os.path.exists(TEMP_WAVE):
            try:
                os.remove(TEMP_WAVE)
            except OSError as e:
                log(f"Impossible de supprimer le fichier WAVE temporaire : {e}")

        # Enregistrement audio
        log("Démon AI Multi-Connect : Enregistrement audio...")
        record_cmd = f"arecord -D hw:{device_id},0 -d 4 -f S16_LE -c1 -r 16000 -t wav {TEMP_WAVE}"
        proc = subprocess.run(record_cmd, shell=True, capture_output=True, text=True)
        if proc.returncode != 0:
            log(f"Erreur arecord : {proc.stderr}")
            time.sleep(2)
            continue

        if not os.path.exists(TEMP_WAVE):
            log("Erreur : Le fichier audio n'a pas été créé.")
            time.sleep(2)
            continue
            
        transcribe_and_send(api_key, cmd_id, stt_engine, google_api_key, stt_language)
        time.sleep(0.1) # Small delay to prevent 100% CPU usage


def listen_wakeword(device_id, api_key, cmd_id, porcupine_access_key, porcupine_wakeword_names, stt_engine="whisper", google_api_key="", stt_language="fr-FR"):
    """Boucle d'écoute avec détection de wakeword Porcupine."""
    if not PORCUPINE_AVAILABLE:
        log("Erreur : Picovoice Porcupine n'est pas disponible. Le mode Wakeword est désactivé.")
        return

    porcupine_instance = None
    pa = None
    audio_stream = None

    try:
        # Initialisation de Porcupine
        if porcupine_wakeword_names:
            wakeword_list = [w.strip() for w in porcupine_wakeword_names.split(',') if w.strip()]
            if not wakeword_list:
                raise ValueError("Aucun nom de wakeword valide fourni pour Picovoice Porcupine.")
            log(f"Utilisation des wakewords par défaut : {', '.join(wakeword_list)}")
            try:
                porcupine_instance = pvporcupine.create(
                    access_key=porcupine_access_key,
                    keywords=wakeword_list,
                    sensitivities=[0.8] * len(wakeword_list)
                )
            except Exception as e:
                log(f"Erreur lors de la creation de l instance Picovoice : {e}")
                return
        else:
            raise ValueError("Aucun wakeword spécifié. Veuillez configurer les wakewords par défaut ou un modèle personnalisé si vous n'utilisez pas les mots-clés par défaut.")

        pa = pyaudio.PyAudio()
        try:
            target_index = int(device_id)
            log(f"Tentative d ouverture du flux sur l index : {target_index}")
        except:
            target_index = None

        audio_stream = pa.open(
            rate=porcupine_instance.sample_rate,
            channels=1,
            format=pyaudio.paInt16,
            input=True,
            frames_per_buffer=porcupine_instance.frame_length,
            input_device_index=int(device_id) 
        )
        log(f"Démon AI Multi-Connect démarré en mode Wakeword sur hw:{device_id},0. En attente de '{', '.join(wakeword_list)}'...")

        # Buffer pour enregistrer la commande après le wakeword
        command_audio_buffer = []
        is_recording_command = False
        # Let's simplify: record for a fixed time after wakeword
        RECORD_COMMAND_FRAMES = porcupine_instance.sample_rate // porcupine_instance.frame_length * 5 # Record for 5 seconds after wakeword

        while True:
            pcm = audio_stream.read(porcupine_instance.frame_length, exception_on_overflow=False)
            pcm_data = struct.unpack_from("h" * porcupine_instance.frame_length, pcm)
            #if max(pcm_data) > 600:
                #log("Audio détecté...")
            if is_recording_command:
                command_audio_buffer.append(pcm_data)
                if len(command_audio_buffer) >= RECORD_COMMAND_FRAMES:
                    log("Démon AI Multi-Connect : Fin d'enregistrement de la commande.")
                    is_recording_command = False
                    
                    # Sauvegarder la commande dans TEMP_WAVE
                    wave_file_path = TEMP_WAVE
                    wf = wave.open(wave_file_path, 'wb')
                    wf.setnchannels(PICOVOICE_CHANNELS)
                    wf.setsampwidth(pa.get_sample_size(pyaudio.paInt16))
                    wf.setframerate(PICOVOICE_SAMPLE_RATE)
                    wf.writeframes(b''.join(struct.pack('h' * len(frame), *frame) for frame in command_audio_buffer))
                    wf.close()
                    
                    command_audio_buffer = [] # Clear buffer
                    transcribe_and_send(api_key, cmd_id, stt_engine, google_api_key, stt_language)
                    # play_notification_sound()  # Already played before
            else:
                keyword_index = porcupine_instance.process(pcm_data)
                if keyword_index >= 0:
                    log(f"Démon AI Multi-Connect : Wakeword détecté !!!")
                    play_notification_sound()  # Play sound to indicate listening
                    time.sleep(0.5)  # Short delay before recording
                    is_recording_command = True
                    command_audio_buffer = [] # Start fresh recording after wakeword
                    log("Démon AI Multi-Connect : Début d'enregistrement de la commande vocale...")

    except Exception as e:
        log(f"Erreur dans la boucle Wakeword : {e}", file=sys.stderr)
    finally:
        if porcupine_instance is not None:
            porcupine_instance.delete()
        if audio_stream is not None:
            audio_stream.close()
        if pa is not None:
            pa.terminate()

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Démon AI Connector pour Jeedom.")
    parser.add_argument("--apikey", required=True, help="Clé API Jeedom Core.")
    parser.add_argument("--cmd_id", required=True, help="ID de la commande Jeedom pour envoyer le texte.")
    parser.add_argument("--device_id", default="1", help="ID du périphérique d'enregistrement audio (par défaut: 1).")
    parser.add_argument("--stt_engine", default="whisper", help="Moteur STT : whisper ou google.")
    parser.add_argument("--google_api_key", default="", help="Clé API Google pour STT/TTS.")
    parser.add_argument("--stt_language", default="fr-FR", help="Langue pour STT.")
    parser.add_argument("--porcupine_enable", type=int, default=0, help="Activer la détection de wakeword Picovoice.")
    parser.add_argument("--porcupine_access_key", default="", help="Clé d'accès Picovoice pour le wakeword.")
    parser.add_argument("--porcupine_wakeword_names", default="picovoice", help="Liste des noms de wakewords Picovoice par défaut (séparés par des virgules).")
    args = parser.parse_args()

    # --- Boilerplate de démon Jeedom ---
    signal.signal(signal.SIGTERM, sigterm_handler)
    pid_dir = os.path.dirname(PID_FILE)
    if not os.path.exists(pid_dir):
        os.makedirs(pid_dir, exist_ok=True)
    
    with open(PID_FILE, 'w') as f:
        f.write(str(os.getpid()))
    
    log("Fichier PID créé. Démarrage de la boucle d'écoute.")
    # --- Fin du Boilerplate ---

    try:
        if args.porcupine_enable:
            log("Démon AI Multi-Connect : Mode Wakeword Picovoice activé.")
            if not PORCUPINE_AVAILABLE:
                log("Erreur : Picovoice Porcupine n'est pas disponible. Veuillez l'installer. Rebasculement en mode périodique.", file=sys.stderr)
                listen_periodic(args.device_id, args.apikey, args.cmd_id, args.stt_engine, args.google_api_key, args.stt_language)
            elif not args.porcupine_access_key:
                log("Erreur : Clé d'accès Picovoice manquante. Rebasculement en mode périodique.", file=sys.stderr)
                listen_periodic(args.device_id, args.apikey, args.cmd_id, args.stt_engine, args.google_api_key, args.stt_language)
            else:
                listen_wakeword(args.device_id, args.apikey, args.cmd_id, args.porcupine_access_key, args.porcupine_wakeword_names, args.stt_engine, args.google_api_key, args.stt_language)
        else:
            log("Démon AI Multi-Connect : Mode d'écoute périodique activé (sans wakeword).")
            listen_periodic(args.device_id, args.apikey, args.cmd_id, args.stt_engine, args.google_api_key, args.stt_language)
    except Exception as e:
        log(f"Erreur majeure dans la boucle principale : {e}", file=sys.stderr)
    finally:
        log("Sortie du programme, nettoyage...")
        if os.path.exists(PID_FILE):
            os.remove(PID_FILE)