#!/bin/bash
#########################
# Script d'installation pour ai_connector
#########################

BASE_PATH=$(dirname "$0")
cd "$BASE_PATH"

echo "Début de l'installation de Whisper.cpp..."

# 1. Nettoyage si une installation précédente a échoué
if [ -d "whisper.cpp" ]; then
    echo "Nettoyage de l'ancienne installation..."
    rm -rf whisper.cpp
fi

# 2. Clonage du dépôt
echo "Clonage de Whisper.cpp..."
git clone https://github.com/ggerganov/whisper.cpp.git
if [ $? -ne 0 ]; then echo "Erreur lors du clonage"; exit 1; fi

cd whisper.cpp

# 3. Compilation optimisée pour Raspberry Pi 4
echo "Compilation en cours (cela peut prendre quelques minutes)..."
# On utilise 4 coeurs pour compiler plus vite
make -j4
if [ $? -ne 0 ]; then echo "Erreur lors de la compilation"; exit 1; fi

# 4. Téléchargement du modèle de langue (modèle 'base' pour un bon compromis)
echo "Téléchargement du modèle de langue 'base'..."
bash ./models/download-ggml-model.sh base
if [ $? -ne 0 ]; then echo "Erreur lors du téléchargement du modèle"; exit 1; fi

#########################
# 5. GESTION DES DROITS (AJOUTÉ)
#########################
echo "Configuration des droits pour Jeedom..."

# Remonter au dossier racine du plugin (ai_connector)
PLUGIN_DIR="/var/www/html/plugins/ai_connector"

# Donner la propriété à www-data (utilisateur web de Jeedom)
sudo chown -R www-data:www-data $PLUGIN_DIR

# S'assurer que les scripts sont exécutables
sudo chmod -R 775 $PLUGIN_DIR
sudo chmod +x $PLUGIN_DIR/resources/demond/ai_connector_daemon.py

# Créer le fichier de log pour éviter le "log: nok"
sudo touch /var/www/html/log/ai_connector_daemon.log
sudo chmod 777 /var/www/html/log/ai_connector_daemon.log

echo "Installation terminée avec succès !"