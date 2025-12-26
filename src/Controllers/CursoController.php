<?php

    require __DIR__ . "/../Models/Curso.php";

    class CursoController extends CursoModel {

        public function __construct() {
            parent::__construct();
        }

        public function getCursos() {
            try {
                $model = $this->getCourses();
                Flight::json([
                    'status' => 'success',
                    'data' => $model
                ]);
            } catch (Exception $e) {
                Flight::json([
                    'status' => 'error',
                    'message' => "Error al mostrar los cursos: {$e->getMessage()}"
                ]);
            }
        }

        public function getAllCoursePaginate($isActive, $limit, $page) {
            $model = $this->getCoursesPagination($isActive, $limit, $page);
            try {
                Flight::json([
                    'status' => 'success',
                    'data' => $model['result'],
                    'total' => $model['total'],
                    'params' => [
                        'active' => $isActive,
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

        public function getCursosByActive($active) {
            $model = $this->getCourseByActive($active);
            try {
                Flight::json([
                    'status' => 'success',
                    'data' => $model
                ]);
            } catch (Exception $e) {
                Flight::json([
                    'status' => "error",
                    'message' => "Error al mostrar los cursos: {$e->getMessage()}"
                ]);
            }
        }

        public function getSearchCourse($isActive, $limit, $page) {
            $data = Flight::request()->data;

            $termino = isset($data['termino']) && is_string($data['termino']) ? $data['termino'] : '';
            try {
                $model = $this->searchCourse($termino, $isActive, $limit, $page);
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
                    'message' => "Error al mostrar el curso: {$e->getMessage()}"
                ]);
            }
        }

        public function getCursosId($id) {
            try {
                $model = $this->getCoursesById($id);
                Flight::json([
                    'status' => 'success',
                    'data' => $model
                ]);
            } catch (Exception $e) {
                Flight::json([
                    'status' => 'error',
                    'message' => "Error al mostrar el curso: {$e->getMessage()}"
                ]);
            }
        }

        public function createCursos() {
            $data = Flight::request()->data;
            try {
                $model = $this->postCourses($data['nombreCurso'], $data['descripcionCurso'], $data['precioCurso'], $data['puntoCurso'], $data['isActiveCurso']);
                if ($model) {
                    Flight::json([
                        'status' => 'success',
                        'message' => 'El curso ha sido creado'
                    ]);
                }
            } catch (Exception $e) {
                Flight::json([
                    'status' => "error",
                    'message' => "Error al crear el curso: {$e->getMessage()}"
                ]);
            }
        }

        public function updateCourse($id) {
            $data = Flight::request()->data;

            if (!isset($data['nombreCurso'], $data['descripcionCurso'], $data['precioCurso'])) {
                return Flight::json([
                    'status' => 'error',
                    'message' => 'Datos incompletos para actualizar el curso.'
                ]);
            }

            try {
                $model = $this->updateCourseById($data['nombreCurso'], $data['descripcionCurso'], $data['precioCurso'], $id);
                if ($model && $model['success']) {
                    return Flight::json([
                        'status' => 'success',
                        'message' => [
                            'success' => true,
                            'rowsAffected' => $model['rowsAffected'] ?? 0
                        ]
                    ]);
                } else {
                    return Flight::json([
                        'status' => 'error',
                        'message' => 'No se pudo actualizar el curso o no hubo cambios.'
                    ]);
                }
            } catch (Exception $e) {
                return Flight::json([
                    'status' => 'error',
                    'message' => 'Error al actualizar el curso: ' . $e->getMessage()
                ]);
            }
        }

        public function updateCoursesActive($idCurso) {
            $data = Flight::request()->data;
            try {
                $model = $this->updateCourseByActive($data['isActiveCurso'], $idCurso);
                if ($model) {
                    Flight::json([
                        'status' => 'success',
                        'message' => 'El curso ha sido actualizado'
                    ]);
                }
            } catch (Exception $e) {
                Flight::json([
                    'status' => "error",
                    'message' => "Error al activar/desactivar el curso: {$e->getMessage()}"
                ]);
            }
        }

    } 