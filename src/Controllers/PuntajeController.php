<?php

    require_once __DIR__ . "/../Models/Puntaje.php";

    class PuntajeController extends PuntajeModel {

        public function findCountPuntajeByUser($id) {
            try {
                $count = $this->countPuntajesByUser($id);

                Flight::json([
                    'status' => 'success',
                    'data' => [
                        'suma' => $count
                    ]
                ], 200);

            } catch (Exception $e) {
                Flight::json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 500);
            }
        }

        public function createPoints()
        {
            $data = Flight::request()->data;

            try {
                // Validaciones básicas
                $required = ['puntaje', 'idUser', 'idCert'];
                foreach ($required as $field) {
                    if (!isset($data[$field]) || $data[$field] === '') {
                        Flight::json(['status' => 'error', 'message' => "Falta el campo requerido: $field"], 400);
                        return;
                    }
                }

                // Insertar puntaje
                $model = $this->postPuntajes($data['puntaje'], $data['idUser'], $data['idCert']);

                if ($model['success']) {
                    Flight::json([
                        'status' => 'success',
                        'message' => 'Los puntos se agregaron con éxito',
                        'idPuntaje' => $model['id']
                    ]);
                } else {
                    Flight::json([
                        'status' => 'error',
                        'message' => 'Error al insertar puntaje',
                        'detalle' => $model['error']
                    ], 500);
                }
            } catch (Exception $e) {
                error_log('Error en createPoints: ' . $e->getMessage());
                Flight::json([
                    'status' => 'error',
                    'message' => 'Error interno del servidor',
                    'detalle' => $e->getMessage()
                ], 500);
            }
        }


        public function removePoints($cert) {
            try {
                $model = $this->deletePuntaje($cert);
                if ($model) {
                    Flight::json([
                        'status' => 'success',
                        'message' => $model['message']
                    ]);
                }
            } catch (Exception $e) {
                Flight::json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
        }

    }