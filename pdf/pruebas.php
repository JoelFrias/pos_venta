<?php
// Set proper header for PDF output
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="factura_ysapelli.pdf"');

require('../libs/fpdf/fpdf.php');

// Custom PDF class for narrow receipts (3 inches = 76.2mm)
class ReceiptPDF extends FPDF {
    // Add title to the PDF document properties
    function SetDocumentTitle($title) {
        $this->SetTitle($title);
    }
    
    function Header() {
        // Empty header
    }
    
    function Footer() {
        // Empty footer
    }
}

// Database connection
require('../models/conexion.php');

// Get data from database for info invoce
$sqlito = "SELECT * FROM infofactura";
$information = $conn->query($sqlito);
$info = $information->fetch_assoc();

// Get data from database
$invoice_id = isset($_GET['factura']) ? intval($_GET['factura']) : 0;

$sql = "SELECT
            f.fecha AS fecha,
            CONCAT(c.id, ' ', c.nombre, ' ', c.apellido) AS nombrec,
            f.numFactura AS numf,
            f.descuento AS descuentof,
            CONCAT(e.nombre, ' ', e.apellido) AS nombree,
            f.tipoFactura AS tipof,
            fm.metodo AS metodof,
            fm.monto AS montof,
            f.balance AS balancef
        FROM
            facturas f
        LEFT JOIN clientes c ON
            c.id = f.idCliente
        LEFT JOIN empleados e ON
            e.id = f.idEmpleado
        LEFT JOIN facturas_metodopago fm ON
            fm.numFactura = f.numFactura
        AND
            f.numFactura = $invoice_id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $invoice = $result->fetch_assoc();
    
    // Get invoice items
    $sql_items = "  SELECT
                        p.descripcion AS descripcionp,
                        fc.importe AS importep,
                        fc.cantidad AS cantidadp,
                        fc.precioVenta
                    FROM
                        facturas_detalles fc
                    JOIN productos p ON
                        p.id = fc.idProducto
                    WHERE
                        fc.numFactura = $invoice_id";
    $result_items = $conn->query($sql_items);
    
    // Create PDF object
    $pdf = new ReceiptPDF('P', 'mm', array(76.2, 297)); // 3 inches width (76.2mm)
    
    // Set PDF document properties (will appear in PDF reader's title bar)
    $pdf->SetDocumentTitle("YSAPELLI Factura #" . $invoice['numf']);
    $pdf->SetAuthor('YSAPELLI');
    $pdf->SetCreator('YSAPELLI Sistema de Facturación');
    
    $pdf->AddPage();
    $pdf->SetMargins(5, 10, 5);
    $pdf->SetFont('Arial', 'B', 12);
    
    // Store name and info
    $pdf->Cell(66, 6, '              ' . $info['name'], 0, 1, 'L');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(66, 4, $info['text1'], 0, 1, 'C');
    $pdf->Cell(66, 4, $info['text2'], 0, 1, 'C');
    
    // Date and invoice number
    $pdf->Cell(66, 4, date('d/m/Y h:i A', strtotime($invoice['fecha'])), 0, 1, 'R');
    $pdf->Ln(3);
    
    // Customer info
    $pdf->Cell(33, 4, 'Nombre Cliente:', 0, 0);
    $pdf->Cell(33, 4, $invoice['nombrec'], 0, 1);
    $pdf->Cell(33, 4, 'NCF:', 0, 0);
    $pdf->Cell(33, 4, '0', 0, 1);
    $pdf->Cell(33, 4, 'Tipo de Factura:', 0, 0);
    $pdf->Cell(33, 4, $invoice['tipof'], 0, 1);
    $pdf->Ln(3);

    // Numero de Factura
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(66, 3, '                   Factura #' . $invoice['numf'], 0, 1, 'L');
    $pdf->Ln(3);
    
    // Header for items
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(40, 4, 'DESCRIPCION', 0, 0);
    $pdf->Cell(13, 4, 'IMPORTE', 0, 1, 'R');
    $pdf->Line(5, $pdf->GetY(), 71.2, $pdf->GetY());
    $pdf->Ln(1);
    
    // Items
    $pdf->SetFont('Arial', '', 8);
    $subtotal = 0;
    
    if ($result_items->num_rows > 0) {
        while($item = $result_items->fetch_assoc()) {
            $pdf->Cell(40, 4, $item['descripcionp'], 0, 0);
            $pdf->Cell(13, 4, number_format($item['importep'], 2), 0, 1, 'R');
            $pdf->Cell(66, 4, $item['cantidadp'] . ' x ' . number_format($item['precioVenta'], 2), 0, 1);
            
            $subtotal += $item['importep'];
        }
    }
    
    $pdf->Ln(1);
    $pdf->Line(5, $pdf->GetY(), 71.2, $pdf->GetY());
    $pdf->Ln(1);
    
    // Totals
    $pdf->Cell(40, 4, '', 0, 0);
    $pdf->Cell(13, 4, 'Subt.:', 0, 0, 'L');
    $pdf->Cell(13, 4, number_format($subtotal, 2), 0, 1, 'R');
    
    $pdf->Cell(40, 4, '', 0, 0);
    $pdf->Cell(13, 4, 'Desc.:', 0, 0, 'L');
    $pdf->Cell(13, 4, number_format($invoice['descuentof'], 2), 0, 1, 'R');
    
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(40, 4, '', 0, 0);
    $pdf->Cell(13, 4, 'Total:', 0, 0, 'L');
    $pdf->Cell(13, 4, number_format(($subtotal - $invoice['descuentof']), 2), 0, 1, 'R');

    // Method Payment
    $pdf->SetFont('Arial', '', 8);

    $pdf->Cell(40, 4, '', 0, 0);
    $pdf->Cell(13, 4, 'Meto.:', 0, 0, 'L');
    $pdf->Cell(13, 4, $invoice['metodof'], 0, 1, 'R');

    $pdf->Cell(40, 4, '', 0, 0);
    $pdf->Cell(13, 4, 'Monto.:', 0, 0, 'L');
    $pdf->Cell(13, 4, number_format($invoice['montof'], 2), 0, 1, 'R');

    $pdf->Cell(40, 4, '', 0, 0);
    $pdf->Cell(13, 4, 'Pend.:', 0, 0, 'L');
    $pdf->Cell(13, 4, number_format($invoice['balancef'], 2), 0, 1, 'R');
    
    // Footer text
    $pdf->Ln(5);
    $pdf->SetFont('Arial', '', 7);
    $pdf->MultiCell(66, 3, $info['text3'], 0, 'C');
    
    $pdf->Ln(5);
    $pdf->Cell(33, 4, 'Le atendio:', 0, 0);
    $pdf->Cell(33, 4, $invoice['nombree'], 0, 1);
    
    // Output PDF directly to browser
    $pdf->Output('I', 'Factura_YSAPELLI_' . $invoice['numf'] . '.pdf');
} else {
    // If no invoice found, don't try to output PDF
    header('Content-Type: text/html'); // Reset header to HTML
    echo "<h2>Error: Factura no encontrada</h2>";
    echo "<p>La factura #$invoice_id no existe en la base de datos.</p>";
    echo "<p><a href='javascript:history.back()'>Volver</a></p>";
}

$conn->close();
?>