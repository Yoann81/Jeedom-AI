import os
import subprocess
import requests
import time
import sys
import signal
import argparse
from urllib.parse import quote

# --- Configuration par défaut ---
JEEDOM_URL = "http://127.0.0.1/core/api/jeeApi.php"
WHISPER_PATH = "/var/www/html/plugins/ai_connector/resources/whisper.cpp/main"
MODEL_PATH = "/var/www/html/plugins/ai_connector/resources/whisper.cpp/models/ggml-base.bin"
TEMP_WAVE = "/tmp/ai_voice.wav"
PID_FILE = '/tmp/jeedom/ai_connector/daemon.pid'

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

def listen(device_id, api_key, cmd_id):
    """Boucle principale d'écoute et de transcription."""
    print(f"Démon AI Multi-Connect démarré sur hw:{device_id},0")
    
    while True:
        if os.path.exists(TEMP_WAVE):
            try:
                os.remove(TEMP_WAVE)
            except OSError as e:
                print(f"Impossible de supprimer le fichier WAVE temporaire : {e}")

        # Enregistrement audio
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
            
        # Transcription
        try:
            cmd = [WHISPER_PATH, "-m", MODEL_PATH, "-f", TEMP_WAVE, "-nt", "-l", "fr"]
            result = subprocess.check_output(cmd, stderr=subprocess.STDOUT)
            text = result.decode('utf-8').strip()
            if text:
                send_to_jeedom(text, api_key, cmd_id)
        except subprocess.CalledProcessError as e:
            print(f"Erreur de transcription Whisper : {e.output.decode('utf-8', errors='ignore')}")
        except Exception as e:
            print(f"Erreur technique inattendue : {e}")
            
        time.sleep(0.1)

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Démon AI Connector pour Jeedom.")
    parser.add_argument("--apikey", required=True, help="Clé API Jeedom Core.")
    parser.add_argument("--cmd_id", required=True, help="ID de la commande Jeedom pour envoyer le texte.")
    parser.add_argument("--device_id", default="1", help="ID du périphérique d'enregistrement audio (par défaut: 1).")
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
        listen(args.device_id, args.apikey, args.cmd_id)
    except Exception as e:
        print(f"Erreur majeure dans la boucle principale : {e}", file=sys.stderr)
    finally:
        print("Sortie du programme, nettoyage...")
        if os.path.exists(PID_FILE):
            os.remove(PID_FILE)