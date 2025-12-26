<?php

    class Connection {
        private $host;
        private $user;
        private $password;
        private $dbname;
        private $charset;

        public $conn = null;

        public function __construct() {
            $this->host = $_ENV['DB_HOSTNAME'];
            $this->user = $_ENV['DB_USERNAME'];
            $this->password = $_ENV['DB_PASSWORD'];
            $this->dbname = $_ENV['DB_DATABASE'];
            $this->charset = 'utf8';
            // $this->host = 'localhost';
            // $this->user = 'suditep_certificado';
            // $this->password = 'hvdD(.zxapX(,!qz';
            // $this->dbname = 'suditep_sdtcertifcados';
        }

        public function connect() {
            try {
                $dsn = "mysql:host=$this->host;dbname=$this->dbname;charset=$this->charset";
                $this->conn = new PDO($dsn, $this->user, $this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                return $this->conn;
            } catch (Exception $e) {
                echo "Error de conexión";
            }
        }
    }