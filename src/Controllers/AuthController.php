<?php

    require __DIR__ . "/../Models/Auth.php";

    class AuthController extends AuthModel {

        public function __construct() {
            parent::__construct();
        }

        public function signIn() {
            $username = Flight::request()->data['username'] ?? null;
            $password = Flight::request()->data['password'] ?? null;
            try {
                $model = $this->login($username, $password);
                if ($model) {
                    Flight::json([
                        'status' => 'success',
                        'token' => $model['token'],
                        'data' => $model['user']
                        // 'data' => $model
                    ], 200);
                } else {
                    Flight::json([
                        'status' => 'error',
                        'message' => $model['message']
                    ], 401);
                }
            } catch (Exception $e) {
                Flight::json([
                    'status' => 'error',
                    'message' => "Error al iniciar sesión"
                ], 500);
            }
        }

    }