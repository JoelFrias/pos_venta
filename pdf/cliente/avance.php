<?php
require('../../libs/fpdf/fpdf.php');

// Clase personalizada para el recibo
class PDF_Receipt extends FPDF {
    function __construct() {
        // Crear PDF con medidas en milímetros
        parent::__construct('P', 'mm', array(76.2, 150)); // 3 pulgadas = 76.2 mm, altura ajustable
        $this->SetFont('Arial', '', 9);
    }
    
    // Función para el encabezado del recibo
    function Header() {
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 6, 'YSAPELLI', 0, 1, 'C');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 5, 'COMPROBANTE DE PAGO', 0, 1, 'C');
        $this->Ln(3);
    }
    
    // Función para agregar una línea de información
    function addInfoLine($label, $value) {
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(30, 5, $label, 0, 0);
        $this->SetFont('Arial', '', 8);
        $this->Cell(40, 5, $value, 0, 1);
    }
    
    // Función para agregar una línea separadora
    function addDashedLine() {
        $this->Cell(0, 0, '', 'T', 1);
        $this->Ln(2);
    }
}

// Creación del PDF
$pdf = new PDF_Receipt();
$pdf->SetAutoPageBreak(true, 5);
$pdf->AliasNbPages();
$pdf->AddPage();

// Get coneccion
require('../../models/conexion.php');

// Get basic informaction
$registro = isset($_GET['registro']) ? $_GET['registro'] : '';

$sql = "SELECT
            DATE_FORMAT(ch.fecha, '%d/%m/%Y %l:%i %p') AS fecha,
            CONCAT(c.nombre,' ',c.apellido) AS cliente,
            ch.monto AS total_pagado,
            ch.metodo AS metodo_pago,
            SUM(f.balance) AS nuevo_balance,
            CONCAT(e.nombre,' ',e.apellido) AS empleado
        FROM
            clientes_historialpagos ch
        JOIN clientes c ON
            ch.idCliente = c.id
        JOIN empleados e ON
            e.id = ch.idEmpleado
        JOIN facturas f ON
            f.idCliente = c.id
        WHERE
            f.estado = 'Pendiente'
        AND	ch.registro = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $registro);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $datos = $result->fetch_assoc();


    // Agregar información al recibo
    $pdf->addInfoLine('Fecha:', $datos['fecha']);
    $pdf->addInfoLine('Cliente:', $datos['cliente']);
    $pdf->Ln(2);

    $balance_anterior = $datos['nuevo_balance'] + $datos['total_pagado']; 

    $pdf->addDashedLine();
    $pdf->addInfoLine('Total Pagado:', '$ ' . number_format($datos['total_pagado']), 2);
    $pdf->addInfoLine('Balance Anterior:', '$ ' . number_format($balance_anterior), 2);
    $pdf->addInfoLine('Balance Actual:', '$ ' . number_format($datos['nuevo_balance']), 2);
    $pdf->addDashedLine();

    $pdf->Ln(2);
    $pdf->addInfoLine('Metodo de Pago:', $datos['metodo_pago']);
    $pdf->addInfoLine('Atendido por:', $datos['empleado']);

    $pdf->SetFont('Arial', '', 7);
    $pdf->MultiCell(0, 4, 'Gracias por su pago. Este comprobante es evidencia de su transaccion con YSAPELLI.', 0, 'C');
}

// Salida del PDF
$pdf->Output('comprobante_pago.pdf', 'I'); // 'I' muestra en el navegador, 'D' fuerza descarga
?>