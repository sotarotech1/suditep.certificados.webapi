<?php

  require_once __DIR__ . "/../Db/Database.php";

  class NivelesModel extends Connection {

    private $table = "niveles";

    public function __construct() {
      parent::__construct();
    }

    public function getNiveles() {
      try {
        $conn = $this->connect();

        $sql = "SELECT * FROM {$this->table}";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
      } catch (Exception $e) {
        error_log("Niveles error: " . $e->getMessage());
        return 0;
      }
    }

    public function getNivelesMax() {
      try {
        $conn = $this->connect();

        $sql = "SELECT MAX(nivelPuntaje) AS max_puntaje FROM {$this->table}";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? (int)$result['max_puntaje'] : 0;
      } catch (Exception $e) {
        error_log('Nivel Max error: ' . $e->getMessage());
        return 0;
      }
    }

  }