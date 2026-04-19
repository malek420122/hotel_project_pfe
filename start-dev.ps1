$ErrorActionPreference = 'Stop'

Write-Output '=== Starting HotelEase Dev Environment ==='

$rootPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$backendPath = Join-Path $rootPath 'backend'
$frontendPath = Join-Path $rootPath 'frontend'
$phpExe = 'C:\xampp\php\php.exe'
$npmExe = 'C:\Program Files\nodejs\npm.cmd'

Push-Location $backendPath
& $phpExe artisan optimize:clear | Out-Null
Pop-Location

function Stop-PortProcess {
    param([int]$Port)

    $connections = Get-NetTCPConnection -LocalPort $Port -ErrorAction SilentlyContinue
    if ($null -eq $connections) {
        return
    }

    $connections | Select-Object -ExpandProperty OwningProcess -Unique | ForEach-Object {
        $processNumber = $_
        if ($processNumber -and $processNumber -ne 0) {
            Stop-Process -Id $processNumber -Force -ErrorAction SilentlyContinue
            Write-Output "Stopped process $processNumber on port $Port"
        }
    }
}

function Get-FreePort {
    param(
        [int]$StartPort = 5173,
        [int]$EndPort = 5180
    )

    for ($port = $StartPort; $port -le $EndPort; $port++) {
        $isUsed = Get-NetTCPConnection -LocalPort $port -ErrorAction SilentlyContinue
        if (-not $isUsed) {
            return $port
        }
    }

    throw "No free port found in range $StartPort-$EndPort"
}

# 1. Stop likely conflicting server processes.
Stop-PortProcess -Port 8010
Stop-PortProcess -Port 5173
Stop-PortProcess -Port 5174

Get-CimInstance Win32_Process | Where-Object {
    $_.CommandLine -match 'php\.exe.+8010|vite|npm\.cmd run dev|node.+vite'
} | ForEach-Object {
    Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue
}

# 2. Start backend with absolute paths.
$backendArgs = "-S 127.0.0.1:8010 -t $backendPath\public $backendPath\public\index.php"
Start-Process -NoNewWindow -FilePath $phpExe -ArgumentList $backendArgs -WorkingDirectory $backendPath | Out-Null

# 3. Verify backend is up.
$backendUp = $false
for ($i = 0; $i -lt 10; $i++) {
    try {
        $status = (Invoke-WebRequest -Uri 'http://127.0.0.1:8010/api/hotels' -Method Head -TimeoutSec 5).StatusCode
        Write-Output "Backend: $status"
        $backendUp = $true
        break
    }
    catch {
        if ($i -eq 9) {
            Write-Output 'Backend: FAILED'
        }
    }
}

if (-not $backendUp) {
    throw 'Backend failed to start on http://127.0.0.1:8010'
}

# 4. Select frontend port and start Vite.
$frontendPort = Get-FreePort -StartPort 5173 -EndPort 5180
$env:VITE_PORT = [string]$frontendPort
$env:VITE_APP_PORT = [string]$frontendPort
$env:PLAYWRIGHT_BASE_URL = "http://localhost:$frontendPort"

Write-Output "Frontend target port: $frontendPort"
Start-Process -NoNewWindow -FilePath $npmExe -ArgumentList 'run dev' -WorkingDirectory $frontendPath | Out-Null

# 5. Verify frontend is up.
$frontendUp = $false
for ($i = 0; $i -lt 10; $i++) {
    try {
        $status = (Invoke-WebRequest -Uri "http://localhost:$frontendPort" -Method Head -TimeoutSec 5).StatusCode
        Write-Output "Frontend: $status on port $frontendPort"
        $frontendUp = $true
        break
    }
    catch {
        if ($i -eq 9) {
            Write-Output "Frontend: FAILED on port $frontendPort"
        }
    }
}

if (-not $frontendUp) {
    throw "Frontend failed to start on http://localhost:$frontendPort"
}

Write-Output "=== Both servers running. Open http://localhost:$frontendPort ==="
