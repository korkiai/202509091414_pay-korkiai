<?php
// Endpoint do pobierania danych firmowych na podstawie NIP-u
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// CORS headers jeśli potrzebne
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metoda POST wymagana']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$nip = isset($input['nip']) ? trim($input['nip']) : '';

if (empty($nip)) {
    http_response_code(400);
    echo json_encode(['error' => 'NIP jest wymagany']);
    exit;
}

// Usuń wszystkie znaki oprócz cyfr z NIP-u
$cleanNip = preg_replace('/[^0-9]/', '', $nip);

if (strlen($cleanNip) !== 10) {
    http_response_code(400);
    echo json_encode(['error' => 'NIP musi mieć 10 cyfr']);
    exit;
}

// Funkcja walidacji NIP-u
function validateNIP($nip) {
    $weights = [6, 5, 7, 2, 3, 4, 5, 6, 7];
    $sum = 0;
    
    for ($i = 0; $i < 9; $i++) {
        $sum += intval($nip[$i]) * $weights[$i];
    }
    
    $checksum = $sum % 11;
    if ($checksum === 10) {
        return false;
    }
    
    return intval($nip[9]) === $checksum;
}

if (!validateNIP($cleanNip)) {
    http_response_code(400);
    echo json_encode(['error' => 'Nieprawidłowy NIP']);
    exit;
}

// Pobieranie danych z API GUS (Główny Urząd Statystyczny)
// UWAGA: To jest uproszczona implementacja. W produkcji powinieneś używać oficjalnego API GUS
// lub komercyjnych usług weryfikacji NIP-u

try {
    // Przykład zapytania do publicznego API (zastąp swoim kluczem API)
    $apiUrl = "https://wl-api.mf.gov.pl/api/search/nip/{$cleanNip}?date=" . date('Y-m-d');
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'header' => [
                'User-Agent: KorkiAI-Payment-System/1.0'
            ]
        ]
    ]);
    
    $response = @file_get_contents($apiUrl, false, $context);
    
    if ($response === false) {
        // Fallback - zwróć podstawowe dane jeśli API nie działa
        echo json_encode([
            'success' => true,
            'data' => [
                'name' => 'Firma (dane z NIP: ' . $nip . ')',
                'nip' => $nip,
                'workingAddress' => 'Adres nie został znaleziony',
                'vatStatus' => 'Sprawdź ręcznie w systemie',
                'regon' => '',
                'krs' => ''
            ]
        ]);
        exit;
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['result']['subject'])) {
        $subject = $data['result']['subject'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'name' => $subject['name'] ?? 'Nieznana nazwa',
                'nip' => $nip,
                'workingAddress' => $subject['workingAddress'] ?? $subject['residenceAddress'] ?? 'Adres nieznany',
                'residenceAddress' => $subject['residenceAddress'] ?? '',
                'vatStatus' => $subject['statusVat'] ?? 'Nieznany',
                'regon' => $subject['regon'] ?? '',
                'krs' => $subject['krs'] ?? ''
            ]
        ]);
    } else {
        // Nie znaleziono danych
        http_response_code(404);
        echo json_encode(['error' => 'Nie znaleziono danych dla podanego NIP-u']);
    }
    
} catch (Exception $e) {
    error_log('Błąd pobierania danych NIP: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Błąd serwera podczas pobierania danych']);
}
?>