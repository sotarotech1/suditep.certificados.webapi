<?php
	
	require_once __DIR__ . "/../Models/Convenio.php";

	class ConvenioController extends Convenio
	{

		public function __construct()
		{
			parent::__construct();
		}

		public function getAllConvenios()
		{
			try {
                $model = $this->getConvenios();
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

		public function getConveniosById(int $id)
		{
			try {
				$model = $this->getConvenioById($id);
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

		public function postConvenios()
		{

			$data = Flight::request()->data;
    		$files = Flight::request()->files;

			try {
		        // --- Validar campos obligatorios ---
		        if (empty($data['nombre']) || empty($data['subtitle']) || empty($data['nroConvenio'])) {
		            Flight::json(['error' => 'Faltan campos obligatorios (nombre o subtitle o nroConvenio)']);
		            return;
		        }

		        // --- Definir ruta base donde guardar imágenes ---
		        $uploadDir = __DIR__ . '/../../storage/convenios/';
		        if (!file_exists($uploadDir)) {
		            mkdir($uploadDir, 0777, true);
		        }

		        // --- Inicializar rutas ---
		        $rutaLogo = null;
		        $rutaFirma = null;

		        // --- Guardar logo si existe ---
		        if (!empty($files['rutaLogo']['tmp_name'])) {
		            $logoName = uniqid('logo_') . '.' . pathinfo($files['rutaLogo']['name'], PATHINFO_EXTENSION);
		            $rutaLogo = 'storage/convenios/' . $logoName;
		            move_uploaded_file($files['rutaLogo']['tmp_name'], $uploadDir . $logoName);
		        }

		        // --- Guardar firma si existe ---
		        if (!empty($files['rutaFirma']['tmp_name'])) {
		            $firmaName = uniqid('firma_') . '.' . pathinfo($files['rutaFirma']['name'], PATHINFO_EXTENSION);
		            $rutaFirma = 'storage/convenios/' . $firmaName;
		            move_uploaded_file($files['rutaFirma']['tmp_name'], $uploadDir . $firmaName);
		        }

		        $body = [
		        	'nombre' => trim($data['nombre']),
		            'subtitle' => trim($data['subtitle']),
		            'nroConvenio' => trim($data['nroConvenio']),
		            'rutaLogo' => $rutaLogo,
		            'rutaFirma' => $rutaFirma
		        ];

		        $id = $this->createConvenio($body);

		        Flight::json([
		            'success' => $id['success'],
		            'message' => 'Convenio guardado correctamente'
		        ]);
		    } catch (Exception $e) {
		        Flight::json(['error' => $e->getMessage()]);
		    }
		}

	}