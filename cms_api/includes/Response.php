<?php
/**
 * JSON response helper for REST API
 */
class Response {
    public static function json($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        echo json_encode($data, JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success($data = null, $message = 'OK', $code = 200) {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    public static function error($message = 'Error', $code = 400, $errors = null) {
        self::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $code);
    }

    public static function notFound($resource = 'Resource') {
        self::error($resource . ' not found', 404);
    }

    public static function methodNotAllowed() {
        self::error('Method not allowed', 405);
    }
}
