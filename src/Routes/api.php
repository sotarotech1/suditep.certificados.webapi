<?php
    
    require_once __DIR__ . "/../Controllers/AuthController.php";
    require_once __DIR__ . "/../Controllers/UserController.php";
    require_once __DIR__ . "/../Controllers/CertificadoController.php";
    require_once __DIR__ . "/../Controllers/PuntajeController.php";
    require_once __DIR__ . "/../Controllers/CursoController.php";
    require_once __DIR__ . "/../Controllers/RankingController.php";
    require_once __DIR__ . "/../Controllers/NivelesController.php";
    require_once __DIR__ . "/../Controllers/CursoAlumnoController.php";
    require_once __DIR__ . "/../Controllers/TemarioController.php";
    require_once __DIR__ . "/../Controllers/ConvenioController.php";

    require_once __DIR__ . "/../Middlewares/AuthMiddleware.php";

    Flight::route('GET /', function() {
        echo 'hola mundo';
    });

    /** Login */
    Flight::route('GET /profile', function() {
        authMiddleware(); // Verifica token

        $user = Flight::get('user'); // Obtenido desde el token

        Flight::json([
            'status' => 'success',
            'message' => 'Acceso autorizado',
            'user' => $user
        ]);
    });

    Flight::route('POST /auth', function() {
        $controller = new AuthController();
        $controller->signIn();
    });

    /** Users */
    Flight::route('GET /users', function() {
        $controller = new UserController();
        $controller->getAllUsers();
    });

    Flight::route('GET /users/paginate/@isActive/@idProfile/@limit/@page', function($isActive, $idProfile, $limit, $page) {
        $controller = new UserController();
        $controller->getAllUserPaginate($isActive, $idProfile, $limit, $page);
    });

    Flight::route('POST /users/search/@limit/@page', function($limit, $page) {
        $controller = new UserController();
        $controller->getSearchUsers($limit, $page);
    });

    Flight::route('GET /users/profile/@id/@active', function($id, $active) {
        $controller = new UserController();
        $controller->getAllUsersByPerfil($id, $active);
    });

    Flight::route('POST /users', function() {
        $controller = new UserController();
        $controller->createUsers();
    });

    Flight::route('GET /user/@id', function($id) {
        if (!is_numeric($id)) {
            Flight::json([
                'status' => 'error',
                'message' => 'ID inválido'
            ], 400);
            return;
        }

        $controller = new UserController();
        $controller->getUserById($id);
    });

    Flight::route('PUT /user/isActive/@id', function($id) {
        $controller = new UserController();
        $controller->updateUsersActive($id);
    });

    Flight::route('PUT /user/@id', function($id) {
        if (!is_numeric($id)) {
            Flight::json([
                'status' => 'error',
                'message' => 'ID inválido'
            ], 400);
            return;
        }

        $controller = new UserController();
        $controller->updateUsersId($id);
    });

    Flight::route('PUT /user/password/@id', function($id) {
        if (!is_numeric($id)) {
            Flight::json([
                'status' => 'error',
                'message' => 'ID inválido'
            ], 400);
            return;
        }

        $controller = new UserController();
        $controller->updatePassword($id);
    });

    Flight::route('DELETE /user/@id', function($id) {
        if (!is_numeric($id)) {
            Flight::json([
                'status' => 'error',
                'message' => 'ID inválido'
            ], 400);
            return;
        }

        $controller = new UserController();
        $controller->deleteUsersById($id);
    });

    /** Certificados */
    Flight::route('GET /certificado/@id', function($id) {
        if (!is_numeric($id)) {
            Flight::json([
                'status' => 'error',
                'message' => 'ID inválido'
            ], 400);
            return;
        }

        $controller = new CertificadoController();
        $controller->findCertificadoByUser($id);
    });

    Flight::route('POST /certificados/old', function() {
        $controller = new CertificadoController();
        $controller->createCertificadoOld();
    });

    Flight::route('POST /certificados', function() {
        $controller = new CertificadoController();
        $controller->createCertificado();
    });

    Flight::route('POST /certificados/preview', function() {
        $controller = new CertificadoController();
        $controller->previewCertificado();
    });

    Flight::route('GET /certificados/count/@id', function($id) {
        if (!is_numeric($id)) {
            Flight::json([
                'status' => 'error',
                'message' => 'ID inválido'
            ], 400);
            return;
        }

        $controller = new CertificadoController();
        $controller->findCountCertificadoByUser($id);
    });

    Flight::route('GET /certificados/last/@id', function($id) {
        $controller = new CertificadoController();
        $controller->findLastCertificadoId($id);
    });

    Flight::route('DELETE /certificados/@id', function($id) {
        $controller = new CertificadoController();
        $controller->removeCertificado($id);
    });

    Flight::route('POST /certificados/import-preview', function() {
        $controller = new CertificadoController();
        $controller->importPreview();
    });

    Flight::route('POST /certificados/import-save', function() {
        $controller = new CertificadoController();
        $controller->importSave();
    });

    /** Puntajes */
    Flight::route('GET /puntaje/@id', function($id) {
        $controller = new PuntajeController();
        $controller->findCountPuntajeByUser($id);
    });

    Flight::route('POST /puntajes', function() {
        $controller = new PuntajeController();
        $controller->createPoints();
    });

    Flight::route('DELETE /puntaje/cert/@cert', function($cert) {
        $controller = new PuntajeController();
        $controller->removePoints($cert);
    });

    /** Cursos */
    Flight::route('GET /cursos', function() {
        $controller = new CursoController();
        $controller->getCursos();
    });

    Flight::route('GET /cursos/paginate/@isActive/@limit/@page', function($isActive, $limit, $page) {
        $controller = new CursoController();
        $controller->getAllCoursePaginate($isActive, $limit, $page);
    });

    Flight::route('GET /cursos/@active', function($active) {
        $controller = new CursoController();
        $controller->getCursosByActive($active);
    });

    Flight::route('POST /cursos/search/@isActive/@limit/@page', function($isActive, $limit, $page) {
        $controller = new CursoController();
        $controller->getSearchCourse($isActive, $limit, $page);
    });

    Flight::route('GET /cursos/id/@id', function($id) {
        $controller = new CursoController();
        $controller->getCursosId($id);
    });

    Flight::route('POST /cursos', function() {
        $controller = new CursoController();
        $controller->createCursos();
    });

    Flight::route('PUT /curso/@id', function($id) {
        $controller = new CursoController();
        $controller->updateCoursesActive($id);
    });

    Flight::route('PUT /cursos/update/@id', function($id) {
        $controller = new CursoController();
        $controller->updateCourse($id);
    });

    /** Ranking */
    Flight::route('GET /ranking/users', function() {
        $controller = new RankingController();
        $controller->findRankingPoints();
    });

    /** Niveles */
    Flight::route('GET /niveles', function() {
        $controller = new NivelesController();
        $controller->findNiveles();
    });

    Flight::route('GET /niveles/max', function() {
        $controller = new NivelesController();
        $controller->findNivelesMax();
    });

    /** Cursos Alumnos */
    Flight::route('GET /cursos-alumnos/@curso', function($curso) {
        $controller = new CursoAlumnoController();
        $controller->getCursoAlumno($curso);
    });

    Flight::route('POST /cursos-alumnos', function() {
        $controller = new CursoAlumnoController();
        $controller->postCursoAlumno();
    });

    Flight::route('DELETE /cursos-alumnos/@userId/@cursoId', function($userId, $cursoId) {
        $controller = new CursoAlumnoController();
        $controller->deleteCursoAlumno($userId, $cursoId);
    });

    /** Temarios */
    Flight::route('GET /temarios', function() {
        $controller = new TemarioController();
        $controller->findTemarios();
    });

    Flight::route('POST /temarios', function() {
        $controller = new TemarioController();
        $controller->storeTemarios();
    });

    Flight::route('GET /temario/curso/@curso', function($curso) {
        $controller = new TemarioController();
        $controller->findTemarioByCurso($curso);
    });

    // CONVENIOS
    Flight::route('GET /convenios', function() {
        $controller = new ConvenioController();
        $controller->getAllConvenios();
    });

    Flight::route('GET /convenion/@id', function($id) {
        $controller = new ConvenioController();
        $controller->getConveniosById($id);
    });

    Flight::route('POST /convenios', function() {
        $controller = new ConvenioController();
        $controller->postConvenios();
    });

    Flight::start();