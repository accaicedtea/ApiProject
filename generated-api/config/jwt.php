<?php
// Semplice handler JWT; i valori tra __PLACEHOLDER__ saranno sostituiti dal builder.
class JWTHandler {
    private $secret_key = '786c5cfff3e1890354f809b521d597666029d91c8c63b594ea9c0e34913a957afde1ae9799324bad6fd29ab11c628997c7f3d90e191af69902598f1752379d9c';
    private $algorithm  = 'HS256'; // HS256, HS384, HS512
    private $ttl        = 86400;    // in secondi

    private function b64url($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public function generateToken(array $user) {
        $header = ['typ' => 'JWT', 'alg' => $this->algorithm];
        $now = time();
        $payload = [
            'sub' => (string)($user['id'] ?? ''),
            'email' => $user['email'] ?? '',
            'role' => $user['role'] ?? 'user',
            'name' => $user['name'] ?? '',
            'azienda_id' => (int)($user['azienda_id'] ?? 0),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $this->ttl,
        ];
        $h = $this->b64url(json_encode($header));
        $p = $this->b64url(json_encode($payload));
        $s = $this->b64url(hash_hmac('sha256', "$h.$p", $this->secret_key, true));
        return "$h.$p.$s";
    }

    public function validateToken(string $token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return false;
        [$h, $p, $s] = $parts;
        $expected = $this->b64url(hash_hmac('sha256', "$h.$p", $this->secret_key, true));
        if (!hash_equals($expected, $s)) return false;

        $payload = json_decode(base64_decode(strtr($p, '-_', '+/')), true);
        if (!is_array($payload)) return false;
        if (($payload['exp'] ?? 0) < time()) return false;
        return $payload;
    }

    public function getTokenFromHeader(): ?string {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        if (preg_match('/Bearer\s+(\S+)/', $auth, $m)) return $m[1];
        return null;
    }
}