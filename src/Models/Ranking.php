<?php

    require_once __DIR__ . "/../Db/Database.php";

    class RankingModel extends Connection {

        public function __construct() {
            parent::__construct();
        }

        public function rankingMorePoints() {
            try {
                $conn = $this->connect();

                $sql = "SELECT nombreUser, apellidoPat, SUM(puntaje) AS puntos FROM users US INNER JOIN puntajes P ON US.idUser=P.idUser WHERE US.idProfile=3 GROUP BY US.idUser ORDER BY puntos LIMIT 5";
                $stmt = $conn->prepare($sql);
                $stmt->execute([]);
                $cert = $stmt->fetchAll(PDO::FETCH_ASSOC);
                return $cert;
            } catch (Exception $e) {
                error_log('Ranking error: ' . $e->getMessage());
                return null;
            }
        }

    }