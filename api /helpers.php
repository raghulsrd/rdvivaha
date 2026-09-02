<?php
declare(strict_types=1);

/** JSON response அனுப்பி script-ஐ முடிக்குது. */
function jsonResponse(int $status, array $payload): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

/** CORS headers + preflight handling. */
function applyCors(string $allowedOrigin): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if ($allowedOrigin === '*' || $origin === $allowedOrigin) {
        header('Access-Control-Allow-Origin: ' . ($allowedOrigin === '*' ? '*' : $origin));
    }
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Vary: Origin');

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

/**
 * Register form-ஓட input-ஐ validate பண்ணுது.
 * @return array{errors: array<string,string>, data: array<string,mixed>}
 */
function validateRegistration(array $body): array
{
    $errors = [];

    $customerName = trim((string) ($body['customerName'] ?? ''));
    $phone1       = trim((string) ($body['phone1'] ?? ''));
    $phone2       = trim((string) ($body['phone2'] ?? ''));
    $email        = strtolower(trim((string) ($body['email'] ?? '')));
    $address      = trim((string) ($body['address'] ?? ''));

    if (mb_strlen($customerName) < 3) {
        $errors['customerName'] = 'Enter your full name';
    }
    if (!preg_match('/^[0-9+\-\s]{10,20}$/', $phone1)) {
        $errors['phone1'] = 'Enter a valid phone number';
    }
    if ($phone2 !== '' && !preg_match('/^[0-9+\-\s]{10,20}$/', $phone2)) {
        $errors['phone2'] = 'Enter a valid alternate phone number';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 191) {
        $errors['email'] = 'Enter a valid email address';
    }
    if (mb_strlen($address) < 10) {
        $errors['address'] = 'Enter your complete address';
    }

    return [
        'errors' => $errors,
        'data'   => [
            'customerName' => $customerName,
            'phone1'       => $phone1,
            'phone2'       => $phone2 !== '' ? $phone2 : null,
            'email'        => $email,
            'address'      => $address,
        ],
    ];
}
/**
 * ஒரே IP-லிருந்து அதிக attempts-ஐ தடுக்குற simple file-based throttle.
 */
function throttle(string $key, int $maxAttempts = 10, int $windowSeconds = 900): bool
{
    $file = sys_get_temp_dir() . '/rdv_' . md5($key) . '.json';
    $now  = time();
    $hits = [];

    if (is_readable($file)) {
        $decoded = json_decode((string) file_get_contents($file), true);
        if (is_array($decoded)) {
            $hits = array_filter($decoded, static fn($t) => ($now - (int) $t) < $windowSeconds);
        }
    }

    if (count($hits) >= $maxAttempts) {
        return false;
    }

    $hits[] = $now;
    @file_put_contents($file, json_encode(array_values($hits)), LOCK_EX);

    return true;
}
