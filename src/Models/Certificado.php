<?php

require_once __DIR__ . "/../Db/Database.php";

class CertificadoModel extends Connection
{

    private $table = "certificados";

    public function __construct()
    {
        parent::__construct();
    }

    public function getCertificadoByUserId($id)
    {
        try {
            $conn = $this->connect();

            $sql = "SELECT C.idCert, C.concepto, C.route_pdf, CU.idCurso, CU.nombreCurso, CU.descripcionCurso, CU.precioCurso, CU.puntoCurso, C.createdAt, U.idUser FROM {$this->table} C INNER JOIN users U ON C.idUser=U.idUser INNER JOIN cursos CU ON C.idCurso=CU.idCurso WHERE U.idUser=?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id]);
            $cert = $stmt->fetchAll(PDO::FETCH_ASSOC);
            unset($cert['password']);
            return $cert;
        } catch (Exception $e) {
            error_log('Certificado error: ' . $e->getMessage());
            return null;
        }
    }

    public function getCertificadoById($id)
    {
        try {
            $conn = $this->connect();

            $sql = "SELECT * FROM {$this->table} WHERE idCert=?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id]);
            $cert = $stmt->fetch(PDO::FETCH_ASSOC);
            return $cert;
        } catch (Exception $e) {
            error_log('Certificado error: ' . $e->getMessage());
            return null;
        }
    }

    public function postCertificados($concepto, $nombreOriginal, $nombreCreado, $route_pdf, $idUser, $idCurso, $isBecado)
    {
        try {
            $conn = $this->connect();

            $sql = "INSERT INTO {$this->table} (concepto, nombreOriginal, nombreCreado, route_pdf, idUser, idCurso, is_becado) VALUES(?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $concepto,
                $nombreOriginal,
                $nombreCreado,
                $route_pdf,
                $idUser,
                $idCurso,
                $isBecado
            ]);
            $lastId = $conn->lastInsertId();
            return ['success' => true, 'id' => $lastId];
        } catch (Exception $e) {
            error_log('Certificado error:' . $e->getMessage());
            return null;
        }
    }

    public function countCertificadoByUser($id)
    {
        try {
            $conn = $this->connect();

            $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE idUser = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? (int)$result['total'] : 0;
        } catch (Exception $e) {
            error_log('Certificado count error: ' . $e->getMessage());
            return null;
        }
    }

    public function findLastRegister($id)
    {
        try {
            $conn = $this->connect();

            $sql = "SELECT concepto, route_pdf, createdAt FROM certificados WHERE idUser=? AND createdAt = ( SELECT MAX(createdAt) FROM certificados WHERE idUser=? )";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id, $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result;
        } catch (Exception $e) {
            error_log('Certificado error: ' . $e->getMessage());
            return null;
        }
    }


    public function deleteCertificados($id)
    {
        try {
            $conn = $this->connect();

            $sql = "DELETE FROM {$this->table} WHERE idCert=?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id]);
            return ['message' => 'El certificado ha sido eliminado'];
        } catch (Exception $e) {
            error_log("Delete certificado error: " . $e->getMessage());
            return null;
        }
    }
}
