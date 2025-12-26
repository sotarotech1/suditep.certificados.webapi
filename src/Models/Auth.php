<?php

    require_once __DIR__ . "/../Db/Database.php";

    use Firebase\JWT\JWT;
    use Firebase\JWT\Key;

    class AuthModel extends Connection {

        private $table = "users";
        private $secretKey = "tkcertifcadossutipep";

        public function __construct() {
            parent::__construct();
        }

        public function login($username, $password) {
            try {
                $conn = $this->connect();

                $sql = "SELECT * FROM {$this->table} WHERE username = ? AND isActiveUser=1";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password'])) {
                    unset($user['password']);
                    $payload = [
                        'iss' => 'suditep.edu.pe',
                        'aud' => 'suditep.edu.pe',
                        'iat' => time(),
                        'exp' => time() + (60*60),
                        'data' => [
                            'idUser' => $user['idUser'],
                            'nombreUser' => $user['nombreUser'],
                            'apellidoPat' => $user['apellidoPat'],
                            'apellidoMat' => $user['apellidoMat'],
                            'fullNameUser' => $user['fullNameUser'],
                            'dniUser' => $user['dniUser'],
                            'emailUser' => $user['emailUser'],
                            'phoneUser' => $user['phoneUser'],
                            'username' => $user['username'],
                            'perfil' => $user['idProfile']
                        ]
                    ];
                    $jwt = JWT::encode($payload, $this->secretKey, 'HS256');
                    $result = [
                        'token' => $jwt,
                        'user' => $user
                    ];
                    return $result;
                } else {
                    return [ "message" => "El usuario y/o contraseña son incorrectas."];
                }
            } catch (Exception $e) {
                error_log('Login error: ' . $e->getMessage());
                return null;
            }
        }

    }