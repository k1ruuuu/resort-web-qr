#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# Prerequisites installer for resort-web-qr
# Installs: Docker Engine, Docker Compose, Git, Make, curl, unzip
# Supports: Ubuntu/Debian, CentOS/RHEL/Fedora, macOS (Homebrew)
# =============================================================================

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'
info()  { echo -e "${GREEN}[INFO]${NC} $*"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $*"; }
error() { echo -e "${RED}[ERROR]${NC} $*"; }
section() { echo -e "\n${CYAN}══════════════════════════════════════════════════${NC}"; echo -e "${CYAN}  $*${NC}"; echo -e "${CYAN}══════════════════════════════════════════════════${NC}"; }

detect_os() {
    case "$(uname -s)" in
        Linux)
            if [[ -f /etc/os-release ]]; then
                . /etc/os-release
                OS="$ID"
                OS_LIKE="$ID_LIKE"
            else
                OS="unknown"
            fi
            ;;
        Darwin)
            OS="macos"
            ;;
        *)
            OS="unknown"
            ;;
    esac
}

check_command() {
    command -v "$1" &>/dev/null
}

install_docker_ubuntu() {
    info "Installing Docker Engine (Ubuntu/Debian)..."
    apt update -y
    apt install -y ca-certificates curl
    install -m 0755 -d /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
    chmod a+r /etc/apt/keyrings/docker.asc
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "${UBUNTU_CODENAME:-$VERSION_CODENAME}") stable" > /etc/apt/sources.list.d/docker.list
    apt update -y
    apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
}

install_docker_fedora() {
    info "Installing Docker Engine (CentOS/RHEL/Fedora)..."
    if command -v dnf &>/dev/null; then
        dnf -y install dnf-plugins-core
        dnf config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo
        dnf -y install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
    elif command -v yum &>/dev/null; then
        yum install -y yum-utils
        yum-config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo
        yum install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
    fi
}

install_docker_macos() {
    info "Installing Docker Desktop (macOS)..."
    if ! check_command brew; then
        info "Installing Homebrew first..."
        /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
    fi
    brew install --cask docker
}

install_prerequisites() {
    # --- System packages ---
    case "$OS" in
        ubuntu|debian|pop|linuxmint|elementary|zorin)
            section "Installing system packages (apt)"
            apt update -y
            apt install -y git make curl unzip ca-certificates
            ;;

        rhel|centos|fedora|rocky|almalinux)
            section "Installing system packages (dnf/yum)"
            local cmd="dnf"
            if ! check_command dnf; then cmd="yum"; fi
            $cmd install -y git make curl unzip ca-certificates
            ;;

        macos)
            section "Installing system packages (Homebrew)"
            if ! check_command brew; then
                /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
            fi
            brew install git make curl unzip
            ;;

        *)
            error "Unsupported OS: $OS"
            error "Please install Docker, Docker Compose, Git, and Make manually."
            exit 1
            ;;
    esac

    # --- Docker Engine ---
    section "Docker Engine"
    if check_command docker; then
        info "Docker already installed: $(docker --version)"
    else
        case "$OS" in
            ubuntu|debian|pop|linuxmint|elementary|zorin) install_docker_ubuntu ;;
            rhel|centos|fedora|rocky|almalinux) install_docker_fedora ;;
            macos) install_docker_macos ;;
        esac
    fi

    # --- Docker Compose ---
    section "Docker Compose"
    if check_command docker-compose; then
        info "Docker Compose (standalone): $(docker-compose --version)"
    elif docker compose version &>/dev/null; then
        info "Docker Compose (plugin): $(docker compose version)"
    else
        warn "Docker Compose not found. Installing plugin..."
        case "$OS" in
            ubuntu|debian|pop|linuxmint|elementary|zorin)
                apt install -y docker-compose-plugin
                ;;
            rhel|centos|fedora|rocky|almalinux)
                if check_command dnf; then
                    dnf install -y docker-compose-plugin
                else
                    yum install -y docker-compose-plugin
                fi
                ;;
            macos)
                warn "Docker Desktop for Mac includes Compose. Reinstall if missing."
                ;;
        esac
    fi

    # --- Start & enable Docker ---
    if [[ "$OS" != "macos" ]]; then
        section "Starting Docker service"
        systemctl enable docker 2>/dev/null || true
        systemctl start docker 2>/dev/null || true
    fi
}

configure_user() {
    section "Adding user to docker group"
    if [[ "$OS" != "macos" ]]; then
        if groups "$SUDO_USER" 2>/dev/null | grep -qv docker; then
            usermod -aG docker "$SUDO_USER"
            info "User '$SUDO_USER' added to 'docker' group."
            warn "You must log out and back in for group changes to take effect."
        else
            info "User '$SUDO_USER' is already in the 'docker' group."
        fi
    fi
}

verify() {
    section "Verification"
    echo ""
    info "Docker:        $(docker --version 2>/dev/null || echo 'NOT INSTALLED')"
    if docker compose version &>/dev/null; then
        info "Docker Compose: $(docker compose version)"
    elif check_command docker-compose; then
        info "Docker Compose: $(docker-compose --version)"
    else
        info "Docker Compose: NOT INSTALLED"
    fi
    info "Git:           $(git --version 2>/dev/null || echo 'NOT INSTALLED')"
    info "Make:          $(make --version 2>/dev/null | head -1 || echo 'NOT INSTALLED')"
    info "curl:          $(curl --version 2>/dev/null | head -1 || echo 'NOT INSTALLED')"
    info "unzip:         $(unzip -v 2>/dev/null | head -1 || echo 'NOT INSTALLED')"
    echo ""

    if docker info &>/dev/null; then
        info "Docker daemon is running and accessible."
    else
        warn "Docker daemon is not accessible."
        warn "If you were just added to the 'docker' group, log out and back in."
        warn "Otherwise, check: sudo systemctl status docker"
    fi
}

# =============================================================================
# Main
# =============================================================================
if [[ "$(uname -s)" != "Darwin" && "$EUID" -ne 0 ]]; then
    echo "This script requires root privileges for package installation."
    echo "Re-run with: sudo bash $0"
    exit 1
fi

echo ""
echo -e "${CYAN}╔═══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║     resort-web-qr — Prerequisites Installer                 ║${NC}"
echo -e "${CYAN}╚═══════════════════════════════════════════════════════════════╝${NC}"
echo ""

detect_os
info "Detected OS: $OS"

install_prerequisites
configure_user
verify

section "Next steps"
echo ""
echo "  1. Clone the repository (if not already done):"
echo "     git clone <repo-url> resort-web-qr"
echo "     cd resort-web-qr"
echo ""
echo "  2. Configure environment:"
echo "     cp .env.docker .env"
echo "     # Edit .env and set DB_PASSWORD, REDIS_PASSWORD, APP_URL"
echo ""
echo "  3. Build and start:"
echo "     docker compose build"
echo "     docker compose up -d"
echo ""
echo "  4. First-run setup:"
echo "     docker compose exec app php artisan key:generate"
echo "     docker compose exec app php artisan migrate"
echo ""
echo "  Or use the Makefile:"
echo "     make fresh-install"
echo ""
