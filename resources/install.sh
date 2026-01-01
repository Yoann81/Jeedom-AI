#!/bin/bash
#########################
# Script d'installation pour ai_connector
# S'arrête immédiatement si une commande échoue
set -e
#########################

BASE_PATH=$(dirname "$0")
cd "$BASE_PATH"

echo "--- Début de l'installation des dépendances de AI Connector ---"

echo "Installation des librairies systeme audio et outils essentiels..."
sudo apt-get update
sudo apt-get install -y \
    libportaudio2 libportaudiocpp0 portaudio19-dev \
    python3-pyaudio python3-dev \
    alsa-utils \
    wget curl mpg123 ffmpeg jq \
    libasound2-dev

echo "Nettoyage des serveurs audio inutiles (JACK)..."
# Supprimer JACK SANS supprimer ses dépendances
sudo apt-get remove -y jackd2 jackd || true
# Garder les dépendances mais pas le serveur complet
sudo apt-get autoremove -y

# Réinstaller les packages qui pourraient avoir été supprimés accidentellement
echo "Vérification et réinstallation des dépendances critiques..."
sudo apt-get install -y \
    libportaudio2 python3-pyaudio mpg123 ffmpeg \
    || true

# 4. Téléchargement du modèle de langue léger (TINY)
echo "Configuration du modèle Whisper..."
cd whisper.cpp
echo "Téléchargement du modèle de langue 'tiny'..."
bash ./models/download-ggml-model.sh tiny

if [ -f "models/ggml-base.bin" ]; then
    echo "Suppression de l'ancien modèle 'base' devenu inutile..."
    rm models/ggml-base.bin
fi
echo "Modèle 'tiny' prêt."
cd ..

# --- NOUVEAU : Paramétrage du fichier son de notification ---
echo "Configuration du signal sonore (Notification)..."
SOUND_FILE="notification.wav"
SOUND_PATH="/var/www/html/plugins/ai_connector/resources/$SOUND_FILE"

# Génération du fichier WAV avec ffmpeg directement (plus fiable)
echo "Génération du son de notification..."
sudo ffmpeg -f lavfi -i "sine=frequency=1000:duration=0.3" -q:a 5 -n "$SOUND_PATH" 2>/dev/null || true

# Si la génération a échoué, télécharger une version
if [ ! -f "$SOUND_PATH" ]; then
    echo "Téléchargement du son de notification..."
    sudo wget -q "https://raw.githubusercontent.com/polyfloyd/messaging-app/master/assets/sent.wav" -O "$SOUND_PATH" || true
fi

# Application des permissions au son
if [ -f "$SOUND_PATH" ]; then
    sudo chown www-data:www-data "$SOUND_PATH"
    sudo chmod 664 "$SOUND_PATH"
    echo "Son de notification prêt."
else
    echo "⚠ Avertissement : Son de notification non généré, TTS fonctionnera sans notification"
fi

# 5. Installation des dépendances Python
echo "Installation des dépendances Python pour le Wakeword..."
PYTHON_VENV_PATH="/var/www/html/plugins/ai_connector/resources/python_venv"

if [ -d "$PYTHON_VENV_PATH" ]; then
    echo "Nettoyage de l'ancien environnement virtuel Python..."
    sudo rm -rf "$PYTHON_VENV_PATH"
fi

sudo python3 -m venv --upgrade-deps "$PYTHON_VENV_PATH"
sudo "$PYTHON_VENV_PATH/bin/python3" -m pip install --upgrade pip wheel setuptools
sudo "$PYTHON_VENV_PATH/bin/python3" -m pip install requests numpy pyserial pvporcupine PyAudio

# Vérification que le venv a été créé correctement
if [ ! -f "$PYTHON_VENV_PATH/bin/python3" ]; then
    echo "❌ ERREUR : Échec création de l'environnement Python"
    exit 1
fi
echo "✓ Environnement Python créé avec succès"

echo "Configuration des permissions..."
sudo usermod -aG audio www-data

# Création du répertoire pour le fichier PID du démon
echo "Création du répertoire pour le démon..."
sudo mkdir -p /tmp/jeedom/ai_connector
sudo chown www-data:www-data /tmp/jeedom/ai_connector
sudo chmod 775 /tmp/jeedom/ai_connector

PLUGIN_DIR=$(dirname $(dirname "$0"))
echo "Application des droits à www-data sur le dossier $PLUGIN_DIR..."
sudo chown -R www-data:www-data "$PLUGIN_DIR"

# Permissions globales
sudo find "$PLUGIN_DIR" -type d -exec chmod 775 {} \;
sudo find "$PLUGIN_DIR" -type f -exec chmod 664 {} \;

# Droits d'exécution spécifiques
sudo chmod +x "$PLUGIN_DIR/resources/install.sh"
sudo chmod +x "$PLUGIN_DIR/resources/demond/ai_connector_daemon.py"
sudo chmod +x "$PLUGIN_DIR/resources/whisper.cpp/whisper-cli"
sudo chmod +x "$PLUGIN_DIR/resources/whisper.cpp/main"

echo "Création du fichier de log du démon..."
sudo touch /var/www/html/log/ai_connector_daemon
sudo chown www-data:www-data /var/www/html/log/ai_connector_daemon
sudo chmod 664 /var/www/html/log/ai_connector_daemon

echo ""
echo "=== Vérification des dépendances ==="
echo "✓ Audio: $(which arecord && echo 'arecord OK' || echo '❌ arecord manquant')"
echo "✓ Audio: $(which aplay && echo 'aplay OK' || echo '❌ aplay manquant')"
echo "✓ Audio: $(which mpg123 && echo 'mpg123 OK' || echo '❌ mpg123 manquant')"
echo "✓ Tools: $(which curl && echo 'curl OK' || echo '❌ curl manquant')"
echo "✓ Tools: $(which wget && echo 'wget OK' || echo '❌ wget manquant')"
echo "✓ Tools: $($PYTHON_VENV_PATH/bin/python3 -c 'import pvporcupine; print("pvporcupine OK")' 2>/dev/null || echo '❌ pvporcupine manquant')"
echo "✓ Directory: $(test -d /tmp/jeedom/ai_connector && echo '/tmp/jeedom/ai_connector OK' || echo '❌ /tmp/jeedom/ai_connector manquant')"

echo ""
echo "--- Installation terminée avec succès ---"
echo "Prochaines étapes :"
echo "1. Redémarrez Jeedom : sudo systemctl restart jeedom"
echo "2. Vérifiez les logs : tail -f /var/www/html/log/ai_connector_daemon"