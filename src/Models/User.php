<?php

    require_once __DIR__ . "/../Db/Database.php";

    class UserModel extends Connection {

        private $table = "users";

        public function __construct() {
            parent::__construct();
        }

        public function getUsers() {
            try {
                $conn = $this->connect();

                $sql = "SELECT * FROM {$this->table}";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $user = $stmt->fetchAll(PDO::FETCH_ASSOC);
                unset($user['password']);
                return $user;
            } catch (Exception $e) {
                error_log('User error: ' . $e->getMessage());
                return null;
            }
        }

        public function getUsersByPerfil($perfil, $isActive) {
            try {
                $conn = $this->connect();

                $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE idProfile=? AND isActiveUser=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([ $perfil, $isActive ]);
                $total = $stmt->fetchColumn();
                return (int)$total;
            } catch (Exception $e) {
                error_log('User error: ' . $e->getMessage());
                return null;
            }
        }

        public function getUserByProfileActive($perfil, $isActive) {
            try {
                $conn = $this->connect();

                $sql = "SELECT * FROM {$this->table} WHERE idProfile=? AND isActiveUser=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([ $perfil, $isActive ]);
                $user = $stmt->fetchAll(PDO::FETCH_ASSOC);
                unset($user['password']);
                return $user;
            } catch (Exception $e) {
                error_log('User error: ' . $e->getMessage());
                return null;
            }
        }

        public function getUserPagination(int $isActive, int $idProfile, int $limit, int $page) {
            try {
                $conn = $this->connect();
                $allUser = $this->getUsersByPerfil($idProfile, $isActive);

                $sql = "SELECT * FROM {$this->table} WHERE isActiveUser=:isActive AND idProfile=:idProfile LIMIT :limit OFFSET :offset";
                
                $offset = ($page - 1) * $limit;

                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':isActive', $isActive, PDO::PARAM_STR);
                $stmt->bindParam(':idProfile', $idProfile, PDO::PARAM_INT);
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                return [
                    'result' => $result,
                    'total' => $allUser
                ];
            } catch (Exception $e) {
                error_log('User error: ' . $e->getMessage());
                return null;
            }
        }

        public function countUserSearch(string $termino, int $isActive) {
            try {
                $conn = $this->connect();

                $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE (nombreUser  LIKE :termino OR apellidoPat LIKE :termino OR apellidoMat LIKE :termino OR dniUser  LIKE :termino OR phoneUser  LIKE :termino) AND isActiveUser=:isActive AND idProfile=3";

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

        public function searchUser(string $termino, int $isActive, int $limit, int $page) {
            try {
                $conn = $this->connect();
                $total = $this->countUserSearch($termino, $isActive);

                $sql = "SELECT * FROM {$this->table} WHERE (nombreUser LIKE :termino OR apellidoPat LIKE :termino OR apellidoMat LIKE :termino OR dniUser LIKE :termino OR phoneUser LIKE :termino) AND isActiveUser = :isActive AND idProfile=3 LIMIT :limit OFFSET :offset";

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
                error_log('Error al buscar al alumno: ' . $e->getMessage());
                return null;
            }
        }

        public function findUserById($id) {
            try {
                $conn = $this->connect();

                $sql = "SELECT * FROM {$this->table} WHERE idUser=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([ $id ]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                unset($user['password']);
                return $user;
            } catch (Exception $e) {
                error_log('User error: ' . $e->getMessage());
                return null;
            }
        }

        public function postUsers($nombre, $apellidoPat, $apellidoMat, $dni, $email, $phone, $username, $password, $perfil, $isActive) {
            try {
                $conn = $this->connect();

                $checkSql = "SELECT COUNT(*) FROM {$this->table} WHERE username=?";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->execute([ $username ]);
                $count = $checkStmt->fetchColumn();

                if ($count > 0) {
                    return [ 'error' => 'El usuario ya esta registrado' ];
                }

                $fullName = "{$nombre} {$apellidoPat} {$apellidoMat}";
                $newPassword = password_hash($password, PASSWORD_BCRYPT);

                $sql = "INSERT INTO {$this->table} (nombreUser, apellidoPat, apellidoMat, fullNameUser, dniUser, emailUser, phoneUser, username, password, idProfile, isActiveUser) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    $nombre, $apellidoPat, $apellidoMat, $fullName, $dni, $email, $phone, $username, $newPassword, $perfil, $isActive
                ]);
                return [ 'success' => true ];
            } catch (Exception $e) {
                error_log('Insert User Error' . $e->getMessage());
                return null;
            }
        }

        public function updateUsersByActive($isActive, $idUser) {
            try {
                $conn = $this->connect();

                $sql = "UPDATE {$this->table} SET isActiveUser=? WHERE idUser=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([ $isActive, $idUser ]);
                return [ 'success' => true ];
            } catch (Exception $e) {
                error_log('Update user Error: ' . $e->getMessage());
                return null;
            }
        }

        public function updateUserProfileImage(int $idUser, string $rutaImg)
        {
            try {
                $conn = $this->connect();
                $sql = "UPDATE {$rutaImg} SET imgProfile = :rutaImg WHERE idUser = :id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':rutaImg', $rutaImg);
                $stmt->bindParam(':id', $idUser);
                $stmt->execute();
                return [ 'success' => true ];
            } catch (Exception $e) {
                error_log('Update user Error: ' . $e->getMessage());
                return null;
            }
        }

        public function updateUsersById($nombre, $apellidoPat, $apellidoMat, $dni, $email, $phone, $username, $id) {
            try {
                $conn = $this->connect();

                $fullName = "{$nombre} {$apellidoPat} {$apellidoMat}";

                $sql = "UPDATE {$this->table} SET nombreUser=?, apellidoPat=?, apellidoMat=?, fullNameUser=?, dniUser=?, emailUser=?, phoneUser=?, username=? WHERE idUser=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([ $nombre, $apellidoPat, $apellidoMat, $fullName, $dni, $email, $phone, $username, $id ]);
                return [ 'success' => true ];
            } catch (Exception $e) {
                error_log('Update user Error: ' . $e->getMessage());
                return null;
            }
        }

        public function updatePasswordByIdUser($password, $idUser) {
            try {
                $conn = $this->connect();

                $newPassword = password_hash($password, PASSWORD_BCRYPT);

                $sql = "UPDATE {$this->table} SET password=? WHERE idUser=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([ $newPassword, $idUser ]);
                return [ 'success' => true ];
            } catch (Exception $e) {
                error_log('Update password for user: ' . $e->getMessage());
                return null;
            }
        }

        public function deleteUserById($id) {
            try {
                $conn = $this->connect();

                $sql = "DELETE FROM {$this->table} WHERE idUser=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([ $id ]);
                return [ 'success' => true ];
            } catch (Exception $e) {
                error_log('Delete user Error: ' . $e->getMessage());
                return null;
            }
        }

    }