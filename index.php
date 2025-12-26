<?php

    require "./vendor/autoload.php";
    require "./bootstrap.php";

    Flight::before('start', function(&$params, &$output){
        // Cabeceras necesarias para CORS
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
    });


    // ------------------------------
    // RESPUESTA A SOLICITUDES OPTIONS (preflight)
    // ------------------------------
    Flight::route('OPTIONS *', function() {
        // Solo responde con los headers, sin contenido
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        Flight::halt(200); // Termina la solicitud con status 200
    });


    require "./src/Routes/api.php";