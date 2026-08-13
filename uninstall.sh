#!/usr/bin/env bash
#
# uninstall.sh — Elimina compose-cli de tu sistema.
#
# Uso:
#   bash uninstall.sh
#
set -euo pipefail

BIN_DIR="${COMPOSE_CLI_BIN_DIR:-$HOME/.local/bin}"
BIN_NAME="${COMPOSE_CLI_BIN_NAME:-compose}"
INSTALL_DIR="${COMPOSE_CLI_INSTALL_DIR:-$HOME/.local/share/compose-cli}"
CONFIG_FILE="compose-cli.env"

LINK="$BIN_DIR/$BIN_NAME"

echo "▶ Eliminando enlace $LINK"
rm -f "$LINK"

if [ -f "$INSTALL_DIR/$CONFIG_FILE" ]; then
    read -r -p "¿Eliminar también la configuración ($INSTALL_DIR/$CONFIG_FILE)? [s/N]: " -n 1 ans
    echo ""
    case "${ans:-N}" in
        s|S|y|Y)
            rm -f "$INSTALL_DIR/$CONFIG_FILE"
            echo "✓ Configuración eliminada."
            ;;
    esac
fi

if [ -d "$INSTALL_DIR" ]; then
    read -r -p "¿Eliminar también el repositorio ($INSTALL_DIR)? [s/N]: " -n 1 ans2
    echo ""
    case "${ans2:-N}" in
        s|S|y|Y)
            rm -rf "$INSTALL_DIR"
            echo "✓ Repositorio eliminado."
            ;;
    esac
fi

echo "✓ Desinstalación completada."
