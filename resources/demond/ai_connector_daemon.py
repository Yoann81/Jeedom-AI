import os
import subprocess
import requests
import time
import sys
import signal
from urllib.parse import quote

# Configuration
JEEDOM_URL = "http://127.0.0.1/core/api/jeeApi.php"
API_KEY = sys.argv[1]
CMD_ID = sys.argv[2]
DEVICE_ID = sys.argv[3]

WHISPER_PATH = "/var/www/html/plugins/ai_connector/resources/whisper.cpp/main"
MODEL_PATH = "/var/www/html/plugins/ai_connector/resources/whisper.cpp/models/ggml-base.bin"
TEMP_WAVE = "/tmp/ai_voice.wav"
PID_FILE = '/tmp/jeedom/ai_connector/daemon.pid'

def sigterm_handler(signum, frame):
    """Gère le signal d'arrêt de Jeedom."""
    print("Signal d'arrêt reçu. Nettoyage...")
    try:
        os.remove(PID_FILE)
    except OSError:
        pass
    sys.exit(0)

def send_to_jeedom(text):
    clean_text = text.strip().replace('"', '').replace("'", "")
    forbidden_words = ["[blank]", "vostfr", "sous-titres", "merci d'avoir", "étiez en train"]
    if len(clean_text) < 4 or any(w in clean_text.lower() for w in forbidden_words):
        return
    print(f"Envoi à Jeedom : {clean_text}")
    encoded_text = quote(clean_text)
    url = f"{JEEDOM_URL}?apikey={API_KEY}&type=cmd&id={CMD_ID}&message={encoded_text}"
    try:
        r = requests.get(url, timeout=5)
        r.raise_for_status()
    except Exception as e:
        print(f"Erreur d'envoi Jeedom : {e}")

def listen():
    """Boucle principale d'écoute et de transcription."""
    print(f"Démon AI Multi-Connect démarré sur hw:{DEVICE_ID},0")
    while True:
        if os.path.exists(TEMP_WAVE):
            os.remove(TEMP_WAVE)
        os.system(f"arecord -D hw:{DEVICE_ID},0 -d 4 -f S16_LE -c1 -r 16000 -t wav {TEMP_WAVE} > /dev/null 2>&1")
        if not os.path.exists(TEMP_WAVE):
            print("Erreur : Le micro ne semble pas capturer de son.")
            time.sleep(2)
            continue
        try:
            cmd = [WHISPER_PATH, "-m", MODEL_PATH, "-f", TEMP_WAVE, "-nt", "-l", "fr", "-t", "2"]
            result = subprocess.check_output(cmd, stderr=subprocess.STDOUT)
            text = result.decode('utf-8').strip()
            if text:
                send_to_jeedom(text)
        except Exception as e:
            print(f"Erreur technique : {e}")
        time.sleep(0.1)

if __name__ == "__main__":
    # --- Boilerplate de démon Jeedom ---
    signal.signal(signal.SIGTERM, sigterm_handler)
    pid_dir = os.path.dirname(PID_FILE)
    if not os.path.exists(pid_dir):
        os.makedirs(pid_dir)
    with open(PID_FILE, 'w') as f:
        f.write(str(os.getpid()))
    print("Fichier PID créé. Démarrage de la boucle d'écoute.")
    # --- Fin du Boilerplate ---

    try:
        listen()
    except Exception as e:
        print(f"Erreur majeure dans la boucle principale : {e}")
    finally:
        print("Sortie inattendue, nettoyage du fichier PID.")
        try:
            os.remove(PID_FILE)
        except OSError:
            pass