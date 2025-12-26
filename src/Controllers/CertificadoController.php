<?php


require_once __DIR__ . "/../Models/Certificado.php";
require_once __DIR__ . "/../Models/User.php";
require_once __DIR__ . "/../Utils/Certificate.php";

class CertificadoController extends CertificadoModel
{
    private $user;
    private $cert;

    public function __construct()
    {
        parent::__construct();
        $this->user = new UserModel();
        $this->cert = new Certificate();
    }

    public function findCertificadoByUser($id)
    {
        try {
            $model = $this->getCertificadoByUserId($id);
            Flight::json([
                'status' => 'success',
                'data' => $model
            ], 200);
        } catch (Exception $e) {
            Flight::json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function createCertificadoOld()
    {
        $data = Flight::request()->data;
        $files = Flight::request()->files;

        try {
            // Validaciones básicas
            if (empty($data['idUser'])) {
                Flight::json(['error' => 'Falta el ID del usuario'], 400);
                return;
            }

            if (empty($data['concepto'])) {
                Flight::json(['error' => 'Falta el concepto del certificado'], 400);
                return;
            }

            if (!isset($files['pdf']) || $files['pdf']['error'] !== UPLOAD_ERR_OK) {
                Flight::json(['error' => 'Archivo PDF no enviado o con error'], 400);
                return;
            }

            $idUser = (int)$data['idUser'];
            $idCurso = (int)$data['idCurso'];
            $concepto = trim($data['concepto']);
            $isBecado = (int)$data['isBecado'] ?? 0;
            $file = $files['pdf'];

            // Validación de extensión
            $nombreOriginal = $file['name'];
            $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

            if ($extension !== 'pdf') {
                Flight::json(['error' => 'El archivo debe ser un PDF'], 400);
                return;
            }

            // Generar nombre seguro y carpeta destino
            $nombreCreado = uniqid('cert_', true) . '.pdf';
            $rutaCarpeta = __DIR__ . '/../../storage/certificados/';

            if (!is_dir($rutaCarpeta)) {
                mkdir($rutaCarpeta, 0777, true);
            }

            $rutaDestino = $rutaCarpeta . $nombreCreado;

            if (!move_uploaded_file($file['tmp_name'], $rutaDestino)) {
                Flight::json(['error' => 'No se pudo mover el archivo al servidor'], 500);
                return;
            }

            // Ruta relativa para la BD
            $rutaRelativa = 'storage/certificados/' . $nombreCreado;

            // Guardar en BD
            $model = $this->postCertificados($concepto, $nombreOriginal, $nombreCreado, $rutaRelativa, $idUser, $idCurso, $isBecado);
            if ($model) {
                Flight::json([
                    'success' => $model['success'],
                    'message' => 'Certificado subido correctamente',
                    'certificado' => [
                        'idCert' => $model['id'],
                        'concepto' => $concepto,
                        'curso' => $idCurso,
                        'nombreOriginal' => $nombreOriginal,
                        'nombreCreado' => $nombreCreado,
                        'ruta' => $rutaRelativa
                    ]
                ]);
            } else {
                Flight::json(['error' => 'No se pudo guardar el certificado en la base de datos'], 500);
            }

        } catch (Exception $e) {
            error_log('Error en /certificados: ' . $e->getMessage());
            Flight::json(['error' => 'Error interno del servidor'], 500);
        }
    }

    public function createCertificado()
    {
        $data = Flight::request()->data;
        $files = Flight::request()->files;

        try {
            // --- Validaciones básicas ---
            $requiredFields = ['idUser', 'idCurso', 'nombre', 'curso', 'fechaInicio', 'fechaFin'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    Flight::json(['error' => "Falta el campo requerido: $field"], 400);
                    return;
                }
            }

            // --- Extraer datos ---
            $duracion = (int) ($data['duracion'] ?? 0);
            $idUser = (int) $data['idUser'];
            $idCurso = (int) $data['idCurso'];
            $nombre = trim($data['nombre']);
            $curso = trim($data['curso']);
            $fechaInicio = $data['fechaInicio'];
            $fechaFin = $data['fechaFin'];
            $convenio = (int) ($data['convenio'] ?? 0);
            $concepto = trim($data['concepto'] ?? 'Certificado de participación');
            $isBecado = (int) ($data['isBecado'] ?? 0);
            $notaFinal = (int) ($data['notaFinal'] ?? 0);
            $codigoUnico = trim($data['codigoUnico'] ?? '');
            $imgProfile = $data['imgProfile'] ?? null;

            // --- Procesar imagen de perfil sin guardarla ---
            $imgPathForCert = null;
            $rutaImg = null; // solo para devolver en la respuesta

            if (!empty($imgProfile) && is_string($imgProfile)) {
                // Detectar tipo de imagen desde el base64
                if (preg_match('/^data:image\/(\w+);base64,/', $imgProfile, $matches)) {
                    $extension = strtolower($matches[1]); // Ejemplo: png, jpg, jpeg
                    $imgData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $imgProfile));

                    // Crear archivo temporal con extensión correcta
                    $tmpFile = tempnam(sys_get_temp_dir(), 'profile_') . '.' . $extension;
                    file_put_contents($tmpFile, $imgData);

                    $imgPathForCert = $tmpFile; // ruta temporal con extensión válida
                }
            }

            // --- Generar certificado ---
            require_once __DIR__ . '/../Utils/Certificate.php';
            $cert = new Certificate();

            $result = $cert->createCertificate(
                $nombre,
                $curso,
                $fechaInicio,
                $fechaFin,
                $convenio,
                $idCurso,
                $notaFinal,
                $codigoUnico,
                $duracion,
                $imgPathForCert, // imagen temporal con extensión
                true // guardar PDF en disco
            );

            // --- Guardar certificado en BD ---
            $nombreOriginal = $concepto . '.pdf';
            $nombreCreado = basename($result['path']);
            $rutaRelativa = 'storage/certificados/' . $nombreCreado;

            $model = $this->postCertificados(
                $concepto,
                $nombreOriginal,
                $nombreCreado,
                $rutaRelativa,
                $idUser,
                $idCurso,
                $isBecado
            );

            if ($model) {
                Flight::json([
                    'success' => true,
                    'message' => 'Certificado generado correctamente',
                    'certificado' => [
                        'idCert' => $model['id'],
                        'concepto' => $concepto,
                        'curso' => $idCurso,
                        'nombreOriginal' => $nombreOriginal,
                        'nombreCreado' => $nombreCreado,
                        'ruta' => $rutaRelativa,
                        'url' => $result['url'],
                        'qr' => $result['qr'],
                        'imagen' => $rutaImg // puede ser null, no se guarda
                    ]
                ]);
            } else {
                Flight::json(['error' => 'No se pudo guardar el certificado en la base de datos'], 500);
            }

            // --- Limpieza: eliminar imagen temporal ---
            if ($imgPathForCert && file_exists($imgPathForCert)) {
                unlink($imgPathForCert);
            }

        } catch (Exception $e) {
            error_log('Error en /certificados: ' . $e->getMessage());
            Flight::json([
                'error' => 'Error interno del servidor',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }


    public function previewCertificado()
    {
        $data = Flight::request()->data;
        $files = Flight::request()->files;

        try {
            // --- Validaciones básicas ---
            $requiredFields = ['idUser', 'idCurso', 'nombre', 'curso', 'fechaInicio', 'fechaFin'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    Flight::json(['error' => "Falta el campo requerido: $field"], 400);
                    return;
                }
            }

            // --- Extraer datos ---
            $duracion = (int) ($data['duracion'] ?? 0);
            $idUser = (int) ($data['idUser'] ?? 0);
            $idCurso = (int) ($data['idCurso'] ?? 0);
            $nombre = trim($data['nombre']);
            $curso = trim($data['curso']);
            $fechaInicio = $data['fechaInicio'];
            $fechaFin = $data['fechaFin'];
            $convenio = (int) ($data['convenio'] ?? 0);
            $concepto = trim($data['concepto'] ?? 'Certificado de participación');
            $isBecado = (int) ($data['isBecado'] ?? 0);
            $notaFinal = (int) ($data['notaFinal'] ?? 0);
            $codigoUnico = trim($data['codigoUnico'] ?? '');
            $imgProfile = $files['imgProfile'] ?? null;
            $imgTempPath = null;

            // --- Manejar imagen temporal (si existe) ---
            if (!empty($imgProfile) && isset($imgProfile['tmp_name']) && $imgProfile['error'] === UPLOAD_ERR_OK) {
                $tempDir = sys_get_temp_dir() . '/certificados_preview/';
                if (!file_exists($tempDir)) {
                    mkdir($tempDir, 0777, true);
                }

                $extension = pathinfo($imgProfile['name'], PATHINFO_EXTENSION);
                $nombreTemp = 'preview_' . uniqid() . '.' . $extension;
                $imgTempPath = $tempDir . $nombreTemp;

                move_uploaded_file($imgProfile['tmp_name'], $imgTempPath);
            }

            // --- Generar certificado temporal ---
            require_once __DIR__ . '/../Utils/Certificate.php';
            $cert = new Certificate();

            $result = $cert->createCertificate(
                $nombre,
                $curso,
                $fechaInicio,
                $fechaFin,
                $convenio,
                $idCurso,
                $notaFinal,
                $codigoUnico,
                $duracion,
                $imgTempPath ?: null, // ✅ opcional
                false // ❌ no guardar en disco
            );

            // --- Convertir PDF a Base64 ---
            $pdfBase64 = base64_encode($result['raw']);

            // --- Eliminar imagen temporal ---
            if ($imgTempPath && file_exists($imgTempPath)) {
                unlink($imgTempPath);
            }

            // --- Respuesta JSON ---
            Flight::json([
                'success' => true,
                'message' => 'Vista previa generada correctamente',
                'preview' => [
                    'pdfBase64' => $pdfBase64,
                    'mime' => 'application/pdf',
                    'qr' => $result['qr'] ?? null,
                    'url' => $result['url'] ?? null
                ]
            ]);
        } catch (Exception $e) {
            error_log('Error en /certificados/preview: ' . $e->getMessage());
            Flight::json([
                'error' => 'Error al generar vista previa',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }


    public function findCountCertificadoByUser($id)
    {
        try {
            $count = $this->countCertificadoByUser($id);

            Flight::json([
                'status' => 'success',
                'data' => [
                    'total' => $count
                ]
            ], 200);
        } catch (Exception $e) {
            Flight::json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function findLastCertificadoId($id)
    {
        try {
            $model = $this->findLastRegister($id);

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

    public function removeCertificado($id)
    {
        try {

            $certificado = $this->getCertificadoById($id);

            if (!$certificado) {
                Flight::json([
                    'status' => 'error',
                    'message' => 'Certificado no encontrado.'
                ]);
                return;
            }

            $filePath = __DIR__ . "/../../{$certificado['route_pdf']}";

            if (file_exists($filePath)) {
                unlink($filePath);
            } else {
                Flight::json([
                    'status' => 'error',
                    'message' => "El archivo no existe en el servidor"
                ]);
                return;
            }

            $model = $this->deleteCertificados($id);
            Flight::json([
                'status' => 'success',
                'message' => $model['message']
            ], 200);
        } catch (Exception $e) {
            Flight::json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function importPreview()
    {
        $files = Flight::request()->files;

        try {
            // --- Validar archivo ---
            if (!isset($files['file']) || $files['file']['error'] !== UPLOAD_ERR_OK) {
                Flight::json(['error' => 'Debe subir un archivo Excel válido'], 400);
                return;
            }

            $ext = strtolower(pathinfo($files['file']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['xlsx', 'xls'])) {
                Flight::json(['error' => 'El archivo debe ser Excel (.xlsx o .xls)'], 400);
                return;
            }

            $filePath = $files['file']['tmp_name'];

            // --- Cargar Excel ---
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            if (count($rows) < 2) {
                Flight::json(['error' => 'El archivo Excel no contiene datos'], 400);
                return;
            }

            // --- Validar encabezados esperados ---
            $expected = [
                'duracion',
                'iduser',
                'idcurso',
                'nombre',
                'curso',
                'fechainicio',
                'fechafin',
                'convenio',
                'concepto',
                'isbecado',
                'notafinal',
                'codigounico'
            ];

            // --- Columna opcional ---
            $optional = ['imgprofile'];

            // Normalizar encabezados de la primera fila
            $headers = array_map('strtolower', array_map('trim', array_values($rows[1])));

            foreach ($expected as $col) {
                if (!in_array($col, $headers)) {
                    Flight::json(['error' => "El archivo Excel debe contener la columna '$col'."], 400);
                    return;
                }
            }

            // --- Construir mapeo encabezado → letra de columna ---
            $allColumns = array_merge($expected, $optional);
            $colIndex = [];
            $colLetters = array_keys($rows[1]); // ['A','B','C',...]
            foreach ($headers as $i => $headerName) {
                $colIndex[$headerName] = $colLetters[$i] ?? null;
            }

            // --- Generar vista previa ---
            $cert = new Certificate();
            $previews = [];

            foreach ($rows as $index => $row) {
                if ($index === 1) continue; // Saltar encabezado
                if (empty(array_filter($row))) continue; // Saltar filas vacías

                try {
                    // Obtener valores
                    $duracion = (int) ($row[$colIndex['duracion']] ?? 0);
                    $idUser = (int) ($row[$colIndex['iduser']] ?? 0);
                    $idCurso = (int) ($row[$colIndex['idcurso']] ?? 0);
                    $nombre = trim($row[$colIndex['nombre']] ?? '');
                    $curso = trim($row[$colIndex['curso']] ?? '');
                    $fechaInicio = trim($row[$colIndex['fechainicio']] ?? '');
                    $fechaFin = trim($row[$colIndex['fechafin']] ?? '');
                    $convenio = (int) ($row[$colIndex['convenio']] ?? 0);
                    $concepto = trim($row[$colIndex['concepto']] ?? '');
                    $isBecado = (int) ($row[$colIndex['isbecado']] ?? 0);
                    $notaFinal = (int) ($row[$colIndex['notafinal']] ?? 0);
                    $codigoUnico = trim($row[$colIndex['codigounico']] ?? '');
                    $imgProfile = isset($colIndex['imgprofile'])
                        ? trim($row[$colIndex['imgprofile']] ?? '')
                        : null;

                    if (empty($nombre) || empty($curso))
                        continue;

                    // --- Generar certificado temporal ---
                    $result = $cert->createCertificate(
                        $nombre,
                        $curso,
                        $fechaInicio,
                        $fechaFin,
                        $convenio,
                        $idCurso,
                        $notaFinal,
                        $codigoUnico,
                        $duracion,
                        false // No guardar en disco, solo vista previa
                    );

                    $pdfBase64 = base64_encode($result['raw'] ?? '');

                    $previews[] = [
                        'duracion' => $duracion,
                        'idUser' => $idUser,
                        'idCurso' => $idCurso,
                        'nombre' => $nombre,
                        'curso' => $curso,
                        'fechaInicio' => $fechaInicio,
                        'fechaFin' => $fechaFin,
                        'convenio' => $convenio,
                        'concepto' => $concepto,
                        'isBecado' => $isBecado,
                        'notaFinal' => $notaFinal,
                        'codigoUnico' => $codigoUnico,
                        'imgProfile' => $imgProfile,
                        'pdfBase64' => $pdfBase64,
                        'qr' => $result['qr'] ?? null,
                        'mime' => 'application/pdf'
                    ];

                } catch (Exception $ex) {
                    $previews[] = [
                        'error' => "Error en la fila {$index}: " . $ex->getMessage()
                    ];
                }
            }

            Flight::json([
                'success' => true,
                'message' => 'Vista previa generada correctamente',
                'total' => count($previews),
                'data' => $previews
            ]);

        } catch (Exception $e) {
            error_log('Error en /certificados/import-preview: ' . $e->getMessage());
            Flight::json([
                'error' => 'Error al generar la vista previa',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    public function importSave()
{
    $files = Flight::request()->files;

    try {
        // --- Validar archivo ---
        if (!isset($files['file']) || $files['file']['error'] !== UPLOAD_ERR_OK) {
            Flight::json(['error' => 'Debe subir un archivo Excel válido'], 400);
            return;
        }

        $ext = strtolower(pathinfo($files['file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls'])) {
            Flight::json(['error' => 'El archivo debe ser Excel (.xlsx o .xls)'], 400);
            return;
        }

        $filePath = $files['file']['tmp_name'];

        // --- Cargar Excel ---
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) < 2) {
            Flight::json(['error' => 'El archivo Excel no contiene datos'], 400);
            return;
        }

        // --- Encabezados esperados ---
        $expected = [
            'duracion', 'iduser', 'idcurso', 'nombre', 'curso',
            'fechainicio', 'fechafin', 'convenio', 'concepto',
            'isbecado', 'notafinal', 'codigounico', 'imgprofile'
        ];

        $headers = array_map('strtolower', array_map('trim', array_values($rows[1])));

        foreach ($expected as $col) {
            if (!in_array($col, $headers)) {
                Flight::json(['error' => "El archivo Excel debe contener la columna '$col'."], 400);
                return;
            }
        }

        // --- Construir mapeo encabezado → letra de columna ---
        $colIndex = [];
        $colLetters = array_keys($rows[1]); // ['A','B','C',...]
        foreach ($headers as $i => $headerName) {
            $colIndex[$headerName] = $colLetters[$i] ?? null;
        }

        $cert = new Certificate();
        $successCount = 0;
        $errors = [];
        $results = [];

        foreach ($rows as $index => $row) {
            if ($index === 1) continue; // Saltar encabezado
            if (empty(array_filter($row))) continue; // Fila vacía

            try {
                // --- Leer datos ---
                $duracion     = (int) ($row[$colIndex['duracion']] ?? 0);
                $idUser       = (int) ($row[$colIndex['iduser']] ?? 0);
                $idCurso      = (int) ($row[$colIndex['idcurso']] ?? 0);
                $nombre       = trim($row[$colIndex['nombre']] ?? '');
                $curso        = trim($row[$colIndex['curso']] ?? '');
                $fechaInicio  = trim($row[$colIndex['fechainicio']] ?? '');
                $fechaFin     = trim($row[$colIndex['fechafin']] ?? '');
                $convenio     = (int) ($row[$colIndex['convenio']] ?? 0);
                $concepto     = trim($row[$colIndex['concepto']] ?? '');
                $isBecado     = (int) ($row[$colIndex['isbecado']] ?? 0);
                $notaFinal    = (int) ($row[$colIndex['notafinal']] ?? 0);
                $codigoUnico  = trim($row[$colIndex['codigounico']] ?? '');
                $imgProfile   = trim($row[$colIndex['imgprofile']] ?? '');

                if (empty($nombre) || empty($curso)) {
                    $errors[] = "Fila {$index}: Falta nombre o curso.";
                    continue;
                }

                $result = $cert->createCertificate(
                    $nombre,
                    $curso,
                    $fechaInicio,
                    $fechaFin,
                    $convenio,
                    $idCurso,
                    $notaFinal,
                    $codigoUnico,
                    $duracion,
                    $imgProfile,
                    true
                );

                $nombreOriginal = $concepto . '.pdf';
                $nombreCreado = basename($result['path']);
                $rutaRelativa = 'storage/certificados/' . $nombreCreado;

                $model = $this->postCertificados(
                    $concepto,
                    $nombreOriginal,
                    $nombreCreado,
                    $rutaRelativa,
                    $idUser,
                    $idCurso,
                    $isBecado
                );

                $results[] = [
                    'fila' => $index,
                    'nombre' => $nombre,
                    'curso' => $curso,
                    'certificado' => $result
                ];

                $successCount++;

            } catch (Exception $ex) {
                $errors[] = "Error en fila {$index}: " . $ex->getMessage();
            }
        }

        Flight::json([
            'success' => true,
            'message' => 'Certificados generados correctamente',
            'total' => count($rows) - 1,
            'creados' => $successCount,
            'errores' => count($errors),
            'detalleErrores' => $errors,
            'data' => $results
        ]);

    } catch (Exception $e) {
        error_log('Error en /certificados: ' . $e->getMessage());
        Flight::json([
            'error' => 'Error al guardar los certificados',
            'detalle' => $e->getMessage()
        ], 500);
    }
}


}
