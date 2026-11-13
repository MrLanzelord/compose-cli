# compose-cli

CLI para gestionar los stacks Docker Compose de tu servidor.

- **Menú interactivo** con navegación por flechas (categoría → stack → servicio → acción).
- **Estado en vivo** de cada stack directamente en el menú (arriba / con error / abajo).
- **Modo directo** de un solo comando: `compose sites/matra up -d` (todo el cliente) o `compose sites/matra cms-matra-wp up -d` (un solo servicio).
- **Configurable** mediante un archivo `.env` (directorio de stacks y sufijos de dominio).

## Requisitos

- **PHP >= 8.4**
- **Docker** con el plugin `docker compose` (v2)
- **git** (solo para la instalación por `curl`)
- Fuente con **Nerd Fonts** (opcional, para los íconos del menú)

## Instalación

```bash
curl -fsSL https://raw.githubusercontent.com/MrLanzelord/compose-cli/main/install.sh | bash
```

El instalador:

1. Clona el repositorio en `~/.local/share/compose-cli`.
2. Da permisos de ejecución a `cli.php`.
3. Crea el enlace `~/.local/bin/compose → ~/.local/share/compose-cli/cli.php`.
4. Añade `~/.local/bin` a tu `PATH` si no está (`.bashrc` / `.zshrc`).
5. Genera la configuración `compose-cli.env` (te pregunta el directorio de stacks y los sufijos de dominio).
6. Verifica con `compose --version`.

> Nota: `REPO_URL` en `install.sh` apunta a `MrLanzelord/compose-cli`; si haces un
> *fork*, ajústala a tu usuario/repo antes de publicar.

### Instalación local (sin clonar)

Si ya tienes el repositorio:

```bash
bash install.sh --local
```

### Desinstalación

```bash
bash ~/.local/share/compose-cli/uninstall.sh
```

## Uso

### Menú interactivo

```bash
compose
```

1. Elige una **categoría** (`ai`, `apps`, `core`, `instances`, `sites`).
2. Elige un **stack** (p. ej. el cliente). Cada línea muestra un ícono de estado:
   - `●` verde = arriba · `!` rojo = con error · `○` gris = abajo.
3. Elige un **servicio** del stack (o *Todos los servicios*).
4. Elige la **acción** (`up`, `up -d`, `down`, `logs -f`, `restart`, …).

Atajos: `↑/↓` mover · `Enter/→` seleccionar · `←` volver.

### Modo directo (un solo comando)

```bash
compose sites/matra up -d                 # todo el cliente
compose sites/matra cms-matra-wp up -d    # solo un servicio del cliente
compose matra restart                     # sin categoría, todo el cliente
compose core/adguard logs -f
compose --dry-run sites/matra up --build  # solo imprime el comando
```

El nombre del stack puede darse **con o sin el sufijo de dominio**
(`sites/foo` o `sites/foo.midominio.com` son equivalentes
si el sufijo está configurado). El servicio se valida contra los servicios
reales del stack (`docker compose config --services`), así que escríbelo tal
cual aparece en el `docker-compose.yml` (p. ej. `cms-matra-wp`, `matra-db`).

| Opción | Descripción |
| ------ | ----------- |
| `--dry-run` | Imprime el comando docker sin ejecutarlo |
| `-h, --help` | Muestra la ayuda |
| `-V, --version` | Muestra la versión |

## Configuración

La configuración se guarda en `compose-cli.env`, junto a `cli.php` dentro del
repositorio instalado. El orden de precedencia es:

1. Variables de entorno reales.
2. Archivo `compose-cli.env`.
3. Valores por defecto.

### Variables

| Variable | Descripción | Ejemplo |
| -------- | ----------- | ------- |
| `COMPOSE_CLI_STACKS_DIR` | Directorio base de los stacks | `/home/yo/server/docker` |
| `COMPOSE_CLI_DOMAIN_SUFFIXES` | Sufijos de dominio a ocultar (separados por comas) | `.midominio.com` |

Ejemplo de `compose-cli.env`:

```env
COMPOSE_CLI_STACKS_DIR=/home/yo/server/docker
COMPOSE_CLI_DOMAIN_SUFFIXES=.midominio.com
```

También puedes pasarlos como variables de entorno sin tocar el archivo:

```bash
COMPOSE_CLI_STACKS_DIR=/srv/docker compose sites/foo up -d
```

## Estructura de carpetas de `server/docker`

La estructura es de dos niveles: **categoría** → **servicio** → `docker-compose.yml`.

```
server/docker/
├── ai/
│   ├── ollama/
│   │   └── docker-compose.yml
│   └── open-webui/
│       └── docker-compose.yml
├── apps/
│   └── immich/
│       └── docker-compose.yml
├── core/
│   ├── adguard/
│   │   └── docker-compose.yml
│   └── npm/
│       └── docker-compose.yml
├── instances/
│   └── macos/
│       └── docker-compose.yml
└── sites/
    └── matra/                      ← 1 stack por cliente
        ├── docker-compose.yml      ← todos los sitios del cliente
        ├── .env
        ├── db/                     ← datadir (ignorado por el CLI)
        └── web/
            └── cms.matra.../       ← public_html (ignorado por el CLI)
```

- **Primer nivel**: categoría (`ai`, `apps`, `core`, `instances`, `sites`…).
- **Segundo nivel**: nombre del stack. En `sites`, cada stack es un **cliente**
  y su `docker-compose.yml` contiene **todos los sitios** de ese cliente como
  servicios (p. ej. `cms-matra-wp`, `matra-db`, `matra-pma`).
- Dentro de cada stack debe estar el archivo `docker-compose.yml`.

> El escaneo es **no recursivo** (2 niveles). Los árboles de datos (`db/`,
> `web/`, `vendor/`, …) no se recorren, lo que evita errores de permisos y
> `docker-compose.yml` anidados que no son stacks reales.

### Regla de los sufijos de dominio

Los dominios de los sitios se ocultan en el menú y en el modo directo
gracias a `COMPOSE_CLI_DOMAIN_SUFFIXES`. Así, el stack `sites/foo.midominio.com`
se muestra como `foo` y se puede gestionar con:

```bash
compose sites/foo up -d
```

**Restricción importante**: no puede haber dos stacks cuyo nombre *sin sufijo* sea el
mismo. Por ejemplo, `foo.midominio.com` y `foo.otrodominio.com`
serían ambos `foo`, lo que provoca un comando ambiguo:

```
❌ Error: 'sites/foo' es ambiguo. Coincide con: sites/foo.otrodominio.com,
   sites/foo.midominio.com. Usa el nombre completo.
```

En ese caso usa siempre el nombre completo. Si añades un nuevo dominio, incluye su
sufijo en la configuración (los más específicos primero, aunque el CLI los ordena
automáticamente por longitud).

## Solución de problemas

| Problema | Solución |
| -------- | -------- |
| `compose: command not found` | Recarga tu shell (`source ~/.bashrc` / `source ~/.zshrc`) o añade `~/.local/bin` al `PATH`. |
| `Se requiere PHP >= 8.4` | Instala/actualiza PHP. |
| El menú no muestra íconos | Usa una fuente con Nerd Fonts. |
| La CLI no encuentra tus stacks | Revisa `COMPOSE_CLI_STACKS_DIR` en `compose-cli.env`. |

## Licencia

AGPL-3.0.
