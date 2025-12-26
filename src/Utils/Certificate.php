<?php

require_once __DIR__ . "/../Models/Temario.php";
require_once __DIR__ . "/../Models/User.php";
require_once __DIR__ . "/../Models/Convenio.php";

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use setasign\Fpdi\Tfpdf\Fpdi;

class PdfCustom extends Fpdi
{
    protected function getFontPath()
    {
        // Redirigimos el path de fuentes al de tu proyecto
        return realpath(__DIR__ . '/../fonts/') . DIRECTORY_SEPARATOR;
    }
}

class Certificate
{

  private string $template;
  private string $storagePdf;
  private string $storageQr;

  private $temarios;
  private $convenios;

  public function __construct()
  {
    $this->template = __DIR__ . "/../../storage/template/CERTIFICADO_FINAL_01.pdf";
    $this->storagePdf = __DIR__ . "/../../storage/certificados/";
    $this->storageQr = __DIR__ . "/../../storage/qrcodes/";
    $this->temarios = new Temario();
    $this->convenios = new Convenio();
  }

  /**
   * Summary of createCertificate
   * @param string $NAME
   * @param string $COURSE
   * @param string $DATE_START
   * @param string $DATE_END
   * @param string $CONVENIO
   * @param bool $SAVE
   * @return void
   */
  public function createCertificate(string $NAME, string $CURSO, string $DATE_START, string $DATE_END, string $CONVENIO, int $COURSEID, int $NOTA_FINAL, string $CODIGO_UNICO, int $DURACION, ?string $IMGPROFILE, bool $SAVE = false): array
  {
    $FILENAME = uniqid('CERT_') . '.pdf';
    $FILEPATH = $this->storagePdf . $FILENAME;
    $PUBLIC_URL = "https://suditep.edu.pe/certificados/storage/certificados/" . $FILENAME;

    // GENERATE QR
    $QR_PATH = $this->generateQr($PUBLIC_URL);

    // CREATE PDF
    $PDF = $this->generatePdf($NAME, $CURSO, $DATE_START, $DATE_END, $CONVENIO, $COURSEID, $NOTA_FINAL, $CODIGO_UNICO, $DURACION, $IMGPROFILE, $QR_PATH);

    if ($SAVE) {
      $PDF->Output($FILEPATH, 'F');

      // 🔽 Comprimir PDF final si es posible
      //$compressed = $this->compressPdf($FILEPATH);

      return [
        'path' => $FILEPATH,
        'filename' => basename($FILEPATH),
        'qr' => $QR_PATH,
        'url' => $PUBLIC_URL
      ];
    }

    return [
      'raw' => $PDF->Output('S'),
      'qr' => $QR_PATH,
      'url' => $PUBLIC_URL
    ];

  }

  /**
   * Summary of generateQr
   * @param string $URL
   * @throws \Exception
   * @return string
   */
  private function generateQr(string $URL)
  {
    $WRITER = new PngWriter();
    $QR_CODE = new QrCode(
      data: $URL,
      encoding: new Encoding('UTF-8'),
      errorCorrectionLevel: ErrorCorrectionLevel::Medium,
      size: 180,
      margin: 5,
      roundBlockSizeMode: RoundBlockSizeMode::Margin,
      foregroundColor: new Color(0, 0, 0),
      backgroundColor: new Color(255, 255, 255)
    );

    $RESULT = $WRITER->write($QR_CODE);
    $QR_PATH = $this->storageQr . uniqid('qr_') . '.png';
    $RESULT->saveToFile($QR_PATH);

    // 🔽 Comprimir QR
    $QR_PATH = $this->compressPng($QR_PATH, 8);

    if (!file_exists($QR_PATH) || filesize($QR_PATH) === 0) {
      throw new \Exception("El archivo QR no se generó correctamente en $QR_PATH");
    }

    return $QR_PATH;
  }

  /**
   * Summary of compressPng
   * @param string $file
   * @param int $compressionLevel
   * @return string
   */
  private function compressPng(string $file, int $compressionLevel = 9): string
  {
    if (!extension_loaded('gd'))
      return $file;

    $image = imagecreatefrompng($file);
    if (!$image)
      return $file;

    $compressedPath = str_replace('.png', '_min.png', $file);
    imagepng($image, $compressedPath, $compressionLevel);
    imagedestroy($image);

    if (file_exists($compressedPath) && filesize($compressedPath) < filesize($file)) {
      @unlink($file);
      return $compressedPath;
    }
    return $file;
  }

  /**
   * Summary of generatePdf
   * @param string $NAME
   * @param string $COURSE
   * @param string $DATE_START
   * @param string $DATE_END
   * @param int $CONVENIO
   * @param string $QR_PATH
   * @return Fpdi
   */
  private function generatePdf(string $NAME, string $CURSO, string $DATE_START, string $DATE_END, int $CONVENIO, int $COURSEID, int $NOTA_FINAL, string $CODIGO_UNICO, int $DURACION, ?string $IMGPROFILE, string $QR_PATH): Fpdi
  {
    $PDF = new PdfCustom();
    $PAGE_COUNT = $PDF->setSourceFile($this->template);

    for ($i = 1; $i <= $PAGE_COUNT; $i++) {
      $TPLIDX = $PDF->importPage($i);
      $size = $PDF->getTemplateSize($TPLIDX);

      $PDF->AddPage($size['orientation'], [$size['width'], $size['height']]);
      $PDF->useTemplate($TPLIDX);

      if ($i === 1) {
        $this->renderMainPage($PDF, $NAME, $CURSO, $DATE_START, $DATE_END, $DURACION, $CONVENIO, $IMGPROFILE, $size);
      }

      if ($i === 2) {
        $this->renderValidationPage($PDF, $NAME, $CURSO, $DATE_START, $DATE_END, $COURSEID, $CONVENIO, $NOTA_FINAL, $CODIGO_UNICO, $DURACION, $QR_PATH, $size);
      }
    }

    return $PDF;
  }

  /**
   * Summary of compressPdf
   * @param string $filePath
   * @return string
   */
  private function compressPdf(string $filePath): string
  {
    $compressedPath = str_replace('.pdf', '_min.pdf', $filePath);

    $gsAvailable = shell_exec("which gs");
    if (!$gsAvailable)
      return $filePath;

    $cmd = "gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/screen -dNOPAUSE -dQUIET -dBATCH -sOutputFile='$compressedPath' '$filePath'";
    shell_exec($cmd);

    if (file_exists($compressedPath) && filesize($compressedPath) < filesize($filePath)) {
      @unlink($filePath);
      return $compressedPath;
    }

    return $filePath;
  }

  /**
   * Summary of renderMainPage
   * @param setasign\Fpdi\Fpdi $PDF
   * @param string $NAME
   * @param string $COURSE
   * @param string $DATE_START
   * @param string $DATE_END
   * @param int $CONVENIO
   * @param array $SIZE
   * @return void
   */
  private function renderMainPage(Fpdi $PDF, string $NAME, string $CURSO, string $DATE_START, string $DATE_END, int $DURACION, int $CONVENIO, ?string $IMGPROFILE, array $SIZE)
  {
    $fontPathRegular = realpath(__DIR__ . 'Montserrat-Regular.ttf');
    $fontPathBold = realpath(__DIR__ . 'Montserrat-Bold.ttf');
    $fontPathItalic = realpath(__DIR__ . 'Montserrat-Italic.ttf');
    $fontPathMedium = realpath(__DIR__ . 'Montserrat-Medium.ttf');

    $PDF->AddFont('Montserrat-Regular', '', $fontPathRegular, true);
    $PDF->AddFont('Montserrat-Bold', '', $fontPathBold, true);
    $PDF->AddFont('Montserrat-Italic', '', $fontPathItalic, true);
    $PDF->AddFont('Montserrat-Medium', '', $fontPathMedium, true);

    $PDF->SetFont('Montserrat-Italic');
    $PDF->SetTextColor(8, 33, 54);

    $pageWidth = $SIZE['width'];
    $usableWidth = $pageWidth - 40;

    // --- Imagenes ---
    if ($CONVENIO === 0) {
      $PDF->Image(__DIR__ . "/../../storage/template/LOGO-SUDITEP-AZUL.png", 13, 2, 70);
      $PDF->Image(__DIR__ . "/../../storage/template/LOGO PERU SE CAPACITA.png", 85, 18, 70);
    } elseif ($CONVENIO === 1) {
      $convenio = $this->convenios->getConvenioById($CONVENIO);
      $logoPath = __DIR__ . "/../../" . $convenio['rutaLogoConvenio'];

      // Validar existencia antes de cargar
      if (file_exists($logoPath)) {
          $PDF->Image($logoPath, 13, 1, 50);
      }
      $PDF->Image(__DIR__ . "/../../storage/template/LOGO-SUDITEP-AZUL.png", 53, 5, 50);
    } elseif ($CONVENIO > 1) {
      $convenio = $this->convenios->getConvenioById($CONVENIO);
      $logoPath = __DIR__ . "/../../" . $convenio['rutaLogoConvenio'];

      // Validar existencia antes de cargar
      if (file_exists($logoPath)) {
          $PDF->Image($logoPath, 13, 8, 40);
      }
      $PDF->Image(__DIR__ . "/../../storage/template/LOGO-SUDITEP-AZUL.png", 53, 5, 50);
    }

    // --- Imagen de perfil (opcional) ---
    if (!empty($IMGPROFILE)) {
      // Si es una URL o una ruta válida
      if (filter_var($IMGPROFILE, FILTER_VALIDATE_URL) || file_exists($IMGPROFILE)) {
        try {
          $PDF->Image($IMGPROFILE, 37, 47, 30);
        } catch (Exception $imgEx) {
          error_log("No se pudo cargar la imagen de perfil: " . $imgEx->getMessage());
        }
      } else {
        error_log("Imagen de perfil no encontrada o inválida: " . $IMGPROFILE);
      }
    }



    // --- Nombre con ajuste dinámico ---
    $this->renderCenteredText($PDF, mb_strtoupper($NAME), $pageWidth, $usableWidth, 30, 89, true);

    // --- Curso ---
    $w = 230;
    $h = 8;
    $text = strtoupper($CURSO);
    $textWidth = $PDF->GetStringWidth($text);
    $pageWidth = $PDF->GetPageWidth();
    $lines = ceil($textWidth / $w);
    if ($lines > 1) {
      $fontSize = 20;
      $y = 110;
    } else {
      $fontSize = 25;
      $y = 115;
    }
    $x = ($pageWidth - $w) / 2;
    $PDF->SetXY($x, $y);
    $PDF->SetFontSize($fontSize);
    $PDF->MultiCell($w, $h, $text, '0', 'C', false);

    // --- Texto total abajo de nombre de curso
    $texto = "llevado a cabo desde el {$this->transformTextDate($DATE_START, "d")} al {$this->transformTextDate($DATE_END, "d")} de {$this->transformTextDate($DATE_END, "m")} del {$this->transformTextDate($DATE_END, "y")}, con una duración de {$DURACION} horas académicas.";
    $w = 160;
    $h = 5;
    $textBottom = mb_strtolower($texto);
    $y = "127.5";
    $fontSize = 11;

    $PDF->SetFont('Montserrat-Bold', '', $fontSize);
    $pageWidth = $PDF->GetPageWidth();
    $x = ($pageWidth - $w) / 2;

    $PDF->SetXY($x, $y);
    $PDF->SetFontSize($fontSize);
    $PDF->MultiCell($w, $h, $textBottom, '0', 'C', false);


    // Firma Anderson
    $PDF->Image(__DIR__ . "/../../storage/template/SELLO SUDITEP - GERENCIA GENERAL.png", 71, 140, 32);
    $PDF->Image(__DIR__ . "/../../storage/template/firma Anderson.png", 85, 147, 45);

    $PDF->Line(74, 169, 135, 169);

    $PDF->SetFont('Montserrat-Bold', '', 10);
    $PDF->SetTextColor(0, 0, 0);
    $PDF->SetXY(72, 170);
    $PDF->Write(6, strtoupper("Anderson A. Romero Ramirez"));

    $PDF->SetFont('Montserrat-Italic', '', 10);
    $PDF->SetXY(88, 174);
    $PDF->Write(6, "Gerente General");

    $PDF->SetXY("70.5", 178);
    $PDF->Write(6, "Centro Sudamericano de Formación");

    $PDF->SetXY(95, 182);
    $PDF->Write(6, "SUDITEP");

    if ($CONVENIO === 0) {

      // Firma Eva
      $PDF->Image(__DIR__ . "/../../storage/template/SELLO-SUDITEP---DIRECCION-ACADEMICA.png", 179, 140, 32);
      $PDF->Image(__DIR__ . "/../../storage/template/FIRMA EVA.png", 194, 147, 42);

      $PDF->Line(174, 169, 236, 169);

      $PDF->SetFont('Montserrat-Bold', '', 10);
      $PDF->SetTextColor(0, 0, 0);
      $PDF->SetXY(183, 170);
      $formattedCourse = "Eva M. Díaz García";
      $PDF->Write(6, strtoupper($formattedCourse));

      $PDF->SetFont('Montserrat-Italic', '', 10);
      $PDF->SetXY(183, 174);
      $PDF->Write(6, "Dirección Académica");

      $PDF->SetFont('Montserrat-Italic', '', 10);
      $PDF->SetXY(171, 178);
      $PDF->Write(6, "Centro Sudamericano de Formación");

      $PDF->SetFont('Montserrat-Italic', '', 10);
      $PDF->SetXY(195, 182);
      $PDF->Write(6, "SUDITEP");

    } elseif ($CONVENIO === 1) {

      // Firma Corlab
      $convenio = $this->convenios->getConvenioById($CONVENIO);
      $firmaPath = __DIR__ . "/../../" . $convenio['rutaFirmaConvenio'];
      if (file_exists($firmaPath)) {
          $PDF->Image($firmaPath, 150, 138, 120);
      }

    } elseif ($CONVENIO > 1) {
     
      // Firma Corlab
      $convenio = $this->convenios->getConvenioById($CONVENIO);
      $firmaPath = __DIR__ . "/../../" . $convenio['rutaFirmaConvenio'];
      if (file_exists($firmaPath)) {
          $PDF->Image($firmaPath, 160, 145, 80);
      }

    }

  }

  /**
   * Summary of renderValidationPage
   * @param setasign\Fpdi\Fpdi $PDF
   * @param string $NAME
   * @param string $COURSE
   * @param string $QR_PATH
   * @return void
   */
  private function renderValidationPage(Fpdi $PDF, string $NAME, string $COURSE, string $DATE_START, string $DATE_END, int $COURSEID, int $CONVENIO, int $NOTA_FINAL, string $CODIGO_UNICO, int $DURACION, string $QR_PATH, array $SIZE)
  {
    $pageWidth = $SIZE['width'];

    $PDF->SetFont('Montserrat-Bold', '', 18);

    if ($CONVENIO === 0) {
      $PDF->SetFontSize(16);
      $PDF->SetXY(105, 30);
      $PDF->SetTextColor(8, 33, 54);
      $formattedTexto = "CENTRO SUDAMERICANO DE FORMACIÓN - SUDITEP";
      $PDF->Write(6, strtoupper($formattedTexto));
    } elseif ($CONVENIO === 1) {
      $PDF->SetFontSize(18);
      $PDF->SetXY(198, 30);
      $PDF->SetTextColor(8, 33, 54);
      $convenio = $this->convenios->getConvenioById($CONVENIO);
      $formattedTexto = mb_convert_case($convenio['subTitleConvenio'], MB_CASE_TITLE, 'UTF-8');
      $PDF->Write(6, mb_convert_encoding(strtoupper($formattedTexto), 'ISO-8859-1', 'UTF-8'));
    } elseif ($CONVENIO > 1) {
      $PDF->SetFontSize(18);
      $PDF->SetXY(207, 30);
      $PDF->SetTextColor(8, 33, 54);
      $convenio = $this->convenios->getConvenioById($CONVENIO);
      $formattedTexto = mb_convert_case($convenio['subTitleConvenio'], MB_CASE_TITLE, 'UTF-8');
      $PDF->Write(6, mb_convert_encoding(strtoupper($formattedTexto), 'ISO-8859-1', 'UTF-8'));
    }

    $PDF->SetFont('Montserrat-Regular', '', 12);
    $PDF->SetTextColor(0, 0, 0);


    // ------ Curso ------
    $w = 100;
    $h = 6;
    $fontSize = 12;
    $x = 57;
    $y = 57.25;

    $labelCurso = "Curso:";
    $courseText = mb_convert_case($COURSE, MB_CASE_TITLE, 'UTF-8');

    $PDF->SetFont('Montserrat-Bold', '', $fontSize);
    $labelWidthCurso = $PDF->GetStringWidth($labelCurso . ' ');
    $wCurso = $w - $labelWidthCurso;

    $PDF->SetFont('Montserrat-Regular', '', $fontSize);
    $courseWidth = $PDF->GetStringWidth($courseText);
    $linesCurso = ceil($courseWidth / $wCurso); // ← detecta si ocupa más de una línea

    // --- Dibuja "Curso:" ---
    $PDF->SetXY($x, $y);
    $PDF->SetTextColor(8, 33, 54);
    $PDF->SetFont('Montserrat-Bold', '', $fontSize);
    $PDF->Cell($labelWidthCurso, $h, $labelCurso, 0, 0, 'L');

    // --- Dibuja el curso ---
    $PDF->SetFont('Montserrat-Regular', '', $fontSize);
    $cursoX = $x + $labelWidthCurso;
    $PDF->SetXY($cursoX, $y);
    $PDF->MultiCell($wCurso, $h, $courseText, 0, 'L', false);


    // ------ Duración ------
    $y += ($linesCurso > 1) ? ($h * $linesCurso + 2) : 8; // 🔹 dinámico según líneas
    $labelDuracion = "Duración:";
    $duracionText = "{$DURACION} horas académicas";

    $PDF->SetFont('Montserrat-Bold', '', $fontSize);
    $labelWidthDuracion = $PDF->GetStringWidth($labelDuracion . ' ');
    $wDuracion = $w - $labelWidthDuracion;

    $PDF->SetXY($x, $y);
    $PDF->Cell($labelWidthDuracion, $h, $labelDuracion, 0, 0, 'L');
    $PDF->SetFont('Montserrat-Regular', '', $fontSize);
    $PDF->SetXY($x + $labelWidthDuracion, $y);
    $PDF->MultiCell($wDuracion, $h, $duracionText, 0, 'L', false);


    // ------ Nombre ------
    $y += 7; // 🔹 separación fija entre duración y nombre
    $labelAlumno = "Alumno:";
    $alumnoText = ucwords(mb_strtolower($NAME));

    $PDF->SetFont('Montserrat-Bold', '', $fontSize);
    $labelWidthAlumno = $PDF->GetStringWidth($labelAlumno . ' ');
    $wAlumno = $w - $labelWidthAlumno;

    $PDF->SetXY($x, $y);
    $PDF->Cell($labelWidthAlumno, $h, $labelAlumno, 0, 0, 'L');
    $PDF->SetFont('Montserrat-Regular', '', $fontSize);
    $PDF->SetXY($x + $labelWidthAlumno, $y);
    $PDF->MultiCell($wAlumno, $h, $alumnoText, 0, 'L', false);


    // ------ Fechas dinámicas según nombre ------
    $nombreWidth = $PDF->GetStringWidth($alumnoText);
    $linesNombre = ceil($nombreWidth / $wAlumno);
    $y += ($linesNombre > 1) ? ($h * $linesNombre + 2) : 6.5;

    // Fecha inicio
    $labelFechaInicio = "Fecha de inicio:";
    $fechaInicioText = $this->transformTextDate($DATE_START, "A");
    $PDF->SetFont('Montserrat-Bold', '', $fontSize);
    $labelWidthFechaInicio = $PDF->GetStringWidth($labelFechaInicio . ' ');
    $wFechaInicio = $w - $labelWidthFechaInicio;
    $PDF->SetXY($x, $y);
    $PDF->Cell($labelWidthFechaInicio, $h, $labelFechaInicio, 0, 0, 'L');
    $PDF->SetFont('Montserrat-Regular', '', $fontSize);
    $PDF->SetXY($x + $labelWidthFechaInicio, $y);
    $PDF->MultiCell($wFechaInicio, $h, $fechaInicioText, 0, 'L', false);

    // Fecha fin
    $y += 6.5;
    $labelFechaFin = "Fecha de término:";
    $fechaFinText = $this->transformTextDate($DATE_END, "A");
    $PDF->SetFont('Montserrat-Bold', '', $fontSize);
    $labelWidthFechaFin = $PDF->GetStringWidth($labelFechaFin . ' ');
    $wFechaFin = $w - $labelWidthFechaFin;
    $PDF->SetXY($x, $y);
    $PDF->Cell($labelWidthFechaFin, $h, $labelFechaFin, 0, 0, 'L');
    $PDF->SetFont('Montserrat-Regular', '', $fontSize);
    $PDF->SetXY($x + $labelWidthFechaFin, $y);
    $PDF->MultiCell($wFechaFin, $h, $fechaFinText, 0, 'L', false);


    // === Datos del temario ===
    $model = $this->temarios->getTemarioByCurso($COURSEID);

    // === Coordenadas exactas del cuadro TEMARIO ===
    $cuadroX = 170;    // posición horizontal (ajusta si se corre a la derecha o izquierda)
    $cuadroY = 65;     // parte superior del cuadro
    $cuadroWidth = 95; // ancho total del cuadro
    $cuadroHeight = 60; // alto total del cuadro visible
    
    $fontSize = 13;
    
    $temarioCount = count($model);
    
    if ($temarioCount === 4) {
        $cuadroY = 65;  // más espacio entre líneas
        $fontSize = 13;
    } elseif ($temarioCount === 5) {
        $cuadroY = 72;   // ligeramente menor para que entre todo
        $fontSize = 11;
    }

    $lineHeight = 8;   // altura de cada línea
    $PDF->SetFont('Montserrat-Medium', '', $fontSize);
    $PDF->SetTextColor(0, 0, 0);
    
    // --- 1️⃣ Calcular la altura total del texto ---
    $totalHeight = 0;

    foreach ($model as $temario) {
        $texto = $temario['nombre_temario'];
        $textWidth = $PDF->GetStringWidth($texto);
        $lines = ceil($textWidth / $cuadroWidth);
        $totalHeight += $lines * $lineHeight;
    }

    // --- 2️⃣ Calcular Y inicial para centrar verticalmente dentro del cuadro ---
    $startY = $cuadroY + (($cuadroHeight - $totalHeight) / 2);

    // --- 3️⃣ Imprimir los temarios dentro del cuadro ---
    $i = 1;
    $y = $startY;

    foreach ($model as $temario) {
        $texto = $i++ . ".- " . $temario['nombre_temario'];

        // Posicionar el cursor dentro del cuadro
        $PDF->SetXY($cuadroX, $y);

        // MultiCell para ajustar el texto dentro del área del cuadro
        $PDF->MultiCell(
            $cuadroWidth,   // ancho del cuadro
            $lineHeight,    // alto por línea
            $texto,         // texto
            0,              // sin borde
            'L'             // alineación izquierda
        );

        // Calcular cuántas líneas usó realmente
        $textWidth = $PDF->GetStringWidth($texto);
        $lines = ceil($textWidth / $cuadroWidth);

        // Avanzar la posición Y para el siguiente ítem
        $y += $lines * $lineHeight;
    }

    // N° Convenio
    if ($CONVENIO > 0) {
      $PDF->SetFont('Montserrat-Bold', '', "8.5");
      $PDF->SetFontSize("8.5");
      $PDF->SetTextColor(30, 30, 30);

      $convenio = $this->convenios->getConvenioById($CONVENIO);
      $convenioNroconv = mb_strtoupper($convenio['nroConvenio'], 'UTF-8');
      $formattedTexto = "Convenio con Resolución N° {$convenioNroconv}";

      $cuadroX = 53;
      $cuadroWidth = 95;
      $posY = 152;

      $anchoTexto = $PDF->GetStringWidth($formattedTexto);
      $posX = $cuadroX + ($cuadroWidth - $anchoTexto) / 2;
      $PDF->SetXY($posX, $posY);
      $PDF->Write(6, mb_convert_encoding($formattedTexto, 'UTF-8', 'UTF-8'));
    }
    /*if ($CONVENIO > 0) {
      $PDF->SetFont('Montserrat-Bold', '', "8.5");
      $PDF->SetTextColor(30, 30, 30);

      // Obtenemos el convenio
      $convenio = $this->convenios->getConvenioById($CONVENIO);
      $nroConvenio = mb_strtoupper($convenio['nroConvenio'], 'UTF-8');
      $texto = "Convenio con Resolución N° {$nroConvenio}";

      // --- Convertir a ISO para que las tildes se muestren bien ---
      $texto = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $texto);

      // --- Coordenadas del cuadro donde centramos ---
      $cuadroX = 50;
      $cuadroWidth = 95;
      $posY = 152;

      // --- Calculamos el ancho del texto ---
      $anchoTexto = $PDF->GetStringWidth($texto);

      // --- Calculamos la X centrada dentro del cuadro ---
      $posX = $cuadroX + ($cuadroWidth - $anchoTexto) / 2;

      // --- Escribimos el texto ---
      $PDF->SetXY($posX, $posY);
      $PDF->Write(6, mb_convert_encoding($texto, 'ISO-8859-1'));
    }*/




    // Nota Final
    $PDF->SetFont('Montserrat-Bold', '', 16);
    $PDF->SetXY(236, "146.5");
    $PDF->Write(6, $NOTA_FINAL);

    // Codigo Unico
    $PDF->SetFont('Montserrat-Bold', '', 12);
    $PDF->SetXY(168, 182);
    $PDF->Write(6, $CODIGO_UNICO);

    // QR
    $PDF->Image(realpath($QR_PATH), 235, 166, 35, 35);
  }

  /**
   * Summary of renderCenteredText
   * @param setasign\Fpdi\Fpdi $PDF
   * @param string $text
   * @param float $pageWidth
   * @param float $usableWidth
   * @param int $fontSize
   * @param float $y
   * @return void
   */
  private function renderCenteredText($PDF, string $text, float $pageWidth, float $usableWidth, int $fontSize, float $y, bool $isTitle)
  {

    if ($isTitle) {
      $PDF->SetFont('Montserrat-Bold', '', $fontSize); 
    } else {
      $PDF->SetFont('Montserrat-Medium', '', $fontSize);
    }
    $encodedText = strtoupper($text);
    $textWidth = $PDF->GetStringWidth($text);

    while ($textWidth > $usableWidth && $fontSize > 8) {
      $fontSize -= 1;
      $PDF->SetFont('Montserrat-Bold', '', $fontSize);
      $textWidth = $PDF->GetStringWidth($encodedText);
    }

    $x = ($pageWidth - $textWidth) / 2;
    $PDF->SetXY($x, $y);
    $PDF->Write(10, $encodedText);
  }

  private function transformTextDate(string $value, string $info)
  {
    $date = new DateTime($value);

    $months = [
      1 => "enero",
      2 => "febrero",
      3 => "marzo",
      4 => "abril",
      5 => "mayo",
      6 => "junio",
      7 => "julio",
      8 => "agosto",
      9 => "setiembre",
      10 => "octubre",
      11 => "noviembre",
      12 => "diciembre"
    ];

    $day = $date->format("d");
    $month = $months[(int) $date->format("n")];
    $year = $date->format("Y");

    if ($info === "d") {
      $formated_date = $day;
    } elseif ($info === "m") {
      $formated_date = $month;
    } elseif ($info === "y") {
      $formated_date = $year;
    } elseif ($info === "A") {
      $formated_date = "{$day} de {$month} de {$year}";
    }

    return $formated_date;
  }

}