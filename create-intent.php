<?php
// Tworzy PaymentIntent dla wybranej kwoty i metody, z włączonymi automatic_payment_methods
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$secret = getenv('STRIPE_SECRET_KEY');
if (!$secret && isset($_SERVER['STRIPE_SECRET_KEY'])) {
    $secret = $_SERVER['STRIPE_SECRET_KEY'];
}
if (!$secret && isset($_ENV['STRIPE_SECRET_KEY'])) {
    $secret = $_ENV['STRIPE_SECRET_KEY'];
}
if (!$secret) {
    // Fallback - wczytaj z pliku konfiguracyjnego
    if (file_exists('stripe-config.php')) {
        $config = include 'stripe-config.php';
        $secret = $config['secret_key'] ?? null;
    }
}
if (!$secret) {
    http_response_code(500);
    echo json_encode([ 'error' => 'Brak STRIPE_SECRET_KEY w ENV i stripe-config.php.' ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$amount = (int)($input['amount'] ?? 0);
$planKey = (string)($input['plan'] ?? '');
$email = isset($input['email']) ? trim($input['email']) : '';
$fullName = isset($input['full_name']) ? trim($input['full_name']) : '';
$nip = isset($input['nip']) ? trim($input['nip']) : '';
$companyName = isset($input['company_name']) ? trim($input['company_name']) : '';
$companyAddress = isset($input['company_address']) ? trim($input['company_address']) : '';
$vatStatus = isset($input['vat_status']) ? trim($input['vat_status']) : '';
$regon = isset($input['regon']) ? trim($input['regon']) : '';
$krs = isset($input['krs']) ? trim($input['krs']) : '';

// Biała lista kwot (PLN -> grosze)
$allowed = [
    'standard' => 14900,
    'premium'  => 29900,
    'vip'      => 49900,
];

if (!isset($allowed[$planKey]) || $allowed[$planKey] !== $amount) {
    http_response_code(400);
    echo json_encode([ 'error' => 'Nieprawidłowy wariant lub kwota.' ]);
    exit;
}

// Opis i metadata
$planNames = [ 'standard' => 'Kurs Korki AI – Standard', 'premium' => 'Kurs Korki AI – Premium', 'vip' => 'Kurs Korki AI – VIP + zasoby' ];
$description = $planNames[$planKey] . ' (' . number_format($amount / 100, 2, ',', ' ') . ' PLN)';

// Przygotowanie danych do Stripe API (cURL)
$postFields = [
    'amount' => $amount,
    'currency' => 'pln',
    'description' => $description,
    'automatic_payment_methods[enabled]' => 'true',
    'automatic_payment_methods[allow_redirects]' => 'always',
    'payment_method_options[card][request_three_d_secure]' => 'automatic',

    'payment_method_options[p24][tos_shown_and_accepted]' => 'true',
    'metadata[plan]' => $planKey,
    'metadata[full_name]' => $fullName,
    'metadata[nip]' => $nip,
    'metadata[company_name]' => $companyName,
    'metadata[company_address]' => $companyAddress,
    'metadata[vat_status]' => $vatStatus,
    'metadata[regon]' => $regon,
    'metadata[krs]' => $krs,
    'metadata[country]' => 'PL',
];

if (!empty($email)) {
    $postFields['receipt_email'] = $email; // dla karty; inne metody mogą zignorować
}

$ch = curl_init('https://api.stripe.com/v1/payment_intents');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_USERPWD, $secret . ':');
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    http_response_code(500);
    echo json_encode([ 'error' => 'Błąd połączenia z Stripe: ' . $curlErr ]);
    exit;
}

$data = json_decode($response, true);
if ($httpCode >= 400) {
    $msg = isset($data['error']['message']) ? $data['error']['message'] : 'Błąd tworzenia PaymentIntent';
    http_response_code($httpCode);
    echo json_encode([ 'error' => $msg ]);
    exit;
}

echo json_encode([
    'client_secret' => $data['client_secret'] ?? null,
]);


