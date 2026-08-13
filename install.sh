#!/usr/bin/env bash
#
# install.sh — Instala compose-cli y crea el comando `compose` en tu PATH.
#
# Uso:
#   curl -fsSL https://raw.githubusercontent.com/<usuario>/<repo>/<rama>/install.sh | bash
#   bash install.sh --local            # instala desde el repositorio actual (sin clonar)
#
# Variables de entorno opcionales:
#   COMPOSE_CLI_REPO_URL      URL del repositorio (por defecto la de abajo).
#   COMPOSE_CLI_REPO_BRANCH   rama a clonar (por defecto: main).
#   COMPOSE_CLI_INSTALL_DIR   directorio del código (por defecto: ~/.local/share/compose-cli).
#   COMPOSE_CLI_BIN_DIR       directorio del enlace (por defecto: ~/.local/bin).
#   COMPOSE_CLI_BIN_NAME      nombre del comando (por defecto: compose).
#   COMPOSE_CLI_STACKS_DIR    directorio de stacks para generar la config.
#   COMPOSE_CLI_DOMAIN_SUFFIXES  sufijos de dominio para generar la config.
#
set -euo pipefail

# ---------------------------------------------------------------------------
# Configuración (ajusta REPO_URL al publicar en GitHub)
# ---------------------------------------------------------------------------
REPO_URL="${COMPOSE_CLI_REPO_URL:-https://github.com/MrLanzelord/compose-cli.git}"
REPO_BRANCH="${COMPOSE_CLI_REPO_BRANCH:-main}"
INSTALL_DIR="${COMPOSE_CLI_INSTALL_DIR:-$HOME/.local/share/compose-cli}"
BIN_DIR="${COMPOSE_CLI_BIN_DIR:-$HOME/.local/bin}"
BIN_NAME="${COMPOSE_CLI_BIN_NAME:-compose}"
CONFIG_FILE="compose-cli.env"

MODE="clone"
for arg in "$@"; do
    case "$arg" in
        --local) MODE="local" ;;
    esac
done

# ---------------------------------------------------------------------------
# Pre-requisitos
# ---------------------------------------------------------------------------
if ! command -v php >/dev/null 2>&1; then
    echo "❌ No se encontró PHP. Instala PHP >= 8.4 y vuelve a ejecutar." >&2
    exit 1
fi

if ! php -r 'exit(version_compare(PHP_VERSION, "8.4.0", ">=") ? 0 : 1);'; then
    echo "❌ Se requiere PHP >= 8.4. Detectado: $(php -v | head -n1)" >&2
    exit 1
fi

if ! command -v docker >/dev/null 2>&1; then
    echo "⚠️  No se encontró docker. Se instalará igual, pero necesitarás Docker para usar los stacks." >&2
fi

if [ "$MODE" = "clone" ]; then
    if ! command -v git >/dev/null 2>&1; then
        echo "❌ No se encontró git. Instálalo o usa 'bash install.sh --local' desde el repositorio." >&2
        exit 1
    fi

    if [[ "$REPO_URL" == *"USUARIO"* ]]; then
        echo "❌ Edita REPO_URL al inicio de install.sh con la URL real de tu repositorio." >&2
        exit 1
    fi
fi

# ---------------------------------------------------------------------------
# Obtener el código
# ---------------------------------------------------------------------------
if [ "$MODE" = "clone" ]; then
    echo "▶ Clonando $REPO_URL → $INSTALL_DIR"
    if [ -d "$INSTALL_DIR/.git" ]; then
        git -C "$INSTALL_DIR" pull --ff-only origin "$REPO_BRANCH" >/dev/null
    else
        mkdir -p "$(dirname "$INSTALL_DIR")"
        git clone --depth 1 --branch "$REPO_BRANCH" "$REPO_URL" "$INSTALL_DIR"
    fi
else
    SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

    if [ ! -f "$SCRIPT_DIR/cli.php" ]; then
        echo "❌ --local debe ejecutarse desde el directorio del repositorio (falta cli.php)." >&2
        exit 1
    fi

    echo "▶ Instalando desde el directorio local: $SCRIPT_DIR"
    mkdir -p "$INSTALL_DIR"

    if [ -f "$INSTALL_DIR/$CONFIG_FILE" ]; then
        cp "$INSTALL_DIR/$CONFIG_FILE" "$INSTALL_DIR/.compose-env-backup"
    fi

    cp -R "$SCRIPT_DIR/." "$INSTALL_DIR/"
    rm -f "$INSTALL_DIR/$CONFIG_FILE"

    if [ -f "$INSTALL_DIR/.compose-env-backup" ]; then
        mv "$INSTALL_DIR/.compose-env-backup" "$INSTALL_DIR/$CONFIG_FILE"
    fi
fi

# ---------------------------------------------------------------------------
# Permisos de ejecución
# ---------------------------------------------------------------------------
chmod +x "$INSTALL_DIR/cli.php"

# ---------------------------------------------------------------------------
# Enlace en ~/.local/bin
# ---------------------------------------------------------------------------
mkdir -p "$BIN_DIR"
ln -sfn "$INSTALL_DIR/cli.php" "$BIN_DIR/$BIN_NAME"
echo "✓ Enlace creado: $BIN_DIR/$BIN_NAME → $INSTALL_DIR/cli.php"

if ! printf '%s' "$PATH" | grep -qF ":$BIN_DIR:" && ! [ "$PATH" = "$BIN_DIR" ]; then
    echo "⚠️  $BIN_DIR no está en tu PATH. Lo añado a tus archivos de shell:"
    for rc in "$HOME/.bashrc" "$HOME/.zshrc"; do
        if [ -f "$rc" ]; then
            printf '\n# compose-cli\nexport PATH="%s:$PATH"\n' "$BIN_DIR" >> "$rc"
            echo "   → $rc"
        fi
    done
fi

# ---------------------------------------------------------------------------
# Configuración (.env) — solo si no existe
# ---------------------------------------------------------------------------
if [ ! -f "$INSTALL_DIR/$CONFIG_FILE" ]; then
    echo ""
    echo "⚙️  Generando $CONFIG_FILE ..."

    default_stacks="${COMPOSE_CLI_STACKS_DIR:-}"
    if [ -z "$default_stacks" ]; then
        for candidate in "$HOME/server/docker" "$HOME/docker"; do
            if [ -d "$candidate" ]; then
                default_stacks="$candidate"
                break
            fi
        done
    fi
    default_stacks="${default_stacks:-$HOME/server/docker}"

    printf 'Directorio de los stacks [%s]: ' "$default_stacks"
    read -r stacks_dir || true
    stacks_dir="${stacks_dir:-$default_stacks}"

    default_suffixes="${COMPOSE_CLI_DOMAIN_SUFFIXES:-.midominio.com}"
    printf 'Sufijos de dominio a ocultar (separados por comas) [%s]: ' "$default_suffixes"
    read -r suffixes || true
    suffixes="${suffixes:-$default_suffixes}"

    cat > "$INSTALL_DIR/$CONFIG_FILE" <<EOF
# Configuración local de compose-cli (generada por install.sh).
# No se sube a git. Ajusta los valores a tu entorno.
COMPOSE_CLI_STACKS_DIR=$stacks_dir
COMPOSE_CLI_DOMAIN_SUFFIXES=$suffixes
EOF
    echo "✓ Configuración escrita en $INSTALL_DIR/$CONFIG_FILE"
fi

# ---------------------------------------------------------------------------
# Verificación
# ---------------------------------------------------------------------------
echo ""
if "$BIN_DIR/$BIN_NAME" --version >/dev/null 2>&1; then
    echo "✅ Instalación completada."
    echo ""
    echo "   Uso directo:   $BIN_NAME sites/foo up -d"
    echo "   Menú interactivo: $BIN_NAME"
    echo "   Ayuda:         $BIN_NAME --help"
    echo ""
    echo "   Si el comando no aparece, recarga tu shell: source ~/.bashrc  (o ~/.zshrc)"
else
    echo "⚠️  El enlace se creó pero la verificación falló. Revisa:" >&2
    echo "   - $BIN_DIR/$BIN_NAME" >&2
    echo "   - Configuración en $INSTALL_DIR/$CONFIG_FILE" >&2
    echo "   - Ejecuta en el terminal: $BIN_DIR/$BIN_NAME --version" >&2
    exit 1
fi
