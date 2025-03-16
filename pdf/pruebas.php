<?php
require('../fpdf/fpdf.php'); // Incluir la librería FPDF

// Crear el PDF con ancho 7" (177.8mm) y altura ajustable
$pdf = new FPDF('P', 'mm', [177.8, 250]);
$pdf->AddPage();

// Configuración de fuente y encabezado
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Factura', 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'Fecha: ' . date('Y-m-d'), 0, 1);

// Encabezados de la tabla
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(80, 10, 'Descripcion', 1);
$pdf->Cell(40, 10, 'Cantidad', 1);
$pdf->Cell(40, 10, 'Precio', 1, 1);

// Datos de prueba
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(80, 10, 'Producto A', 1);
$pdf->Cell(40, 10, '2', 1);
$pdf->Cell(40, 10, '$10.00', 1, 1);

$pdf->Cell(80, 10, 'Producto B', 1);
$pdf->Cell(40, 10, '1', 1);
$pdf->Cell(40, 10, '$15.00', 1, 1);

// Total
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(120, 10, 'Total', 1);
$pdf->Cell(40, 10, '$35.00', 1, 1, 'R');

// Salida del PDF
$pdf->Output();
?>
