#!/bin/bash
#########################
# Script d'installation pour ai_connector
# S'arrête immédiatement si une commande échoue
set -e
#########################

BASE_PATH=$(dirname "$0")
cd "$BASE_PATH"

echo "--- Début de l'installation des dépendances de AI Connector ---"

echo "Installation des librairies systeme audio..."
sudo apt-get install -y libportaudio2 libportaudiocpp0 portaudio19-dev python3-pyaudio wget
echo "Nettoyage des serveurs audio inutiles (JACK)..."
sudo apt-get remove --purge -y jackd2 jackd libjack-jackd2-0
sudo apt-get autoremove -y

echo "Installation des dependances audio..."
sudo apt-get install -y python3-pyaudio libasound2-dev

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
# Téléchargement d'un bip court et propre
if [ ! -f "$SOUND_FILE" ]; then
    echo "Téléchargement du son de notification..."
    # Utilisation d'un son libre de droit (un bip court de 0.5s)
    sudo wget "https://raw.githubusercontent.com/polyfloyd/messaging-app/master/assets/sent.wav" -O "$SOUND_FILE"
fi

# 5. Installation des dépendances Python
echo "Installation des dépendances Python pour le Wakeword..."
PYTHON_VENV_PATH="/var/www/html/plugins/ai_connector/resources/python_venv"

if [ -d "$PYTHON_VENV_PATH" ]; then
    echo "Nettoyage de l'ancien environnement virtuel Python..."
    sudo rm -rf "$PYTHON_VENV_PATH"
fi

sudo python3 -m venv --upgrade-deps "$PYTHON_VENV_PATH"
sudo "$PYTHON_VENV_PATH/bin/python3" -m pip install --upgrade pip wheel
sudo "$PYTHON_VENV_PATH/bin/python3" -m pip install requests numpy pyserial pvporcupine PyAudio

echo "Génération du son de notification..."
# Vérification/Installation de ffmpeg si nécessaire
if ! command -v ffmpeg &> /dev/null; then
    sudo apt-get install -y ffmpeg
fi

# Génération du fichier WAV (16-bit, 44100Hz, Mono)
# On utilise une fréquence de 1000Hz pour un bip clair
sudo ffmpeg -f lavfi -i "sine=frequency=1000:duration=0.3" /var/www/html/plugins/ai_connector/resources/notification.wav -y

# Application des permissions
sudo chown www-data:www-data /var/www/html/plugins/ai_connector/resources/notification.wav
sudo chmod 664 /var/www/html/plugins/ai_connector/resources/notification.wav
echo "Son de notification généré avec succès."

# 6. Gestion des droits et permissions
echo "Configuration des permissions..."
sudo usermod -aG audio www-data

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

# S'assurer que le fichier son est lisible
sudo chmod 664 "$PLUGIN_DIR/resources/$SOUND_FILE"

echo "Création du fichier de log du démon..."
sudo touch /var/www/html/log/ai_connector_daemon
sudo chown www-data:www-data /var/www/html/log/ai_connector_daemon
sudo chmod 664 /var/www/html/log/ai_connector_daemon

echo ""
echo "--- Installation terminée avec succès ---"