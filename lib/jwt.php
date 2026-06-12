<?php

function base64UrlEncode(string $data): string
{
    return rtrim(
        strtr(base64_encode($data), '+/', '-_'),
        '='
    );
}

function base64UrlDecode(string $data): string|false
{
    $base64 = strtr($data, '-_', '+/');

    $remainder = strlen($base64) % 4;

    if ($remainder !== 0) {
        $base64 .= str_repeat('=', 4 - $remainder);
    }

    return base64_decode($base64, true);
}

function createJwt(array $payload, string $secret): string
{
    $header = [
        'typ' => 'JWT',
        'alg' => 'HS256'
    ];

    $headerJson = json_encode($header);
    $payloadJson = json_encode($payload);

    if ($headerJson === false || $payloadJson === false) {
        throw new RuntimeException(
            'JWT-Daten konnten nicht als JSON codiert werden.'
        );
    }

    $headerEncoded = base64UrlEncode($headerJson);
    $payloadEncoded = base64UrlEncode($payloadJson);

    $signature = hash_hmac(
        'sha256',
        $headerEncoded . '.' . $payloadEncoded,
        $secret,
        true
    );

    $signatureEncoded = base64UrlEncode($signature);

    return $headerEncoded
        . '.'
        . $payloadEncoded
        . '.'
        . $signatureEncoded;
}

function verifyJwt(string $token, string $secret): array|false
{
    $parts = explode('.', $token);

    if (count($parts) !== 3) {
        return false;
    }

    [$headerEncoded,$payloadEncoded,$signatureEncoded] = $parts;

    $headerJson = base64UrlDecode($headerEncoded);
    $payloadJson = base64UrlDecode($payloadEncoded);
    $providedSignature = base64UrlDecode($signatureEncoded);

    if (
        $headerJson === false ||
        $payloadJson === false ||
        $providedSignature === false
    ) {
        return false;
    }

    $header = json_decode($headerJson, true);
    $payload = json_decode($payloadJson, true);

    if (!is_array($header) || !is_array($payload)) {
        return false;
    }

    if (
        ($header['typ'] ?? null) !== 'JWT' ||
        ($header['alg'] ?? null) !== 'HS256'
    ) {
        return false;
    }

    $expectedSignature = hash_hmac(
        'sha256',
        $headerEncoded . '.' . $payloadEncoded,
        $secret,
        true
    );

    if (!hash_equals($expectedSignature, $providedSignature)) {
        return false;
    }

    $currentTime = time();

    // Ablaufzeit
    if (
        !isset($payload['exp']) ||
        !is_numeric($payload['exp']) ||
        (int) $payload['exp'] <= $currentTime
    ) {
        return false;
    }

    // Token darf erst ab nbf verwendet werden
    if (
        isset($payload['nbf']) &&
        (
            !is_numeric($payload['nbf']) ||
            (int) $payload['nbf'] > $currentTime
        )
    ) {
        return false;
    }

    // Ausstellungszeit kontrollieren
    if (
        isset($payload['iat']) &&
        (
            !is_numeric($payload['iat']) ||
            (int) $payload['iat'] > $currentTime + 60
        )
    ) {
        return false;
    }

    return $payload;
}