<?php

    require __DIR__ . "/../Models/CursoAlumno.php";

    class CursoAlumnoController extends CursoAlumnoModel {

        public function __construct() {
            parent::__construct();
        }

        public function getCursoAlumno($curso) {
            $model = $this->getCoursesByAlumno($curso);
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

        public function postCursoAlumno() {
            $data = Flight::request()->data;

            if (!isset($data['userId']) || !isset($data['cursoId'])) {
                Flight::json([
                    'status' => 'error',
                    'message' => 'Faltan datos obligatorios'
                ]);
                return;
            }

            $userId = (int)$data['userId'];
            $cursoId = (int)$data['cursoId'];

            $model = $this->postCoursesByAlumno($userId, $cursoId);
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

        public function deleteCursoAlumno($userId, $cursoId) {
            $model = $this->deleteCoursesAlumno($userId, $cursoId);
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

    }