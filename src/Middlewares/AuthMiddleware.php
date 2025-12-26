<?php

    use Firebase\JWT\JWT;
    use Firebase\JWT\Key;

    function authMiddleware() {
        $header = getallheaders();
        $authHeader = $header['Authorization'] ?? null;

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            Flight::json([
                'status' => 'error',
                'message' => 'Token no proporcionado o malformado'
            ], 401);
            exit;
        }

        $jwt = $matches[1];
        $secretKey = "tkcertifcadossutipep";

        try {
            $decoded = JWT::decode($jwt, new Key($secretKey, 'HS256'));

            Flight::set('user', $decoded->data);
        } catch (Exception $e) {
            Flight::json([
                'status' => 'error',
                'message' => 'Token inválido: ' . $e->getMessage()
            ], 401);
            exit;
        }
    }