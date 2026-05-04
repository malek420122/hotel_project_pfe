<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Promotion;

$promos = Promotion::all();
$count = 0;
foreach ($promos as $p) {
    if (is_array($p->dateDebut) || empty($p->dateDebut)) {
        $p->dateDebut = $p->created_at ?? now();
        $p->dateFin = ($p->created_at ?? now())->copy()->addMonths(3);
        $p->save();
        $count++;
    }
}
echo "Fixed $count corrupted promos.\n";
