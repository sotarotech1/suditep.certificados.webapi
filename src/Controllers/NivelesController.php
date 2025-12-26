<?php

  require_once __DIR__ . "/../Models/Niveles.php";

  class NivelesController extends NivelesModel {

    public function findNiveles() {
      try {
        $nivel = $this->getNiveles();
        Flight::json([
          'status' => 'success',
          'data' => $nivel,
        ], 200);
      } catch (Exception $e) {
        Flight::json([
          'status' => 'error',
          'message' => $e->getMessage()
        ], 500);
      }
    }

    public function findNivelesMax() {
      try {
        $nivel = $this->getNivelesMax();
        Flight::json([
          'status' => 'success',
          'data' => $nivel,
        ], 200);
      } catch (Exception $e) {
        Flight::json([
          'status' => 'error',
          'message' => $e->getMessage()
        ], 500);
      }
    }

  }