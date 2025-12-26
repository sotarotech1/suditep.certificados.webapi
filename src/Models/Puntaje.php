<?php

    require_once __DIR__ . "/../Db/Database.php";

    class PuntajeModel extends Connection {

        private $table = "puntajes";

        public function __construct() {
            parent::__construct();
        }

        public function countPuntajesByUser($id) {
            try {
                $conn = $this->connect();

                $sql = "SELECT SUM(puntaje) as total FROM {$this->table} WHERE idUser=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([ $id ]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                return $result ? (int)$result['total'] : 0;
            } catch (Exception $e) {
                error_log('Puntajes count error: ' . $e->getMessage());
                return 0;
            }
        }

        public function postPuntajes($puntaje, $idUser, $idCert)
        {
            try {
                $conn = $this->connect();

                $sql = "INSERT INTO {$this->table} (puntaje, idUser, idCert) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$puntaje, $idUser, $idCert]);

                // Retornar el ID insertado
                return [
                    'success' => true,
                    'id' => $conn->lastInsertId()
                ];
            } catch (Exception $e) {
                error_log('Insert points error: ' . $e->getMessage());
                return [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }


        public function deletePuntaje($cert) {
            try {
                $conn = $this->connect();

                $sql = "DELETE FROM {$this->table} WHERE idCert=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([ $cert ]);
                return [ 'message' => 'Se elimino los puntajes' ];
            } catch (Exception $e) {
                error_log("Delete points error: " . $e->getMessage());
                return null;
            }
        }

    }