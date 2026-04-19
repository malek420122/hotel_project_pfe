<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture - {{ $reservation->reference ?? 'N/A' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 13px; color: #333; background: #fff; }
        .header { background: #1a365d; color: #fff; padding: 30px 40px; display: table; width: 100%; }
        .header-left { display: table-cell; vertical-align: middle; }
        .header-right { display: table-cell; text-align: right; vertical-align: middle; }
        .brand { font-size: 28px; font-weight: bold; letter-spacing: 2px; }
        .brand span { color: #63b3ed; }
        .tagline { font-size: 11px; color: #bee3f8; margin-top: 4px; }
        .invoice-title { font-size: 20px; font-weight: bold; }
        .invoice-ref { font-size: 14px; color: #bee3f8; margin-top: 4px; }
        .content { padding: 30px 40px; }
        .section { margin-bottom: 25px; }
        .section-title { font-size: 14px; font-weight: bold; color: #1a365d; border-bottom: 2px solid #1a365d; padding-bottom: 6px; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1px; }
        .info-grid { display: table; width: 100%; }
        .info-col { display: table-cell; width: 50%; vertical-align: top; padding-right: 20px; }
        .info-col:last-child { padding-right: 0; }
        .info-row { margin-bottom: 6px; }
        .info-label { font-weight: bold; color: #4a5568; display: inline-block; width: 130px; }
        .info-value { color: #2d3748; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th { background: #1a365d; color: #fff; padding: 10px 12px; text-align: left; font-size: 12px; }
        table.items td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; }
        table.items tr:last-child td { border-bottom: none; }
        table.items tr:nth-child(even) td { background: #f7fafc; }
        .totals { float: right; width: 280px; margin-top: 15px; }
        .total-row { display: table; width: 100%; margin-bottom: 5px; }
        .total-label { display: table-cell; text-align: left; color: #4a5568; }
        .total-value { display: table-cell; text-align: right; font-weight: bold; }
        .total-final { font-size: 16px; color: #1a365d; border-top: 2px solid #1a365d; padding-top: 8px; margin-top: 8px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .badge-success { background: #c6f6d5; color: #276749; }
        .badge-warning { background: #feebc8; color: #744210; }
        .badge-info { background: #bee3f8; color: #1a365d; }
        .footer { margin-top: 40px; padding: 20px 40px; background: #f7fafc; border-top: 2px solid #e2e8f0; text-align: center; font-size: 11px; color: #718096; }
        .clearfix::after { content: ""; display: table; clear: both; }
        .stars { color: #f6ad55; }
    </style>
</head>
<body>

@php
    $asText = function ($value, string $fallback = 'N/A') {
        if (is_null($value) || $value === '') {
            return $fallback;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            foreach (['fr', 'en', 'ar', 'name', 'nom', 'label', 'value'] as $key) {
                if (isset($value[$key]) && is_scalar($value[$key]) && $value[$key] !== '') {
                    return (string) $value[$key];
                }
            }

            $flat = collect($value)
                ->filter(fn ($item) => is_scalar($item) && $item !== '')
                ->map(fn ($item) => (string) $item)
                ->values();

            return $flat->isNotEmpty() ? $flat->implode(', ') : $fallback;
        }

        return $fallback;
    };
@endphp

<div class="header">
    <div class="header-left">
        <div class="brand">Hotel<span>Ease</span></div>
        <div class="tagline">Votre partenaire de confiance pour des séjours exceptionnels</div>
    </div>
    <div class="header-right">
        <div class="invoice-title">FACTURE</div>
        <div class="invoice-ref">{{ $reservation->reference ?? 'N/A' }}</div>
        <div style="font-size:11px; color:#bee3f8; margin-top:4px;">Émise le {{ now()->format('d/m/Y') }}</div>
    </div>
</div>

<div class="content">

    <div class="section">
        <div class="info-grid">
            <div class="info-col">
                <div class="section-title">Client</div>
                @if($client)
                <div class="info-row"><span class="info-label">Nom :</span><span class="info-value">{{ $client->prenom }} {{ $client->nom }}</span></div>
                <div class="info-row"><span class="info-label">Email :</span><span class="info-value">{{ $client->email }}</span></div>
                <div class="info-row"><span class="info-label">Téléphone :</span><span class="info-value">{{ $client->telephone ?? 'N/A' }}</span></div>
                <div class="info-row"><span class="info-label">Fidélité :</span><span class="info-value">{{ $client->niveau_fidelite ?? 'Bronze' }} ({{ $client->points_fidelite ?? 0 }} pts)</span></div>
                @else
                <div class="info-row"><span class="info-value">Client non trouvé</span></div>
                @endif
            </div>
            <div class="info-col">
                <div class="section-title">Établissement</div>
                @if($hotel)
                <div class="info-row"><span class="info-label">Hôtel :</span><span class="info-value">{{ $asText($hotel->nom) }}</span></div>
                <div class="info-row"><span class="info-label">Adresse :</span><span class="info-value">{{ $asText($hotel->adresse) }}</span></div>
                <div class="info-row"><span class="info-label">Ville :</span><span class="info-value">{{ $asText($hotel->ville) }}</span></div>
                <div class="info-row"><span class="info-label">Étoiles :</span><span class="stars">{{ str_repeat('★', $hotel->etoiles ?? 3) }}</span></div>
                @else
                <div class="info-row"><span class="info-value">Hôtel non trouvé</span></div>
                @endif
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Détails du séjour</div>
        <div class="info-grid">
            <div class="info-col">
                <div class="info-row"><span class="info-label">Chambre :</span><span class="info-value">{{ $asText($chambre->nom ?? null) }} ({{ $asText($chambre->type ?? null, '') }})</span></div>
                <div class="info-row"><span class="info-label">Arrivée :</span><span class="info-value">{{ \Carbon\Carbon::parse($reservation->dateArrivee)->format('d/m/Y') }}</span></div>
                <div class="info-row"><span class="info-label">Départ :</span><span class="info-value">{{ \Carbon\Carbon::parse($reservation->dateDepart)->format('d/m/Y') }}</span></div>
            </div>
            <div class="info-col">
                <div class="info-row"><span class="info-label">Voyageurs :</span><span class="info-value">{{ $reservation->nbVoyageurs }}</span></div>
                <div class="info-row"><span class="info-label">Nuits :</span><span class="info-value">{{ \Carbon\Carbon::parse($reservation->dateArrivee)->diffInDays(\Carbon\Carbon::parse($reservation->dateDepart)) }}</span></div>
                <div class="info-row"><span class="info-label">Statut :</span>
                    @php $statut = $reservation->statut ?? 'EN_ATTENTE'; @endphp
                    <span class="badge {{ in_array($statut, ['TERMINEE','CONFIRMEE']) ? 'badge-success' : (in_array($statut, ['EN_COURS']) ? 'badge-info' : 'badge-warning') }}">{{ $statut }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Détail des prestations</div>
        <table class="items">
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align:right;">Quantité</th>
                    <th style="text-align:right;">Prix unitaire</th>
                    <th style="text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $nuits = \Carbon\Carbon::parse($reservation->dateArrivee)->diffInDays(\Carbon\Carbon::parse($reservation->dateDepart));
                    $prixNuit = $chambre ? $chambre->prix_base : 0;
                    $sousTotal = $prixNuit * $nuits;
                @endphp
                <tr>
                    <td>Chambre {{ $asText($chambre->nom ?? null) }} ({{ $asText($chambre->type ?? null, '') }})</td>
                    <td style="text-align:right;">{{ $nuits }} nuit(s)</td>
                    <td style="text-align:right;">{{ number_format($prixNuit, 2) }} €</td>
                    <td style="text-align:right;">{{ number_format($sousTotal, 2) }} €</td>
                </tr>
                @if(!empty($reservation->servicesChoisis) && is_array($reservation->servicesChoisis))
                    @foreach($reservation->servicesChoisis as $service)
                    <tr>
                        <td>Service : {{ $asText($service['nom'] ?? null, 'Service supplémentaire') }}</td>
                        <td style="text-align:right;">1</td>
                        <td style="text-align:right;">{{ number_format($service['prix'] ?? 0, 2) }} €</td>
                        <td style="text-align:right;">{{ number_format($service['prix'] ?? 0, 2) }} €</td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    <div class="clearfix">
        <div class="totals">
            <div class="total-row">
                <span class="total-label">Sous-total :</span>
                <span class="total-value">{{ number_format($sousTotal, 2) }} €</span>
            </div>
            @if(($reservation->remiseAppliquee ?? 0) > 0)
            <div class="total-row">
                <span class="total-label">Remise ({{ $reservation->remiseAppliquee }}%) :</span>
                <span class="total-value" style="color:#e53e3e;">-{{ number_format($sousTotal * $reservation->remiseAppliquee / 100, 2) }} €</span>
            </div>
            @endif
            <div class="total-row total-final">
                <span class="total-label" style="font-weight:bold; font-size:16px;">TOTAL TTC :</span>
                <span class="total-value" style="font-size:18px; color:#1a365d;">{{ number_format($reservation->prixTotal ?? 0, 2) }} €</span>
            </div>
        </div>
    </div>

    @if($reservation->demandesSpeciales)
    <div class="section" style="margin-top:40px;">
        <div class="section-title">Demandes spéciales</div>
        <p style="color:#4a5568; font-style:italic;">{{ $asText($reservation->demandesSpeciales, '-') }}</p>
    </div>
    @endif

</div>

<div class="footer">
    <p style="font-weight:bold; font-size:13px; color:#1a365d; margin-bottom:6px;">HotelEase — Votre satisfaction est notre priorité</p>
    <p>Pour toute question concernant cette facture, contactez-nous à : support@hotelease.com</p>
    <p style="margin-top:4px;">Ce document est généré automatiquement et constitue une preuve de réservation officielle.</p>
</div>

</body>
</html>
