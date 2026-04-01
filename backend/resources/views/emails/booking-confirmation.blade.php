<h2>Confirmation de reservation</h2>
<p>Votre reservation <strong>{{ $reservation->reference }}</strong> a ete enregistree.</p>
<p>Chambre: {{ $chambre->nom ?? 'N/A' }}</p>
<p>Arrivee: {{ $reservation->dateArrivee }} | Depart: {{ $reservation->dateDepart }}</p>
<p>Montant total: {{ $reservation->prixTotal }} EUR</p>
<p>Merci de votre confiance.</p>
