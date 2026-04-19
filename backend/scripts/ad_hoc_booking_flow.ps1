$ErrorActionPreference = 'Stop'

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$backendDir = Split-Path -Parent $scriptDir
Push-Location $backendDir

$base = 'http://127.0.0.1:8010/api'
$headers = @{ Accept = 'application/json' }
$clientEmail = 'demo.user@example.com'
$clientPassword = 'Client123!'

$client = Invoke-RestMethod -Method Post -Uri "$base/auth/login" -Headers $headers -ContentType 'application/json' -Body (@{ email = $clientEmail; password = $clientPassword } | ConvertTo-Json)
$token = [string]$client.token

$hotelsResponse = Invoke-RestMethod -Method Get -Uri "$base/hotels?page=1&per_page=1" -Headers $headers
$hotelRows = @()
if ($hotelsResponse -is [System.Array]) {
    $hotelRows = $hotelsResponse
} elseif ($null -ne $hotelsResponse.data) {
    $hotelRows = $hotelsResponse.data
} elseif ($null -ne $hotelsResponse.hotels) {
    $hotelRows = $hotelsResponse.hotels
}

if ($hotelRows.Count -eq 0) {
    Pop-Location
    throw 'No hotels returned by API.'
}

$hotelId = [string]$hotelRows[0]._id
if (-not $hotelId) {
    $hotelId = [string]$hotelRows[0].id
}

if (-not $hotelId) {
    Pop-Location
    throw 'Unable to resolve a hotel ID from API response.'
}

$roomsResponse = Invoke-RestMethod -Method Get -Uri "$base/hotels/$hotelId/chambres" -Headers $headers
$roomRows = @()
if ($roomsResponse -is [System.Array]) {
    $roomRows = $roomsResponse
} elseif ($null -ne $roomsResponse.data) {
    $roomRows = $roomsResponse.data
}

if ($roomRows.Count -eq 0) {
    Pop-Location
    throw 'No rooms returned for the selected hotel.'
}

$preferredRoom = $roomRows | Where-Object { $null -eq $_.estDisponible -or $_.estDisponible -eq $true } | Select-Object -First 1
if ($null -eq $preferredRoom) {
    $preferredRoom = $roomRows | Select-Object -First 1
}

$roomId = [string]$preferredRoom._id
if (-not $roomId) {
    $roomId = [string]$preferredRoom.id
}

if (-not $roomId) {
    Pop-Location
    throw 'Unable to resolve a room ID from API response.'
}

$roomCapacity = 1
if ($null -ne $preferredRoom.maxVoyageurs) {
    $roomCapacity = [int]$preferredRoom.maxVoyageurs
} elseif ($null -ne $preferredRoom.capacite) {
    $roomCapacity = [int]$preferredRoom.capacite
}
if ($roomCapacity -lt 1) { $roomCapacity = 1 }
$travelerCount = [Math]::Min(2, $roomCapacity)

$beforeLoyalty = Invoke-RestMethod -Method Get -Uri "$base/client/loyalty" -Headers @{ Authorization = "Bearer $token" }
$beforePoints = 0
if ($null -ne $beforeLoyalty.points) { $beforePoints = [int]$beforeLoyalty.points }
elseif ($null -ne $beforeLoyalty.totalPoints) { $beforePoints = [int]$beforeLoyalty.totalPoints }
$beforeNotifications = (Invoke-RestMethod -Method Get -Uri "$base/notifications" -Headers @{ Authorization = "Bearer $token" }).Count

$reservation = $null
$lastError = $null

for ($attempt = 0; $attempt -lt 6; $attempt++) {
    $arrival = (Get-Date).Date.AddDays(30 + ($attempt * 10)).ToString('yyyy-MM-dd')
    $departure = (Get-Date).Date.AddDays(32 + ($attempt * 10)).ToString('yyyy-MM-dd')

    $payload = @{
        hotelId = $hotelId
        chambreId = $roomId
        dateArrivee = $arrival
        dateDepart = $departure
        nbVoyageurs = $travelerCount
    } | ConvertTo-Json

    try {
        $reservation = Invoke-RestMethod -Method Post -Uri "$base/reservations" -Headers @{ Authorization = "Bearer $token" } -ContentType 'application/json' -Body $payload
        break
    } catch {
        $lastError = $_
    }
}

if ($null -eq $reservation) {
    Pop-Location
    throw "Unable to create reservation in smoke flow. Last error: $($lastError.Exception.Message)"
}
$reservationId = [string]$reservation._id

$dbReservationExists = & "C:\xampp\php\php.exe" artisan tinker --execute="echo \App\Models\Reservation::where('_id', '$reservationId')->exists() ? 'yes' : 'no';"

$afterLoyalty = Invoke-RestMethod -Method Get -Uri "$base/client/loyalty" -Headers @{ Authorization = "Bearer $token" }
$afterPoints = 0
if ($null -ne $afterLoyalty.points) { $afterPoints = [int]$afterLoyalty.points }
elseif ($null -ne $afterLoyalty.totalPoints) { $afterPoints = [int]$afterLoyalty.totalPoints }
$afterNotifications = (Invoke-RestMethod -Method Get -Uri "$base/notifications" -Headers @{ Authorization = "Bearer $token" }).Count

[PSCustomObject]@{
    clientEmail = $clientEmail
    hotelId = $hotelId
    roomId = $roomId
    reservationId = $reservationId
    reservationInDb = ($dbReservationExists -match 'yes')
    loyaltyBefore = $beforePoints
    loyaltyAfter = $afterPoints
    notificationsBefore = $beforeNotifications
    notificationsAfter = $afterNotifications
} | ConvertTo-Json -Depth 5

Pop-Location
