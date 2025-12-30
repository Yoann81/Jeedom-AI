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
sudo apt-get install -y libportaudio2 libportaudiocpp0 portaudio19-dev python3-pyaudio
echo "Nettoyage des serveurs audio inutiles (JACK)..."
sudo apt-get remove --purge -y jackd2 jackd libjack-jackd2-0
sudo apt-get autoremove -y

echo "Installation des dependances audio..."
sudo apt-get install -y python3-pyaudio libasound2-dev

# 4. Téléchargement du modèle de langue léger (TINY)
echo "Configuration du modèle Whisper..."
cd whisper.cpp

# On télécharge le modèle tiny (beaucoup plus rapide sur Raspberry Pi)
echo "Téléchargement du modèle de langue 'tiny'..."
bash ./models/download-ggml-model.sh tiny

# On vérifie si l'ancien modèle base existe pour le supprimer (gain de place)
if [ -f "models/ggml-base.bin" ]; then
    echo "Suppression de l'ancien modèle 'base' devenu inutile..."
    rm models/ggml-base.bin
fi
echo "Modèle 'tiny' prêt."

# On remonte pour la suite
cd ..

# 5. Installation des dépendances Python pour le Wakeword
echo "Installation des dépendances Python pour le Wakeword..."
PYTHON_VENV_PATH="/var/www/html/plugins/ai_connector/resources/python_venv"

if [ -d "$PYTHON_VENV_PATH" ]; then
    echo "Nettoyage de l'ancien environnement virtuel Python..."
    sudo rm -rf "$PYTHON_VENV_PATH"
fi

sudo apt-get update
sudo apt-get install -y portaudio19-dev
sudo python3 -m venv --upgrade-deps "$PYTHON_VENV_PATH"

echo "Mise à jour de pip et wheel dans l'environnement virtuel..."
sudo "$PYTHON_VENV_PATH/bin/python3" -m pip install --upgrade pip wheel

echo "Installation des modules Python requis..."
sudo "$PYTHON_VENV_PATH/bin/python3" -m pip install --force-reinstall --upgrade requests
sudo "$PYTHON_VENV_PATH/bin/python3" -m pip install --force-reinstall --upgrade numpy
sudo "$PYTHON_VENV_PATH/bin/python3" -m pip install --force-reinstall --upgrade pyserial
sudo "$PYTHON_VENV_PATH/bin/python3" -m pip install pvporcupine
sudo "$PYTHON_VENV_PATH/bin/python3" -m pip install PyAudio

# [ ... Vérifications de pvporcupine et PyAudio identiques à ton script d'origine ... ]

# 6. Gestion des droits
echo "Configuration des permissions..."
sudo usermod -aG audio www-data

PLUGIN_DIR=$(dirname $(dirname "$0"))
echo "Application des droits à www-data sur le dossier $PLUGIN_DIR..."
sudo chown -R www-data:www-data "$PLUGIN_DIR"

echo "Application des permissions (775 pour les dossiers, 664 pour les fichiers)..."
sudo find "$PLUGIN_DIR" -type d -exec chmod 775 {} \;
sudo find "$PLUGIN_DIR" -type f -exec chmod 664 {} \;

echo "Attribution des droits d'exécution aux scripts..."
sudo chmod +x "$PLUGIN_DIR/resources/install.sh"
sudo chmod +x "$PLUGIN_DIR/resources/demond/ai_connector_daemon.py"
sudo chmod +x "$PLUGIN_DIR/resources/whisper.cpp/whisper-cli"
sudo chmod +x "$PLUGIN_DIR/resources/whisper.cpp/main"

echo "Création du fichier de log du démon..."
sudo touch /var/www/html/log/ai_connector_daemon
sudo chown www-data:www-data /var/www/html/log/ai_connector_daemon
sudo chmod 664 /var/www/html/log/ai_connector_daemon

echo ""
echo "--- Installation des dépendances terminée ---"