<?php
namespace App\Helpers;

class Response {
    public static function json(array $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function ok(array $data = [], int $code = 200): void {
        self::json(array_merge(['status' => 'ok'], $data), $code);
    }

    public static function error(string $message, int $code = 400, array $extra = []): void {
        self::json(array_merge(['status' => 'error', 'message' => $message], $extra), $code);
    }
}
