<#
.SYNOPSIS
    Prerequisites installer for resort-web-qr (Windows)
.DESCRIPTION
    Installs Docker Desktop, Git for Windows, and GNU Make via Chocolatey or Winget.
    Run this script from an Administrator PowerShell prompt.
#>

#requires -RunAsAdministrator

$Host.UI.RawUI.ForegroundColor = "Cyan"
Write-Host "╔═══════════════════════════════════════════════════════════════════╗"
Write-Host "║     resort-web-qr — Prerequisites Installer (Windows)          ║"
Write-Host "╚═══════════════════════════════════════════════════════════════════╝"
Write-Host ""
$Host.UI.RawUI.ForegroundColor = "White"

function Write-Info  { Write-Host "[INFO]  $args" -ForegroundColor Green }
function Write-Warn  { Write-Host "[WARN]  $args" -ForegroundColor Yellow }
function Write-Error { Write-Host "[ERROR] $args" -ForegroundColor Red }

# ---- Check if winget is available (Windows 10 1809+ / Windows 11) ----
$UseWinget = $false
if (Get-Command winget -ErrorAction SilentlyContinue) {
    $UseWinget = $true
    Write-Info "Using winget package manager."
} else {
    Write-Info "winget not found. Will use Chocolatey or manual install."
}

# ---- Helper: install via winget ----
function Install-WithWinget {
    param([string]$Name, [string]$WingetId, [string]$ChocoPackage)
    if ($UseWinget) {
        $installed = winget list --id $WingetId 2>$null | Select-String $WingetId
        if (-not $installed) {
            Write-Info "Installing $Name via winget..."
            winget install --id $WingetId --silent --accept-package-agreements --accept-source-agreements
        } else {
            Write-Info "$Name already installed."
        }
    } elseif (Get-Command choco -ErrorAction SilentlyContinue) {
        $installed = choco list $ChocoPackage --local-only 2>$null | Select-String $ChocoPackage
        if (-not $installed) {
            Write-Info "Installing $Name via Chocolatey..."
            choco install $ChocoPackage -y
        } else {
            Write-Info "$Name already installed."
        }
    } else {
        Write-Warn "No package manager available. Please install $Name manually."
    }
}

# ---- Install Chocolatey if neither winget nor choco exists ----
if (-not $UseWinget -and -not (Get-Command choco -ErrorAction SilentlyContinue)) {
    Write-Info "Installing Chocolatey package manager..."
    Set-ExecutionPolicy Bypass -Scope Process -Force
    [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072
    Invoke-Expression ((New-Object System.Net.WebClient).DownloadString('https://chocolatey.org/install.ps1'))
    refreshenv
}

# ---- 1. Git for Windows ----
Write-Host ""
Write-Host "─────────────────────────────────────────────────────────────────" -ForegroundColor Cyan
Write-Host "  Installing Git" -ForegroundColor Cyan
Write-Host "─────────────────────────────────────────────────────────────────" -ForegroundColor Cyan
if (Get-Command git -ErrorAction SilentlyContinue) {
    Write-Info "Git already installed: $(git --version)"
} else {
    Install-WithWinget -Name "Git" -WingetId "Git.Git" -ChocoPackage "git"
}

# ---- 2. Docker Desktop ----
Write-Host ""
Write-Host "─────────────────────────────────────────────────────────────────" -ForegroundColor Cyan
Write-Host "  Installing Docker Desktop" -ForegroundColor Cyan
Write-Host "─────────────────────────────────────────────────────────────────" -ForegroundColor Cyan
if (Get-Command docker -ErrorAction SilentlyContinue) {
    Write-Info "Docker already installed: $(docker --version)"
} else {
    Install-WithWinget -Name "Docker Desktop" -WingetId "Docker.DockerDesktop" -ChocoPackage "docker-desktop"
    Write-Warn "Docker Desktop requires a reboot or manual start."
    Write-Warn "After installation, launch Docker Desktop from the Start menu."
}

# ---- 3. GNU Make ----
Write-Host ""
Write-Host "─────────────────────────────────────────────────────────────────" -ForegroundColor Cyan
Write-Host "  Installing GNU Make" -ForegroundColor Cyan
Write-Host "─────────────────────────────────────────────────────────────────" -ForegroundColor Cyan
if (Get-Command make -ErrorAction SilentlyContinue) {
    Write-Info "Make already installed."
} else {
    Install-WithWinget -Name "GNU Make" -WingetId "GnuWin32.Make" -ChocoPackage "make"
    if (-not (Get-Command make -ErrorAction SilentlyContinue)) {
        Write-Warn "Make not found via package manager."
        Write-Warn "Install manually: https://gnuwin32.sourceforge.net/packages/make.htm"
        Write-Warn "Or use 'choco install make' in an admin shell."
    }
}

# ---- 4. curl and unzip (usually present on Windows 10+) ----
Write-Host ""
Write-Host "─────────────────────────────────────────────────────────────────" -ForegroundColor Cyan
Write-Host "  Verifying curl and unzip" -ForegroundColor Cyan
Write-Host "─────────────────────────────────────────────────────────────────" -ForegroundColor Cyan
if (Get-Command curl -ErrorAction SilentlyContinue) {
    Write-Info "curl available."
} else {
    Write-Warn "curl not found — install via: winget install cURL.cURL"
}
if (Get-Command unzip -ErrorAction SilentlyContinue) {
    Write-Info "unzip available."
} else {
    Write-Warn "unzip not found — install via: winget install GnuWin32.Unzip"
}

# ---- 5. Verify ----
Write-Host ""
Write-Host "─────────────────────────────────────────────────────────────────" -ForegroundColor Cyan
Write-Host "  Verification" -ForegroundColor Cyan
Write-Host "─────────────────────────────────────────────────────────────────" -ForegroundColor Cyan
Write-Host ""

try {
    $dv = docker --version 2>$null
    Write-Info "Docker:        $dv"
} catch {
    Write-Info "Docker:        NOT INSTALLED"
}

try {
    $dcv = docker compose version 2>$null
    Write-Info "Docker Compose: $dcv"
} catch {
    try {
        $dcv = docker-compose --version 2>$null
        Write-Info "Docker Compose: $dcv"
    } catch {
        Write-Info "Docker Compose: NOT INSTALLED"
    }
}

try {
    $gv = git --version 2>$null
    Write-Info "Git:           $gv"
} catch {
    Write-Info "Git:           NOT INSTALLED"
}

try {
    $mv = make --version 2>$null | Select-Object -First 1
    Write-Info "Make:          $mv"
} catch {
    Write-Info "Make:          NOT INSTALLED"
}

Write-Host ""
Write-Host "─────────────────────────────────────────────────────────────────" -ForegroundColor Cyan
Write-Host "  Next steps" -ForegroundColor Cyan
Write-Host "─────────────────────────────────────────────────────────────────" -ForegroundColor Cyan
Write-Host ""
Write-Host "  1. Clone the repository:"
Write-Host "     git clone <repo-url> resort-web-qr"
Write-Host "     cd resort-web-qr"
Write-Host ""
Write-Host "  2. Configure environment:"
Write-Host "     copy .env.docker .env"
Write-Host "     # Edit .env and set DB_PASSWORD, REDIS_PASSWORD, APP_URL"
Write-Host ""
Write-Host "  3. Build and start:"
Write-Host "     docker compose build"
Write-Host "     docker compose up -d"
Write-Host ""
Write-Host "  4. First-run setup:"
Write-Host "     docker compose exec app php artisan key:generate"
Write-Host "     docker compose exec app php artisan migrate"
Write-Host ""
Write-Host "  Or use the Makefile:"
Write-Host "     make fresh-install"
Write-Host ""
