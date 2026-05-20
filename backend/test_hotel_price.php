
function createRequest($uri, $method, $data) {
    return \Illuminate\Http\Request::create($uri, $method, $data);
}

$controller = new \App\Http\Controllers\HotelController();

echo "--- TEST MODIF PRIX : Hotel Sunset Paradise ---\n";

$hotel = \App\Models\Hotel::where('nom', 'LIKE', '%Sunset Paradise%')->first();

if (!$hotel) {
    echo "ERREUR: Hotel 'Hotel Sunset Paradise' introuvable dans la base.\n";
    // Create it to test
    $hotel = \App\Models\Hotel::create([
        'nom' => 'Hotel Sunset Paradise',
        'adresse' => 'Sunset Beach 1',
        'ville' => 'Miami',
        'etoiles' => 4,
        'prix_min' => 100,
        'latitude' => 25.7617,
        'longitude' => -80.1918
    ]);
    echo "Hotel cree pour le test.\n";
}

echo "ID Hotel: " . $hotel->_id . "\n";
echo "Prix actuel: " . $hotel->prix_min . "\n";

$nouveauPrix = 150;
echo "Tentative de modification du prix a: " . $nouveauPrix . "\n";

$req = createRequest('/api/admin/hotels/'.$hotel->_id, 'PUT', [
    'prix_min' => $nouveauPrix,
]);

$res = $controller->update($req, $hotel->_id);

echo "Statut reponse: " . $res->getStatusCode() . "\n";
if ($res->getStatusCode() === 200) {
    $hotel->refresh();
    echo "Nouveau prix en base: " . $hotel->prix_min . "\n";
    if ((int)$hotel->prix_min === $nouveauPrix) {
        echo "SUCCES: Le prix a ete mis a jour!\n";
    } else {
        echo "ERREUR: Le prix n'a pas change en base.\n";
    }
} else {
    echo "ERREUR: La requête a échoué avec le message: " . json_decode($res->getContent())->message . "\n";
}

echo "--- FIN DU TEST ---\n";
