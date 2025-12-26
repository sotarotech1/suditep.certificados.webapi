<?php

    require_once __DIR__ . "/../Models/User.php";

    class UserController extends UserModel {

        public function __construct() {
            parent::__construct();
        }

        public function getAllUsers() {
            try {
                $model = $this->getUsers();
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

        public function getAllUserPaginate($isActive, $idProfile, $limit, $page) {
            $model = $this->getUserPagination($isActive, $idProfile, $limit, $page);
            try {
                Flight::json([
                    'status' => 'success',
                    'data' => $model['result'],
                    'total' => $model['total'],
                    'params' => [
                        'active' => $isActive,
                        'profile' => $idProfile,
                        'limit' => $limit,
                        'page' => $page
                    ]
                ]);
            } catch (Exception $e) {
                Flight::json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
        }

        public function getAllUsersByPerfil($perfil, $isActive) {
            $model = $this->getUserByProfileActive($perfil, $isActive);
            try {
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

        public function getSearchUsers($limit, $page) {
            $data = Flight::request()->data;

            $termino = isset($data['termino']) && is_string($data['termino']) ? $data['termino'] : '';
            $isActive = $data['isActive'];
            try {
                $model = $this->searchUser($termino, $isActive, $limit, $page);
                Flight::json([
                    'status' => 'success',
                    'data' => $model['result'],
                    'total' => $model['total'],
                    'params' => [
                        'limit' => $limit,
                        'page' => $page
                    ]
                ]);
            } catch (Exception $e) {
                Flight::json([
                    'status' => 'error',
                    'message' => "Error al mostrar el alumno: {$e->getMessage()}"
                ]);
            }
        }

        public function getUserById($id) {
            $model = $this->findUserById($id);
            try {
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

        public function createUsers() {
            $data = Flight::request()->data;
            try {
                $model = $this->postUsers($data['nombreUser'], $data['apellidoPat'], $data['apellidoMat'], $data['dniUser'], $data['emailUser'], $data['phoneUser'], $data['username'], $data['password'], $data['idProfile'], $data['isActiveUser']);
                if ($model) {
                    Flight::json([
                        'status' => 'success',
                        'message' => 'El usuario ha sido creado'
                    ], 200);
                }
            } catch (Exception $e) {
                Flight::json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
        }

        public function updateUsersActive($idUser) {
            $data = Flight::request()->data;
            try {
                $model = $this->updateUsersByActive($data['isActiveUser'], $idUser);
                if ($model) {
                    Flight::json([
                        'status' => 'success',
                        'message' => 'El usuario ha sido actualizado'
                    ]);
                }
            } catch (Exception $e) {
                Flight::json([
                    'status' => "error",
                    'message' => "Error al activar/desactivar el usuario: {$e->getMessage()}"
                ]);
            }
        }

        public function updatePassword($idUser) {
            $data = Flight::request()->data;
            try {
                $model = $this->updatePasswordByIdUser($data['password'], $idUser);
                if ($model) {
                    Flight::json([
                        'status' => 'success',
                        'message' => 'La contraseña ha sido modificada'
                    ]);
                }
            } catch (Exception $e) {
                Flight::json([
                    'status' => "error",
                    'message' => "Error al modificar la contraseña: {$e->getMessage()}"
                ]);
            }
        }

        public function updateUsersId($id) {
            $data = Flight::request()->data;
            try {
                $model = $this->updateUsersById($data['nombreUser'], $data['apellidoPat'], $data['apellidoMat'], $data['dniUser'], $data['emailUser'], $data['phoneUser'], $data['username'], $id);
                if ($model) {
                    Flight::json([
                        'status' => 'success',
                        'message' => 'El usuario ha sido actualizado'
                    ]);
                }
            } catch (Exception $e) {
                Flight::json([
                    'status' => "error",
                    'message' => "Error al actualizar el usuario: {$e->getMessage()}"
                ]);
            }
        }

        public function deleteUsersById($id) {
            try {
                $model = $this->deleteUserById($id);
                if ($model) {
                    Flight::json([
                        'status' => 'success',
                        'message' => 'El usuario ha sido eliminado'
                    ]);
                }
            } catch (Exception $e) {
                Flight::json([
                    'status' => "error",
                    'message' => "Error al eliminar el usuario: {$e->getMessage()}"
                ]);
            }
        }

    }