<?php

require_once __DIR__ . "/../Models/Temario.php";

class TemarioController extends Temario
{

  public function __construct()
  {
    parent::__construct();
  }

  public function findTemarios()
  {
    try {
      $model = $this->getTemarios();
      Flight::json([
        'status' => 'success',
        'data' => $model
      ]);
    } catch (Exception $e) {
      Flight::json([
        'status' => 'error',
        'message' => $e->getMessage()
      ]);
    }
  }

  public function findTemarioByCurso($curso)
  {
    try {
      $model = $this->getTemarioByCurso($curso);
      Flight::json([
        'status' => 'success',
        'data' => $model
      ], 200);
    } catch (Exception $e) {
      Flight::json([
        'status' => 'error',
        'message' => $e->getMessage()
      ]);
    }
  }

  public function storeTemarios()
  {
    $data = Flight::request()->data->getData();

    $body = [
      'nombre' => $data['nombre'] ?? null,
      'desc'   => $data['desc'] ?? null,
      'curso'  => $data['curso'] ?? null,
      'activo' => $data['activo'] ?? 0
    ];

    try {
      $model = $this->saveTemarios($body);

      if (!$model['success']) {
        throw new Exception($model['message']);
      }

      // ✅ Respuesta exitosa
      Flight::json([
        'status' => 'success',
        'message' => $model['message'],
        'data' => ['id' => $model['id']]
      ]);
    } catch (Exception $e) {
      // ❌ Manejo de errores coherente
      Flight::json([
        'status' => 'error',
        'message' => 'Error al crear el temario: ' . $e->getMessage()
      ]);
    }
  }
}
