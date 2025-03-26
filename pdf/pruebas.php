<?php
require('../fpdf/fpdf.php');

class InvoiceReport extends FPDF
{
    function Header()
    {
        // Company Information
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'Mary Variedades', 0, 1, 'C');
        
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, 'Ave. Hatuey, frente a Pedro Racing, Santiago, Rep Dom.', 0, 1, 'C');
        $this->Cell(0, 6, 'Telefono: (829)789-4950   Instagram: @mary_varides', 0, 1, 'C');
        
        // Date and Invoice Number
        $this->SetY(30);
        $this->Cell(0, 6, '25/03/2025 12.27 AM', 0, 1, 'R');
        $this->Cell(0, 6, 'Factura Contado # 1650', 0, 1, 'R');
        
        // Customer Information
        $this->SetY(45);
        $this->Cell(0, 6, 'Nombre Cliente: Cliente al Contado', 0, 1);
        $this->Cell(0, 6, 'NCF: 0', 0, 1);
        
        // Table Header
        $this->SetY(60);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(120, 10, 'DESCRIPCION', 1, 0, 'C');
        $this->Cell(30, 10, 'ITBIS', 1, 0, 'C');
        $this->Cell(40, 10, 'IMPORTE', 1, 1, 'C');
        
        // Table Content
        $this->SetFont('Arial', '', 10);
        $this->Cell(120, 10, 'Base de Mujer', 1, 0);
        $this->Cell(30, 10, '0.00', 1, 0, 'R');
        $this->Cell(40, 10, '700.00', 1, 1, 'R');
    }
}

// Create new PDF document
$pdf = new InvoiceReport();
$pdf->AddPage();
$pdf->Output('invoice.pdf', 'F');
echo "PDF generated successfully!";
?>