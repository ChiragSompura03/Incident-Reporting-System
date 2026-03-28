<?php

require_once __DIR__ . '/../config/config.php';

class JWTHelper {

    public static function generateAccessToken(array $payload): string {
        $payload['iat'] = time();
        $payload['exp'] = time() + JWT_ACCESS_EXPIRY;
        $payload['type'] = 'access';
        return self::encode($payload);
    }

    public static function generateRefreshToken(array $payload): string {
        $payload['iat']  = time();
        $payload['exp']  = time() + JWT_REFRESH_EXPIRY;
        $payload['type'] = 'refresh';
        $payload['jti']  = bin2hex(random_bytes(16));
        return self::encode($payload);
    }

    public static function validateToken(string $token): array {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new Exception('Invalid token structure');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $expectedSig = self::sign("$headerB64.$payloadB64");
        if (!hash_equals($expectedSig, $signatureB64)) {
            throw new Exception('Invalid token signature');
        }

        $payload = json_decode(self::base64UrlDecode($payloadB64), true);
        if (!$payload) {
            throw new Exception('Invalid token payload');
        }

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new Exception('Token has expired');
        }

        return $payload;
    }

    public static function getBearerToken(): ?string {
        $headers = null;

        if (isset($_SERVER['Authorization'])) {
            $headers = $_SERVER['Authorization'];
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (function_exists('apache_request_headers')) {
            $reqHeaders = apache_request_headers();
            if (isset($reqHeaders['Authorization'])) {
                $headers = $reqHeaders['Authorization'];
            }
        }

        if ($headers && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }

        if (!empty($_SESSION['access_token']) && !empty($_SESSION['user_id'])) {
            return $_SESSION['access_token'];
        }

        return null;
    }

    private static function encode(array $payload): string {
        $header = self::base64UrlEncode(json_encode([
            'alg' => JWT_ALGORITHM,
            'typ' => 'JWT'
        ]));

        $payloadEncoded = self::base64UrlEncode(json_encode($payload));
        $signature      = self::sign("$header.$payloadEncoded");

        return "$header.$payloadEncoded.$signature";
    }

    private static function sign(string $data): string {
        return self::base64UrlEncode(
            hash_hmac('sha256', $data, JWT_SECRET, true)
        );
    }

    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}