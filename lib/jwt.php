<?php
function base64UrlEncode(string $data): string {
      return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
  }

  function createJwt(array $payload, string $secret): string {
      $header = ['typ' => 'JWT', 'alg' => 'HS256'];

      $headerEncoded  = base64UrlEncode(json_encode($header));
      $payloadEncoded = base64UrlEncode(json_encode($payload));

      // Signatur: HMAC-SHA256 über "header.payload"
      // 4. Argument true → binär (statt hex), wird dann base64-encoded
      $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true);
      $signatureEncoded = base64UrlEncode($signature);

      return "$headerEncoded.$payloadEncoded.$signatureEncoded";
  }
?>