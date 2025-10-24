<?php

namespace App\Core;

require_once __DIR__ . '/../lib/fpdf.php';

/**
 * PdfGenerator
 * 
 * Helper class for generating secure PDF tickets
 * Uses FPDF library with input sanitization
 */
class PdfGenerator {
    
    /**
     * Generate a ticket PDF
     * 
     * @param array $ticketData Ticket information
     * @return string PDF content as binary string
     */
    public static function generateTicketPdf(array $ticketData): string {
        // Create new PDF instance
        // P = Portrait, mm = millimeters, A4 = page size
        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        
        // Security: Sanitize all inputs before adding to PDF
        // This prevents any malicious content injection
        $sanitize = function($text) {
            // Remove any HTML/JS tags
            $text = strip_tags($text);
            // Convert special characters
            $text = htmlspecialchars_decode($text);
            // Remove any control characters
            $text = preg_replace('/[\x00-\x1F\x7F]/u', '', $text);
            // Limit length to prevent overflow
            $text = mb_substr($text, 0, 200);
            return $text;
        };
        
        // Header - Company/System Name
        $pdf->SetFont('Arial', 'B', 20);
        $pdf->Cell(0, 15, $sanitize('BILET SISTEMI'), 0, 1, 'C');
        $pdf->Ln(5);
        
        // Divider line
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(10);
        
        // Ticket Title
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, $sanitize('OTOBUS BILETI'), 0, 1, 'C');
        $pdf->Ln(5);
        
        // Ticket ID
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->Cell(0, 6, 'Bilet No: #' . $sanitize($ticketData['ticket_id']), 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(10);
        
        // Main content box
        $boxY = $pdf->GetY();
        $pdf->SetFillColor(245, 245, 245);
        $pdf->Rect(15, $boxY, 180, 100, 'F');
        $pdf->SetY($boxY + 5);
        
        // Firma Name
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(40, 8, '', 0, 0); // Left margin
        $pdf->Cell(0, 8, $sanitize($ticketData['firma_name']), 0, 1);
        $pdf->Ln(3);
        
        // Route information
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->Cell(40, 10, '', 0, 0); // Left margin
        $pdf->Cell(0, 10, 
            $sanitize($ticketData['from_city']) . ' -> ' . $sanitize($ticketData['to_city']), 
            0, 1
        );
        $pdf->Ln(5);
        
        // Date and Time
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(40, 7, '', 0, 0);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(30, 7, 'Tarih:', 0, 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 7, $sanitize($ticketData['date']), 0, 1);
        
        $pdf->Cell(40, 7, '', 0, 0);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(30, 7, 'Saat:', 0, 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 7, $sanitize($ticketData['time']), 0, 1);
        $pdf->Ln(3);
        
        // Seat Number - Highlighted
        $pdf->Cell(40, 7, '', 0, 0);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(30, 7, 'Koltuk No:', 0, 0);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetTextColor(220, 53, 69); // Red highlight
        $pdf->Cell(0, 7, $sanitize($ticketData['seat_number']), 0, 1);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(3);
        
        // Price
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(40, 7, '', 0, 0);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(30, 7, 'Ucret:', 0, 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 7, $sanitize($ticketData['price']) . ' TL', 0, 1);
        $pdf->Ln(3);
        
        // Passenger Name
        $pdf->Cell(40, 7, '', 0, 0);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(30, 7, 'Yolcu:', 0, 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 7, $sanitize($ticketData['passenger_name']), 0, 1);
        
        $pdf->Ln(15);
        
        // Divider line
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(5);
        
        // Status
        $statusText = $ticketData['status'] === 'active' ? 'AKTIF' : 'IPTAL EDILDI';
        $pdf->SetFont('Arial', 'B', 12);
        if ($ticketData['status'] === 'active') {
            $pdf->SetTextColor(40, 167, 69); // Green
        } else {
            $pdf->SetTextColor(220, 53, 69); // Red
        }
        $pdf->Cell(0, 10, 'Durum: ' . $sanitize($statusText), 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(5);
        
        // Purchase date
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->Cell(0, 6, 'Satin Alma Tarihi: ' . $sanitize($ticketData['purchase_date']), 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(10);
        
        // Footer information
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->MultiCell(0, 5, 
            "Bu bilet dijital olarak olusturulmustur.\n" .
            "Lutfen seyahat sirasinda kimliginizi yanınızda bulundurun.\n" .
            "Seferden 1 saat oncesine kadar iptal edebilirsiniz.",
            0, 'C'
        );
        
        // Return PDF as string
        return $pdf->Output('S'); // S = return as string
    }
    
    /**
     * Send PDF as download response
     * 
     * @param string $pdfContent PDF binary content
     * @param string $filename Filename for download
     * @return void
     */
    public static function sendPdfResponse(string $pdfContent, string $filename): void {
        // Security headers
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdfContent));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        // Prevent any HTML/JS execution
        header('X-Content-Type-Options: nosniff');
        
        echo $pdfContent;
        exit;
    }
}


