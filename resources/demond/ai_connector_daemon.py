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
from urllib.parse import quote

try:
    import pvporcupine
    PORCUPINE_AVAILABLE = True
except ImportError:
    PORCUPINE_AVAILABLE = False
    print("Avertissement : Picovoice Porcupine n est pas installe.")

from ctypes import *

# Fonction pour supprimer les messages d'erreur ALSA dans le terminal
ERROR_HANDLER_FUNC = CFUNCTYPE(None, c_char_p, c_int, c_char_p, c_int, c_char_p)
def py_error_handler(filename, line, function, err, fmt):
    pass
c_error_handler = ERROR_HANDLER_FUNC(py_error_handler)
asound = cdll.LoadLibrary('libasound.so.2')
asound.snd_lib_error_set_handler(c_error_handler)

# --- Configuration par défaut ---
JEEDOM_URL = "http://127.0.0.1/core/api/jeeApi.php"
WHISPER_PATH = "/var/www/html/plugins/ai_connector/resources/whisper.cpp/whisper-cli"
MODEL_PATH = "/var/www/html/plugins/ai_connector/resources/whisper.cpp/models/ggml-base.bin"
TEMP_WAVE = "/tmp/ai_voice.wav"
PID_FILE = '/tmp/jeedom/ai_connector/daemon.pid'
# --- Porcupine config ---
PICOVOICE_SAMPLE_RATE = 16000 # Porcupine's required sample rate
PICOVOICE_FRAME_LENGTH = 512 # Porcupine's required frame length
PICOVOICE_CHANNELS = 1 # Porcupine's required number of channels


def sigterm_handler(signum, frame):
    """Gère le signal d'arrêt de Jeedom."""
    print("Signal d'arrêt reçu. Nettoyage...")
    try:
        if os.path.exists(PID_FILE):
            os.remove(PID_FILE)
    except OSError as e:
        print(f"Erreur lors de la suppression du fichier PID : {e}")
    sys.exit(0)

def send_to_jeedom(text, api_key, cmd_id):
    """Envoie le texte transcit à Jeedom."""
    clean_text = text.strip().replace('"', '').replace("'", "")
    forbidden_words = ["[blank]", "vostfr", "sous-titres", "merci d'avoir", "étiez en train"]
    
    if len(clean_text) < 4 or any(w in clean_text.lower() for w in forbidden_words):
        return
        
    print(f"Envoi à Jeedom : {clean_text}")
    encoded_text = quote(clean_text)
    url = f"{JEEDOM_URL}?apikey={api_key}&type=cmd&id={cmd_id}&message={encoded_text}"
    
    try:
        r = requests.get(url, timeout=5)
        r.raise_for_status()
    except requests.exceptions.RequestException as e:
        print(f"Erreur d'envoi Jeedom : {e}")

def transcribe_and_send(api_key, cmd_id):
    """Transcrit l'audio et envoie le texte à Jeedom."""
    if not os.path.exists(TEMP_WAVE):
        print("Erreur : Le fichier audio n'a pas été créé pour la transcription.")
        return False
        
    print("Démon AI Multi-Connect : Transcription audio...")
    try:
        cmd = [WHISPER_PATH, "-m", MODEL_PATH, "-f", TEMP_WAVE, "-nt", "-l", "fr"]
        
        # Capture stdout for transcription, stderr for diagnostics
        process = subprocess.Popen(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
        stdout, stderr = process.communicate()
        
        text = stdout.strip() # Only take stdout for the transcription

        # Log any stderr from whisper-cli separately
        if stderr:
            print(f"Whisper CLI stderr: {stderr.strip()}")

        print(f"Démon AI Multi-Connect : Texte transcrit : '{text}'")
        if text:
            send_to_jeedom(text, api_key, cmd_id)
        return True
    except subprocess.CalledProcessError as e:
        print(f"Erreur de transcription Whisper : {e.output.decode('utf-8', errors='ignore')}")
    except Exception as e:
        print(f"Erreur technique inattendue lors de la transcription : {e}")
    return False

def listen_periodic(device_id, api_key, cmd_id):
    """Boucle principale d'écoute et de transcription périodique."""
    print(f"Démon AI Multi-Connect démarré en mode périodique sur hw:{device_id},0")
    
    while True:
        if os.path.exists(TEMP_WAVE):
            try:
                os.remove(TEMP_WAVE)
            except OSError as e:
                print(f"Impossible de supprimer le fichier WAVE temporaire : {e}")

        # Enregistrement audio
        print("Démon AI Multi-Connect : Enregistrement audio...")
        record_cmd = f"arecord -D hw:{device_id},0 -d 4 -f S16_LE -c1 -r 16000 -t wav {TEMP_WAVE}"
        proc = subprocess.run(record_cmd, shell=True, capture_output=True, text=True)
        if proc.returncode != 0:
            print(f"Erreur arecord : {proc.stderr}")
            time.sleep(2)
            continue

        if not os.path.exists(TEMP_WAVE):
            print("Erreur : Le fichier audio n'a pas été créé.")
            time.sleep(2)
            continue
            
        transcribe_and_send(api_key, cmd_id)
        time.sleep(0.1) # Small delay to prevent 100% CPU usage


def listen_wakeword(device_id, api_key, cmd_id, porcupine_access_key, porcupine_wakeword_names):
    """Boucle d'écoute avec détection de wakeword Porcupine."""
    if not PORCUPINE_AVAILABLE:
        print("Erreur : Picovoice Porcupine n'est pas disponible. Le mode Wakeword est désactivé.")
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
            print(f"Utilisation des wakewords par défaut : {', '.join(wakeword_list)}")
            try:
                porcupine_instance = pvporcupine.create(
                    access_key=porcupine_access_key,
                    keywords=wakeword_list,
                    sensitivities=[0.5] * len(wakeword_list)
                )
            except Exception as e:
                print(f"Erreur lors de la creation de l instance Picovoice : {e}")
                return
        else:
            raise ValueError("Aucun wakeword spécifié. Veuillez configurer les wakewords par défaut ou un modèle personnalisé si vous n'utilisez pas les mots-clés par défaut.")

        pa = pyaudio.PyAudio()
        try:
            target_index = int(device_id)
            print(f"Tentative d ouverture du flux sur l index : {target_index}")
        except:
            target_index = None

        audio_stream = pa.open(
            rate=porcupine_instance.sample_rate,
            channels=1,
            format=pyaudio.paInt16,
            input=True,
            frames_per_buffer=porcupine_instance.frame_length,
            input_device_index=target_index,
            start=True # On force le demarrage immediat
        )
        print(f"Démon AI Multi-Connect démarré en mode Wakeword sur hw:{device_id},0. En attente de '{os.path.basename(keyword_path)}'...")

        # Buffer pour enregistrer la commande après le wakeword
        command_audio_buffer = []
        is_recording_command = False
        # Let's simplify: record for a fixed time after wakeword
        RECORD_COMMAND_FRAMES = porcupine_instance.sample_rate // porcupine_instance.frame_length * 3 # Record for 3 seconds after wakeword

        while True:
            pcm = audio_stream.read(porcupine_instance.frame_length, exception_on_overflow=False)
            pcm_data = struct.unpack_from("h" * porcupine_instance.frame_length, pcm)

            if is_recording_command:
                command_audio_buffer.append(pcm_data)
                if len(command_audio_buffer) >= RECORD_COMMAND_FRAMES:
                    print("Démon AI Multi-Connect : Fin d'enregistrement de la commande.")
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
                    transcribe_and_send(api_key, cmd_id)
            else:
                keyword_index = porcupine_instance.process(pcm_data)
                if keyword_index >= 0:
                    print(f"Démon AI Multi-Connect : Wakeword détecté ('{porcupine_instance.keyword_names[keyword_index]}') !")
                    is_recording_command = True
                    command_audio_buffer = [] # Start fresh recording after wakeword
                    print("Démon AI Multi-Connect : Début d'enregistrement de la commande vocale...")

    except Exception as e:
        print(f"Erreur dans la boucle Wakeword : {e}", file=sys.stderr)
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
    
    print("Fichier PID créé. Démarrage de la boucle d'écoute.")
    # --- Fin du Boilerplate ---

    try:
        if args.porcupine_enable:
            print("Démon AI Multi-Connect : Mode Wakeword Picovoice activé.")
            if not PORCUPINE_AVAILABLE:
                print("Erreur : Picovoice Porcupine n'est pas disponible. Veuillez l'installer. Rebasculement en mode périodique.", file=sys.stderr)
                listen_periodic(args.device_id, args.apikey, args.cmd_id)
            elif not args.porcupine_access_key:
                print("Erreur : Clé d'accès Picovoice manquante. Rebasculement en mode périodique.", file=sys.stderr)
                listen_periodic(args.device_id, args.apikey, args.cmd_id)
            else:
                listen_wakeword(args.device_id, args.apikey, args.cmd_id, args.porcupine_access_key, args.porcupine_wakeword_names)
        else:
            print("Démon AI Multi-Connect : Mode d'écoute périodique activé (sans wakeword).")
            listen_periodic(args.device_id, args.apikey, args.cmd_id)
    except Exception as e:
        print(f"Erreur majeure dans la boucle principale : {e}", file=sys.stderr)
    finally:
        print("Sortie du programme, nettoyage...")
        if os.path.exists(PID_FILE):
            os.remove(PID_FILE)