<?php

    require_once __DIR__ . "/../Db/Database.php";
    // require_once __DIR__ . "/../Utils/Utils.php";

    class CursoAlumnoModel extends Connection {

        private $table = "cursos_alumnos";
        // private $utils;

        public function __construct() {
            parent::__construct();
            // $this->utils = new Utils();
        }

        public function getCoursesByAlumno(int $curso) {
            try {
                $conn = $this->connect();

                $sql = "SELECT U.idUser, U.fullNameUser, U.dniUser, U.emailUser, U.phoneUser FROM {$this->table} CA LEFT JOIN users U ON CA.userId=U.idUser WHERE CA.cursoId=?";

                $stmt = $conn->prepare($sql);
                $stmt->execute([ $curso ]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log('CursoAlumno error: ' . $e->getMessage());
                return null;
            }
        }

        public function postCoursesByAlumno(int $userId, int $cursoId) {
            try {
                $conn = $this->connect();

                $sql = "INSERT INTO {$this->table} (userId, cursoId) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([ $userId, $cursoId ]);
                return [ 'success' => true ];
            } catch (Exception $e) {
                error_log('CursoAlumno error: ' . $e->getMessage());
                return null;
            }
        }

        public function deleteCoursesAlumno(int $userId, int $cursoId) {
            try {
                $conn = $this->connect();

                $sql = "DELETE FROM {$this->table} WHERE userId=? AND cursoId=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([ $userId, $cursoId ]);
                return [ 'success' => true ];
            } catch (Exception $e) {
                error_log('CursoAlumno error: ' . $e->getMessage());
                return null;
            }
        }

    }