#!/bin/bash
#########################
# Script d'installation pour ai_connector
# S'arrête immédiatement si une commande échoue
set -e
#########################

BASE_PATH=$(dirname "$0")
cd "$BASE_PATH"

echo "--- Début de l'installation des dépendances de AI Connector ---"

# 1. Nettoyage si une installation précédente a échoué
if [ -d "whisper.cpp" ]; then
    echo "Nettoyage de l'ancienne installation de whisper.cpp..."
    rm -rf whisper.cpp
fi

# 2. Clonage du dépôt
echo "Clonage de Whisper.cpp depuis Github..."
git clone https://github.com/ggerganov/whisper.cpp.git
echo "Clonage terminé."

cd whisper.cpp

# 3. Compilation optimisée pour Raspberry Pi
echo "Compilation de Whisper.cpp (cela peut prendre plusieurs minutes)..."
make -j4
echo "Compilation terminée."

# Déplacer l'exécutable principal au bon endroit et le renommer si nécessaire
echo "Déplacement de l'exécutable 'whisper-cli'..."
mv ./build/bin/main whisper-cli
echo "Exécutable placé."

# 4. Téléchargement du modèle de langue
echo "Téléchargement du modèle de langue 'base'..."
bash ./models/download-ggml-model.sh base
echo "Modèle téléchargé."

# 5. Installation des dépendances Python pour le Wakeword (Picovoice Porcupine)
echo "Installation des dépendances Python pour le Wakeword..."
PYTHON_VENV_PATH="/var/www/html/plugins/ai_connector/resources/python_venv"

if [ -d "$PYTHON_VENV_PATH" ]; then
    echo "Nettoyage de l'ancien environnement virtuel Python..."
    sudo rm -rf "$PYTHON_VENV_PATH"
fi

sudo apt-get update
sudo apt-get install -y portaudio19-dev
sudo python3 -m venv --upgrade-deps "$PYTHON_VENV_PATH"

# Vérification critique de l'environnement virtuel
if [ -L "$PYTHON_VENV_PATH/bin/python3" ] && [ "$(readlink -f "$PYTHON_VENV_PATH/bin/python3")" == "$(readlink -f "/usr/bin/python3")" ]; then
    echo " "
    echo "##########################################################################"
    echo "  ERREUR CRITIQUE : Environnement Virtuel Invalide"
    echo "##########################################################################"
    echo "  Le Python de l'environnement virtuel ('$PYTHON_VENV_PATH/bin/python3')"
    echo "  pointe vers le Python système ('/usr/bin/python3')."
    echo "  Cela indique que votre installation de python3-venv ou la configuration"
    echo "  de votre système est défectueuse et ne crée pas d'environnement isolé."
    echo "  "
    echo "  Veuillez tenter de résoudre ce problème système (par exemple, en"
    echo "  réinstallant python3-venv ou en vérifiant votre version de Python)."
    echo "  "
    echo "  L'installation ne peut pas continuer avec un environnement virtuel non isolé."
    echo "##########################################################################"
    echo " "
    exit 1
fi

echo "Mise à jour de pip et wheel dans l'environnement virtuel..."
sudo "$PYTHON_VENV_PATH/bin/python3" -m pip install --upgrade pip wheel

echo "Installation des modules Python requis..."
sudo "$PYTHON_VENV_PATH/bin/python3" -m pip install --force-reinstall --upgrade requests
sudo "$PYTHON_VENV_PATH/bin/python3" -m pip install --force-reinstall --upgrade numpy
sudo "$PYTHON_VENV_PATH/bin/python3" -m pip install --force-reinstall --upgrade pyserial
sudo "$PYTHON_VENV_PATH/bin/python3" -m pip install pvporcupine
sudo "$PYTHON_VENV_PATH/bin/python3" -m pip install PyAudio
echo "Dépendances Python installées."

# 6. Gestion des droits
echo "Configuration des permissions..."

# Ajout de l'utilisateur www-data au groupe audio pour l'accès au micro
echo "Ajout de l'utilisateur www-data au groupe 'audio'..."
sudo usermod -aG audio www-data

# Donner la propriété à www-data pour l'ensemble du plugin
# Le script est déjà dans /resources, donc on remonte de deux niveaux
PLUGIN_DIR=$(dirname $(dirname "$0"))
echo "Application des droits à www-data sur le dossier $PLUGIN_DIR..."
sudo chown -R www-data:www-data "$PLUGIN_DIR"

# Assurer les bonnes permissions sur les dossiers et fichiers
echo "Application des permissions (775 pour les dossiers, 664 pour les fichiers)..."
sudo find "$PLUGIN_DIR" -type d -exec chmod 775 {} \;
sudo find "$PLUGIN_DIR" -type f -exec chmod 664 {} \;

# Rendre les scripts clés exécutables
echo "Attribution des droits d'exécution aux scripts..."
sudo chmod +x "$PLUGIN_DIR/resources/install.sh"
sudo chmod +x "$PLUGIN_DIR/resources/demond/ai_connector_daemon.py"

sudo chmod +x "$PLUGIN_DIR/resources/whisper.cpp/whisper-cli" # <-- LA LIGNE MANQUANTE

# Création du fichier de log pour éviter les erreurs au premier lancement
echo "Création du fichier de log du démon..."
sudo touch /var/www/html/log/ai_connector_daemon
sudo chown www-data:www-data /var/www/html/log/ai_connector_daemon
sudo chmod 664 /var/www/html/log/ai_connector_daemon

echo ""
echo "--- Installation des dépendances terminée ---"
echo ""
echo "##########################################################################"
echo "  ACTION REQUISE : REDÉMARRAGE"
echo "##########################################################################"
echo "  Pour que les permissions audio prennent effet, un redémarrage complet"
echo "  de votre système est nécessaire."
echo ""
echo "  Veuillez exécuter la commande : sudo reboot"
echo "##########################################################################"