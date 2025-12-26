<?php

  class Utils {

    public function generateCodeCourse(string $nombreCurso, int $numeroCurso, int $anio){
      $palabras = explode(' ', strtoupper($nombreCurso));
      $iniciales = '';

      for ($i = 0; $i < min(2, count($palabras)); $i++) {
          $iniciales .= substr($palabras[$i], 0, 1);
      }

      $numeroCursoFormateado = str_pad($numeroCurso, 2, '0', STR_PAD_LEFT);
      $anioCorto = substr((string)$anio, -2);

      return $iniciales . $numeroCursoFormateado . $anioCorto;
    }

    public function obtenerNumeroCursoPorTipo(PDO $conn, string $nombreCurso, int $anio): int {
      $palabras = explode(' ', strtoupper($nombreCurso));
      $iniciales = '';
      for ($i = 0; $i < min(2, count($palabras)); $i++) {
          $iniciales .= substr($palabras[$i], 0, 1);
      }

      $anioCorto = substr((string)$anio, -2);

      $stmt = $conn->prepare("
          SELECT COUNT(*) + 1 as total 
          FROM cursos 
          WHERE itemCurso LIKE :prefijo
      ");
      $stmt->execute([
          ':prefijo' => $iniciales . '%' . $anioCorto // ejemplo: 'AA%25'
      ]);
      $row = $stmt->fetch();
      return (int)$row['total'];
    } 

  }