<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\AuthController;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Validator;

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$data = [
    'nom' => 'Test',
    'prenom' => 'User',
    'email' => 'test.' . bin2hex(random_bytes(4)) . '@example.com',
    'password' => 'Password123!',
    'password_confirmation' => 'Password123!',
    'telephone' => '12345678',
    'role' => 'client'
];

echo "Testing registration with: " . $data['email'] . "\n";

try {
    $request = Request::create('/api/auth/register', 'POST', $data);
    $request->headers->set('Accept', 'application/json');
    
    // Simulate validation since RegisterRequest might not automatically run here
    $rules = (new RegisterRequest())->rules();
    $validator = Validator::make($data, $rules);
    
    if ($validator->fails()) {
        echo "Validation FAILED:\n";
        print_r($validator->errors()->all());
        exit;
    }

    $response = $kernel->handle($request);
    
    echo "Status Code: " . $response->getStatusCode() . "\n";
    echo "Body: " . $response->getContent() . "\n";
    
    if ($response->getStatusCode() == 201) {
        echo "SUCCESS!\n";
    } else {
        echo "FAILED!\n";
    }

} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
