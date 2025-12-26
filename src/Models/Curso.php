<?php
    
    require_once __DIR__ . "/../Db/Database.php";
    require_once __DIR__ . "/../Utils/Utils.php";

    class CursoModel extends Connection {

        private $table = "cursos";
        private $utils;

        public function __construct() {
            parent::__construct();
            $this->utils = new Utils();
        }

        public function getCourses() {
            try {
                $conn = $this->connect();

                $sql = "SELECT * FROM {$this->table} WHERE isActiveCurso=0 OR isActiveCurso=1";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log('Curso error: ' . $e->getMessage());
                return null;
            }
        }

        public function getCoursesByActive($active) {
            try {
                $conn =  $this->connect();

                $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE isActiveCurso=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([ $active ]);
                $curso = $stmt->fetchColumn();
                return (int)$curso;
            } catch (Exception $e) {
                error_log('Cursos count error: ' . $e->getMessage());
                return null;
            }
        }

        public function getCourseByActive($active) {
            try {
                $conn =  $this->connect();

                $sql = "SELECT * FROM {$this->table} WHERE isActiveCurso=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([ $active ]);
                $curso = $stmt->fetch(PDO::FETCH_ASSOC);
                return $curso;
            } catch (Exception $e) {
                error_log('Cursos count error: ' . $e->getMessage());
                return null;
            }
        }

        public function getCoursesPagination(int $isActive, int $limit, int $page) {
            try {
                $conn = $this->connect();
                $allCourse = $this->getCoursesByActive($isActive);

                $sql = "SELECT * FROM {$this->table} WHERE isActiveCurso=:isActive LIMIT :limit OFFSET :offset";

                $offset = ($page - 1) * $limit;

                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':isActive', $isActive, PDO::PARAM_STR);
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                return [
                    'result' => $result,
                    'total' => $allCourse
                ];
            } catch (Exception $e) {
                error_log('Cursos count error: ' . $e->getMessage());
                return null;
            }
        }

        public function countCourseSearch(string $termino, int $isActive) {
            try {
                $conn = $this->connect();

                $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE (itemCurso LIKE :termino OR nombreCurso LIKE :termino) AND isActiveCurso=:isActive";

                $searchTerm = "%".$termino."%";

                $countStmt = $conn->prepare($sql);
                $countStmt->bindParam(':termino', $searchTerm, PDO::PARAM_STR);
                $countStmt->bindParam(':isActive', $isActive, PDO::PARAM_INT);
                $countStmt->execute();
                $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
                return $total;
            } catch (Exception $e) {
                error_log('Error al buscar al alumno: ' . $e->getMessage());
                return null;
            }
        }

        public function searchCourse(string $termino, int $isActive, int $limit, int $page) {
            try {
                $conn = $this->connect();
                $total = $this->countCourseSearch($termino, $isActive);
                
                $sql = "SELECT * FROM {$this->table} WHERE (itemCurso LIKE :termino OR nombreCurso LIKE :termino) AND isActiveCurso=:isActive LIMIT :limit OFFSET :offset";

                $searchTerm = "%".$termino."%";
                $offset = ($page - 1) * $limit;

                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':termino', $searchTerm, PDO::PARAM_STR);
                $stmt->bindParam(':isActive', $isActive, PDO::PARAM_INT);
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                return [
                    'result' => $result,
                    'total' => (int)$total
                ];
            } catch (Exception $e) {
                error_log('Error al buscar el curso: ' . $e->getMessage());
                return null;
            }
        }

        public function getCoursesById($id) {
            try {
                $conn = $this->connect();

                $sql = "SELECT * FROM {$this->table} WHERE idCurso=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([ $id ]);
                $curso = $stmt->fetch(PDO::FETCH_ASSOC);
                return $curso;
            } catch (Exception $e) {
                error_log('Cursos id error: ' . $e->getMessage());
                return null;
            }
        }

        public function postCourses($nombre, $descripcion, $precio, $puntos, $isActive) {
            try {
                $conn = $this->connect();

                $numberCourse = $this->utils->obtenerNumeroCursoPorTipo($conn, $nombre, date("Y"));
                $itemCurso = $this->utils->generateCodeCourse($nombre, $numberCourse, date("Y"));

                $sql = "INSERT INTO {$this->table}(itemCurso, nombreCurso, descripcionCurso, precioCurso, puntoCurso, isActiveCurso) VALUES(?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([ $itemCurso, $nombre, $descripcion, $precio, $puntos, $isActive ]);
                return [ 'success' => true ];
            } catch (Exception $e) {
                error_log('Cursos insert error: ' . $e->getMessage());
                return null;
            }
        }

        public function updateCourseById($nombre, $descripcion, $precio, $idCurso) {
            try {
                $conn = $this->connect();

                $sql = "UPDATE {$this->table} SET nombreCurso=?, descripcionCurso=?, precioCurso=? WHERE idCurso=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([ $nombre, $descripcion, $precio, $idCurso ]);
                $affected = $stmt->rowCount();
                return [ 'success' => $affected > 0, 'rowsAffected' => $affected ];
            } catch (Exception $e) {
                error_log('Cursos update error: ' . $e->getMessage());
                return null;
            }
        }

        public function updateCourseByActive($isActive, $idCurso) {
            try {
                $conn = $this->connect();

                $sql = "UPDATE {$this->table} SET isActiveCurso=? WHERE idCurso=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([ $isActive, $idCurso ]);
                return [ 'success' => true ];
            } catch (Exception $e) {
                error_log('Cursos update error: ' . $e->getMessage());
                return null;
            }
        }

    }