#!/bin/bash
#########################
# Script de vérification de l'installation du plugin AI Connector
#########################

echo "=== Vérification de l'installation AI Connector ==="
echo ""

ERRORS=0
WARNINGS=0

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

check_command() {
    if command -v "$1" &> /dev/null; then
        echo -e "${GREEN}✓${NC} $2 : $(which $1)"
        return 0
    else
        echo -e "${RED}✗${NC} $2 : manquant"
        ((ERRORS++))
        return 1
    fi
}

check_file() {
    if [ -f "$1" ]; then
        echo -e "${GREEN}✓${NC} $2 : $1"
        return 0
    else
        echo -e "${RED}✗${NC} $2 : manquant ($1)"
        ((ERRORS++))
        return 1
    fi
}

check_directory() {
    if [ -d "$1" ]; then
        echo -e "${GREEN}✓${NC} $2 : $1"
        return 0
    else
        echo -e "${RED}✗${NC} $2 : manquant ($1)"
        ((ERRORS++))
        return 1
    fi
}

check_permission() {
    if [ -f "$1" ] && [ -x "$1" ]; then
        echo -e "${GREEN}✓${NC} Exécutable : $1"
        return 0
    else
        echo -e "${YELLOW}⚠${NC} Non-exécutable : $1"
        ((WARNINGS++))
        return 1
    fi
}

echo "--- Outils système ---"
check_command "arecord" "ALSA Recording"
check_command "aplay" "ALSA Playback"
check_command "mpg123" "MP3 Player"
check_command "curl" "HTTP Client (PHP)"
check_command "wget" "File Download"
check_command "ffmpeg" "Audio Generation"

echo ""
echo "--- Dossiers essentiels ---"
PLUGIN_DIR="/var/www/html/plugins/ai_connector"
check_directory "$PLUGIN_DIR" "Plugin directory"
check_directory "$PLUGIN_DIR/resources" "Resources directory"
check_directory "$PLUGIN_DIR/resources/whisper.cpp" "Whisper directory"
check_directory "/tmp/jeedom/ai_connector" "Daemon PID directory"
check_directory "/var/www/html/log" "Log directory"

echo ""
echo "--- Fichiers critiques ---"
check_file "$PLUGIN_DIR/resources/python_venv/bin/python3" "Python venv"
check_file "$PLUGIN_DIR/resources/whisper.cpp/whisper-cli" "Whisper CLI"
check_file "$PLUGIN_DIR/resources/whisper.cpp/models/ggml-tiny.bin" "Whisper model (tiny)"
check_file "$PLUGIN_DIR/resources/demond/ai_connector_daemon.py" "Daemon script"

echo ""
echo "--- Permissions d'exécution ---"
check_permission "$PLUGIN_DIR/resources/install.sh" "install.sh"
check_permission "$PLUGIN_DIR/resources/demond/ai_connector_daemon.py" "daemon.py"
check_permission "$PLUGIN_DIR/resources/whisper.cpp/whisper-cli" "whisper-cli"

echo ""
echo "--- Dépendances Python ---"
PYTHON_VENV="/var/www/html/plugins/ai_connector/resources/python_venv/bin/python3"
if [ -f "$PYTHON_VENV" ]; then
    echo "Checking Python packages..."
    $PYTHON_VENV -c "import requests" 2>/dev/null && echo -e "${GREEN}✓${NC} requests" || { echo -e "${RED}✗${NC} requests"; ((ERRORS++)); }
    $PYTHON_VENV -c "import numpy" 2>/dev/null && echo -e "${GREEN}✓${NC} numpy" || { echo -e "${RED}✗${NC} numpy"; ((ERRORS++)); }
    $PYTHON_VENV -c "import pyaudio" 2>/dev/null && echo -e "${GREEN}✓${NC} pyaudio" || { echo -e "${RED}✗${NC} pyaudio"; ((ERRORS++)); }
    $PYTHON_VENV -c "import pvporcupine" 2>/dev/null && echo -e "${GREEN}✓${NC} pvporcupine" || { echo -e "${RED}✗${NC} pvporcupine"; ((ERRORS++)); }
else
    echo -e "${RED}✗${NC} Python venv not found"
    ((ERRORS++))
fi

echo ""
echo "--- Permissions www-data ---"
PLUGIN_OWNER=$(stat -c '%U' "$PLUGIN_DIR" 2>/dev/null || echo "unknown")
if [ "$PLUGIN_OWNER" = "www-data" ]; then
    echo -e "${GREEN}✓${NC} Plugin owned by www-data"
else
    echo -e "${YELLOW}⚠${NC} Plugin owned by $PLUGIN_OWNER (should be www-data)"
    ((WARNINGS++))
fi

echo ""
echo "--- Audio groups ---"
if groups www-data | grep -q "audio"; then
    echo -e "${GREEN}✓${NC} www-data in audio group"
else
    echo -e "${YELLOW}⚠${NC} www-data not in audio group"
    ((WARNINGS++))
fi

echo ""
echo "=== Résumé ==="
if [ $ERRORS -eq 0 ]; then
    echo -e "${GREEN}✓ Installation OK !${NC}"
    if [ $WARNINGS -gt 0 ]; then
        echo -e "${YELLOW}⚠ $WARNINGS avertissement(s)${NC}"
    fi
else
    echo -e "${RED}✗ $ERRORS erreur(s) détectée(s)${NC}"
    echo "Exécutez : sudo bash /var/www/html/plugins/ai_connector/resources/install.sh"
fi

exit $ERRORS
