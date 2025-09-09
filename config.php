<?php
// Zwraca publishable key do inicjalizacji Stripe.js po stronie klienta
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Logowanie dla debugowania
error_log('Config.php: Próba pobrania kluczy Stripe');

$publishable = getenv('STRIPE_PUBLISHABLE_KEY');
if (!$publishable && isset($_SERVER['STRIPE_PUBLISHABLE_KEY'])) {
    $publishable = $_SERVER['STRIPE_PUBLISHABLE_KEY'];
    error_log('Config.php: Klucz znaleziony w $_SERVER');
}
if (!$publishable && isset($_ENV['STRIPE_PUBLISHABLE_KEY'])) {
    $publishable = $_ENV['STRIPE_PUBLISHABLE_KEY'];
    error_log('Config.php: Klucz znaleziony w $_ENV');
}

if (!$publishable) {
    error_log('Config.php: Próba fallback z pliku stripe-config.php');
    // Fallback - wczytaj z pliku konfiguracyjnego
    if (file_exists('stripe-config.php')) {
        $config = include 'stripe-config.php';
        $publishable = $config['publishable_key'] ?? null;
        error_log('Config.php: Fallback - klucz ' . ($publishable ? 'znaleziony' : 'NIE znaleziony'));
    }
}

if (!$publishable) {
    error_log('Config.php: BŁĄD - Brak klucza STRIPE_PUBLISHABLE_KEY we wszystkich źródłach');
    http_response_code(500);
    echo json_encode([ 
        'error' => 'Brak STRIPE_PUBLISHABLE_KEY w ENV.',
        'debug' => [
            'getenv_available' => function_exists('getenv'),
            'server_available' => isset($_SERVER),
            'env_available' => isset($_ENV),
            'config_file_exists' => file_exists('stripe-config.php')
        ]
    ]);
    exit;
}

error_log('Config.php: Klucz znaleziony, długość: ' . strlen($publishable));
echo json_encode([
    'publishableKey' => $publishable
]);


