<?php

require_once __DIR__ . "/../Db/Database.php";

class Temario extends Connection
{

  private $table = "temario_cursos";

  public function __construct()
  {
    parent::__construct();
  }

  public function getTemarios()
  {
    try {
      $conn = $this->connect();

      $sql = "SELECT TC.id_temario, TC.nombre_temario, TC.desc_temario, TC.activo, CU.itemCurso, CU.nombreCurso, CU.descripcionCurso, CU.isActiveCurso FROM {$this->table} TC INNER JOIN cursos CU ON TC.cursoId=CU.idCurso";
      $stmt = $conn->prepare($sql);
      $stmt->execute();
      $temario = $stmt->fetchAll(PDO::FETCH_ASSOC);
      return $temario;
    } catch (Exception $e) {
      error_log('Temario error: ' . $e->getMessage());
      return null;
    }
  }

  public function getTemarioByCurso($curso)
  {
    try {
      $conn = $this->connect();

      $sql = "SELECT * FROM {$this->table} WHERE cursoId=?";
      $stmt = $conn->prepare($sql);
      $stmt->execute([$curso]);
      $temario = $stmt->fetchAll(PDO::FETCH_ASSOC);
      return $temario;
    } catch (Exception $e) {
      error_log('Error temario: ' . $e->getMessage());
      return null;
    }
  }

  public function saveTemarios(array $body)
  {
    try {
      // ✅ Validación de campos requeridos
      if (!isset($body['nombre'], $body['desc'], $body['curso'], $body['activo'])) {
        throw new Exception('Datos incompletos para crear el temario.');
      }

      $conn = $this->connect();

      // ✅ Inserción con prepared statement seguro
      $sql = "INSERT INTO {$this->table} (nombre_temario, desc_temario, cursoId, activo)
                VALUES (:nombre, :desc, :curso, :activo)";

      $stmt = $conn->prepare($sql);

      $stmt->bindValue(':nombre', trim($body['nombre']), PDO::PARAM_STR);
      $stmt->bindValue(':desc', trim($body['desc']), PDO::PARAM_STR);
      $stmt->bindValue(':curso', (int) $body['curso'], PDO::PARAM_INT);
      $stmt->bindValue(':activo', (int) $body['activo'], PDO::PARAM_INT);

      $stmt->execute();

      // ✅ Obtener ID autogenerado
      $id = $conn->lastInsertId();

      return [
        'success' => true,
        'id' => (int)$id,
        'message' => 'Temario creado correctamente.'
      ];
    } catch (Exception $e) {
      error_log('Temarios insert error: ' . $e->getMessage());

      return [
        'success' => false,
        'message' => 'Error al guardar el temario: ' . $e->getMessage()
      ];
    }
  }
}
