$ErrorActionPreference = 'Stop'

$base = 'http://127.0.0.1:8000/api'
$results = @()

function Login-Api {
    param(
        [string]$Email,
        [string]$Password
    )

    $body = @{ email = $Email; password = $Password } | ConvertTo-Json
    $login = Invoke-RestMethod -Method Post -Uri "$base/auth/login" -ContentType 'application/json' -Body $body
    return @{
        user = $login.user
        headers = @{ Authorization = "Bearer $($login.token)" }
    }
}

function Get-FirstHotelRoom {
    $idsRaw = php backend\artisan tinker --execute="echo json_encode(['hotelId'=>(string) optional(\App\Models\Hotel::first())->_id,'hotelName'=>(string) optional(\App\Models\Hotel::first())->nom,'chambreId'=>(string) optional(\App\Models\Chambre::first())->_id,'chambreName'=>(string) optional(\App\Models\Chambre::first())->nom]);"
    $jsonLine = ($idsRaw -split "`n" | Where-Object { $_ -match '^\{' } | Select-Object -Last 1)
    if (-not $jsonLine) {
        throw 'Unable to parse DB IDs from tinker output.'
    }

    $obj = $jsonLine | ConvertFrom-Json
    if (-not $obj.hotelId -or -not $obj.chambreId) {
        throw 'No usable hotel/room IDs found in DB.'
    }

    return @{
        hotelId = [string]$obj.hotelId
        hotelName = [string]$obj.hotelName
        roomId = [string]$obj.chambreId
        roomName = [string]$obj.chambreName
    }
}

try {
    $client = Login-Api -Email 'demo.user@example.com' -Password 'Client123!'
    $reception = Login-Api -Email 'reception@example.com' -Password 'Reception123!'
    $admin = Login-Api -Email 'admin@hotelease.com' -Password 'Admin123!'
    $target = Get-FirstHotelRoom

    # Scenario 1: client create + cancel
    $arrivee1 = (Get-Date).AddDays(80).ToString('yyyy-MM-dd')
    $depart1 = (Get-Date).AddDays(82).ToString('yyyy-MM-dd')
    $payload1 = @{
        hotelId = $target.hotelId
        chambreId = $target.roomId
        dateArrivee = $arrivee1
        dateDepart = $depart1
        nbVoyageurs = 2
        demandesSpeciales = 'E2E cancel test'
        methodePaiement = 'carte'
        servicesChoisis = @()
    } | ConvertTo-Json -Depth 5

    $res1 = Invoke-RestMethod -Method Post -Uri "$base/reservations" -Headers $client.headers -ContentType 'application/json' -Body $payload1
    Invoke-RestMethod -Method Delete -Uri "$base/reservations/$($res1._id)" -Headers $client.headers | Out-Null
    $mine1 = Invoke-RestMethod -Method Get -Uri "$base/client/reservations" -Headers $client.headers
    $row1 = $mine1 | Where-Object { [string]$_.reference -eq [string]$res1.reference } | Select-Object -First 1

    $results += [PSCustomObject]@{
        scenario = 'client_create_cancel'
        reservationId = [string]$res1._id
        reference = [string]$res1.reference
        status = [string]$row1.statut
        pass = ([string]$row1.statut -eq 'ANNULEE')
    }

    # Scenario 2: reception confirm -> checkin -> checkout
    $arrivee2 = (Get-Date).AddDays(90).ToString('yyyy-MM-dd')
    $depart2 = (Get-Date).AddDays(92).ToString('yyyy-MM-dd')
    $payload2 = @{
        hotelId = $target.hotelId
        chambreId = $target.roomId
        dateArrivee = $arrivee2
        dateDepart = $depart2
        nbVoyageurs = 2
        demandesSpeciales = 'E2E reception lifecycle'
        methodePaiement = 'carte'
        servicesChoisis = @()
    } | ConvertTo-Json -Depth 5

    $res2 = Invoke-RestMethod -Method Post -Uri "$base/reservations" -Headers $client.headers -ContentType 'application/json' -Body $payload2
    $confirm = Invoke-RestMethod -Method Put -Uri "$base/reservations/$($res2._id)/confirmer" -Headers $reception.headers
    $checkin = Invoke-RestMethod -Method Put -Uri "$base/reservations/$($res2._id)/checkin" -Headers $reception.headers
    $checkout = Invoke-RestMethod -Method Put -Uri "$base/reservations/$($res2._id)/checkout" -Headers $reception.headers

    $results += [PSCustomObject]@{
        scenario = 'reception_lifecycle'
        reservationId = [string]$res2._id
        reference = [string]$res2.reference
        confirmedStatus = [string]$confirm.statut
        checkinStatus = [string]$checkin.statut
        checkoutStatus = [string]$checkout.statut
        pass = ([string]$confirm.statut -eq 'CONFIRMEE' -and [string]$checkin.statut -eq 'EN_COURS' -and [string]$checkout.statut -eq 'TERMINEE')
    }

    # Scenario 3: reception reject
    $arrivee3 = (Get-Date).AddDays(100).ToString('yyyy-MM-dd')
    $depart3 = (Get-Date).AddDays(101).ToString('yyyy-MM-dd')
    $payload3 = @{
        hotelId = $target.hotelId
        chambreId = $target.roomId
        dateArrivee = $arrivee3
        dateDepart = $depart3
        nbVoyageurs = 2
        demandesSpeciales = 'E2E reject path'
        methodePaiement = 'carte'
        servicesChoisis = @()
    } | ConvertTo-Json -Depth 5

    $res3 = Invoke-RestMethod -Method Post -Uri "$base/reservations" -Headers $client.headers -ContentType 'application/json' -Body $payload3
    $rejectBody = @{ motif = 'Test reject by reception' } | ConvertTo-Json
    $reject = Invoke-RestMethod -Method Put -Uri "$base/reservations/$($res3._id)/rejeter" -Headers $reception.headers -ContentType 'application/json' -Body $rejectBody

    $results += [PSCustomObject]@{
        scenario = 'reception_reject'
        reservationId = [string]$res3._id
        reference = [string]$res3.reference
        status = [string]$reject.statut
        pass = ([string]$reject.statut -eq 'REJETE')
    }

    # Scenario 4: invoice download
    $invoice = Invoke-WebRequest -Method Get -Uri "$base/reservations/$($res2._id)/invoice" -Headers $client.headers
    $contentType = [string]$invoice.Headers['Content-Type']
    $bytes = if ($invoice.Content) { [Text.Encoding]::UTF8.GetByteCount([string]$invoice.Content) } else { 0 }

    $results += [PSCustomObject]@{
        scenario = 'invoice_download'
        reservationId = [string]$res2._id
        contentType = $contentType
        contentLengthApprox = $bytes
        pass = ($contentType -like '*pdf*' -or $bytes -gt 1000)
    }

    # Scenario 5: payment processing
    $arrivee4 = (Get-Date).AddDays(110).ToString('yyyy-MM-dd')
    $depart4 = (Get-Date).AddDays(112).ToString('yyyy-MM-dd')
    $payload4 = @{
        hotelId = $target.hotelId
        chambreId = $target.roomId
        dateArrivee = $arrivee4
        dateDepart = $depart4
        nbVoyageurs = 2
        demandesSpeciales = 'E2E payment flow'
        methodePaiement = 'carte'
        servicesChoisis = @()
    } | ConvertTo-Json -Depth 5

    $res4 = Invoke-RestMethod -Method Post -Uri "$base/reservations" -Headers $client.headers -ContentType 'application/json' -Body $payload4
    $payment = Invoke-RestMethod -Method Post -Uri "$base/payments/process" -Headers $client.headers -ContentType 'application/json' -Body (@{ reservationId = $res4._id; methode = 'carte' } | ConvertTo-Json)
    $mine4 = Invoke-RestMethod -Method Get -Uri "$base/client/reservations" -Headers $client.headers
    $hist4 = Invoke-RestMethod -Method Get -Uri "$base/payments/history" -Headers $client.headers
    $adminPayments = Invoke-RestMethod -Method Get -Uri "$base/admin/payments" -Headers $admin.headers

    $reservationUpdated = $mine4 | Where-Object { [string]$_.reference -eq [string]$res4.reference } | Select-Object -First 1
    $paymentHistoryRow = $hist4 | Where-Object { [string]$_.reservationId -eq [string]$res4._id } | Select-Object -First 1
    $adminPaymentRow = $adminPayments | Where-Object { [string]$_.reservationId -eq [string]$res4._id } | Select-Object -First 1

    $results += [PSCustomObject]@{
        scenario = 'payment_process'
        reservationId = [string]$res4._id
        reference = [string]$res4.reference
        paymentStatus = [string]$payment.status
        reservationStatus = [string]$reservationUpdated.statut
        historyStatus = [string]$paymentHistoryRow.statut
        adminStatus = [string]$adminPaymentRow.statut
        pass = (
            [string]$payment.status -eq 'success' -and
            [string]$reservationUpdated.statut -eq 'CONFIRMEE' -and
            [string]$paymentHistoryRow.statut -eq 'PAYE' -and
            [string]$adminPaymentRow.statut -eq 'PAYE'
        )
    }

    [PSCustomObject]@{
        ok = $true
        testedUser = [string]$client.user.email
        testedHotel = $target.hotelName
        testedRoom = $target.roomName
        scenarios = $results
    } | ConvertTo-Json -Depth 8
}
catch {
    [PSCustomObject]@{
        ok = $false
        error = $_.Exception.Message
        scenarios = $results
    } | ConvertTo-Json -Depth 8
    exit 1
}
