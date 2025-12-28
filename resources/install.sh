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

echo "Installation terminée avec succès !"