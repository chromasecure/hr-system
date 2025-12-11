<?php
namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use DateTimeImmutable;

class JwtHelper {
    public function __construct(
        private string $secret,
        private int $ttlMinutes
    ) {}

    public function issueToken(array $payload): string {
        $now = new DateTimeImmutable();
        $exp = $now->modify("+{$this->ttlMinutes} minutes")->getTimestamp();
        $base = [
            'iat' => $now->getTimestamp(),
            'exp' => $exp,
        ];
        return JWT::encode(array_merge($base, $payload), $this->secret, 'HS256');
    }

    public function decode(string $token): object {
        return JWT::decode($token, new Key($this->secret, 'HS256'));
    }
}
