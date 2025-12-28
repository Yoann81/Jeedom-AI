import os
import subprocess
import requests
import time
import sys
from urllib.parse import quote # Pour encoder le texte proprement

# Configuration
JEEDOM_URL = "http://127.0.0.1/core/api/jeeApi.php"
API_KEY = sys.argv[1]
CMD_ID = sys.argv[2]
DEVICE_ID = sys.argv[3]

WHISPER_PATH = "/var/www/html/plugins/ai_connector/resources/whisper.cpp/main"
MODEL_PATH = "/var/www/html/plugins/ai_connector/resources/whisper.cpp/models/ggml-base.bin"
TEMP_WAVE = "/tmp/ai_voice.wav"

def send_to_jeedom(text):
    # Nettoyage et encodage du texte pour l'URL
    clean_text = text.strip().replace('"', '').replace("'", "")
    
    # Filtre anti-hallucinations amélioré
    forbidden_words = ["[blank]", "vostfr", "sous-titres", "merci d'avoir", "étiez en train"]
    if len(clean_text) < 4 or any(w in clean_text.lower() for w in forbidden_words):
        return 
        
    print(f"Envoi à Jeedom : {clean_text}")
    
    # Encodage du message pour éviter les erreurs HTTP
    encoded_text = quote(clean_text)
    url = f"{JEEDOM_URL}?apikey={API_KEY}&type=cmd&id={CMD_ID}&message={encoded_text}"
    
    try:
        r = requests.get(url, timeout=5)
        r.raise_for_status()
    except Exception as e:
        print(f"Erreur d'envoi Jeedom : {e}")

def listen():
    print(f"Démon AI Multi-Connect démarré sur hw:{DEVICE_ID},0")
    
    while True:
        # 1. On supprime l'ancien fichier pour éviter les boucles infinies en cas d'erreur micro
        if os.path.exists(TEMP_WAVE):
            os.remove(TEMP_WAVE)

        # 2. Enregistrement (on réduit à 4s pour plus de réactivité)
        # -t wav : format wav obligatoire pour whisper.cpp
        os.system(f"arecord -D hw:{DEVICE_ID},0 -d 4 -f S16_LE -c1 -r 16000 -t wav {TEMP_WAVE} > /dev/null 2>&1")
        
        if not os.path.exists(TEMP_WAVE):
            print("Erreur : Le micro ne semble pas capturer de son.")
            time.sleep(2)
            continue

        # 3. Transcription
        try:
            # -nt : no timestamps / -ot : output text only
            # On ajoute --threads 2 pour ne pas saturer le Pi 4
            cmd = [WHISPER_PATH, "-m", MODEL_PATH, "-f", TEMP_WAVE, "-nt", "-l", "fr", "-t", "2"]
            result = subprocess.check_output(cmd, stderr=subprocess.STDOUT)
            text = result.decode('utf-8').strip()
            
            if text:
                send_to_jeedom(text)
                
        except Exception as e:
            print(f"Erreur technique : {e}")
        
        # Petit repos pour laisser le CPU souffler
        time.sleep(0.1)

if __name__ == "__main__":
    listen()