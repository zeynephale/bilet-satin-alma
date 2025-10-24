<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\CouponService;
use App\Core\Database;
use App\Core\PdfGenerator;
use App\Core\Response;
use App\Core\SecurityHeaders;
use App\Core\Session;
use App\Models\Ticket;
use App\Models\Trip;
use App\Models\User;
use App\Models\Coupon;

class TicketController {
    
    /**
     * List all tickets for the current user
     * Route: GET /me/tickets
     * Access: User role only
     */
    public function index(): void {
        Auth::requireRole('user');
        
        // Prevent caching of user-specific ticket list
        SecurityHeaders::noCache();
        
        // Get all tickets (active and cancelled) for the current user
        $tickets = Ticket::findByUser(Auth::id());
        
        require __DIR__ . '/../views/tickets/list.php';
    }
    
    /**
     * Show single ticket detail
     * Route: GET /tickets/{id}
     * Access: User role only, must own the ticket
     * IDOR Protection: ticket.user_id === currentUser.id
     */
    public function show(string $id): void {
        Auth::requireRole('user');
        
        // Prevent caching of user-specific ticket details
        SecurityHeaders::noCache();
        
        $ticketId = (int) $id;
        $ticket = Ticket::find($ticketId);
        
        // 404 if ticket doesn't exist
        if (!$ticket) {
            Session::flash('error', 'Bilet bulunamadı.');
            Response::notFound();
        }
        
        // IDOR Protection: ensure user owns the ticket
        // 403 Forbidden if trying to access someone else's ticket
        if ($ticket->user_id !== Auth::id()) {
            Session::flash('error', 'Bu bilete erişim yetkiniz yok.');
            http_response_code(403);
            Response::redirect('/me/tickets');
        }
        
        $trip = $ticket->getTrip();
        $firm = $trip ? \App\Models\Firm::find($trip->firma_id) : null;
        
        require __DIR__ . '/../views/tickets/show.php';
    }
    
    public function purchase(): void {
        // 1. Role check - must be 'user'
        Auth::requireRole('user');
        Csrf::validateOrFail();
        
        // 2. Input validation
        $tripId = (int) ($_POST['trip_id'] ?? 0);
        $seatNumber = (int) ($_POST['seat_number'] ?? 0);
        $couponCode = trim($_POST['coupon_code'] ?? '');
        
        // Validate integer boundaries
        if ($tripId <= 0 || $tripId > PHP_INT_MAX) {
            Session::flash('error', 'Geçersiz sefer ID.');
            Response::back();
        }
        
        if ($seatNumber <= 0 || $seatNumber > 1000) { // Reasonable max seat limit
            Session::flash('error', 'Geçersiz koltuk numarası.');
            Response::back();
        }
        
        try {
            // Begin transaction with IMMEDIATE lock
            $transactionStarted = Database::beginTransaction();
            if (!$transactionStarted) {
                throw new \Exception('Transaction başlatılamadı. Lütfen tekrar deneyin.');
            }
            
            $trip = Trip::find($tripId);
            if (!$trip) {
                throw new \Exception('Sefer bulunamadı.');
            }
            
            // Validate seat number is within trip's seat range
            if ($seatNumber < 1 || $seatNumber > $trip->seats) {
                throw new \Exception('Geçersiz koltuk numarası. Bu seferde 1-' . $trip->seats . ' arası koltuklar bulunmaktadır.');
            }
            
            // 3. Seat availability check with SQL (server-side validation)
            // Check if seat is already occupied (status='active')
            $db = Database::getInstance();
            $stmt = $db->prepare(
                "SELECT COUNT(*) as count FROM tickets 
                 WHERE trip_id = :trip_id AND seat_number = :seat_number AND status = 'active'"
            );
            $stmt->execute([
                'trip_id' => $tripId,
                'seat_number' => $seatNumber
            ]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                // 409 Conflict - seat already taken
                http_response_code(409);
                throw new \Exception('Bu koltuk dolu. Lütfen başka bir koltuk seçin.');
            }
            
            $user = Auth::user();
            $price = $trip->price;
            $discount = 0;
            $couponInfo = null;
            
            // 4. Optional coupon validation and application
            // Using CouponService for thread-safe atomic operations
            if (!empty($couponCode)) {
                // This method:
                // - Locks the coupon row (SELECT FOR UPDATE)
                // - Validates expiry_date, usage_limit, scope (global/firma)
                // - Atomically decrements usage_limit
                // - Returns discount info
                // - Throws Exception on any validation failure
                $couponInfo = CouponService::validateAndApplyCoupon(
                    $db,
                    $couponCode,
                    $trip->id,
                    $trip->firma_id,
                    $user->id
                );
                
                // Calculate discount amount from price
                $discount = ($price * $couponInfo['discount_percent']) / 100;
            }
            
            $finalPrice = max(0, $price - $discount);
            
            // 5. User credit check
            if ($user->credit < $finalPrice) {
                // 402 Payment Required - insufficient funds
                http_response_code(402);
                throw new \Exception(
                    'Yetersiz bakiye. Gerekli: ' . number_format($finalPrice, 2) . ' TL, ' .
                    'Bakiyeniz: ' . number_format($user->credit, 2) . ' TL'
                );
            }
            
            // 6. Perform the transaction operations
            
            // a) Deduct credit from user
            $stmt = $db->prepare(
                "UPDATE users SET credit = credit - :amount WHERE id = :id AND credit >= :amount"
            );
            $creditDeducted = $stmt->execute([
                'amount' => $finalPrice,
                'id' => $user->id
            ]);
            
            if (!$creditDeducted || $stmt->rowCount() === 0) {
                throw new \Exception('Kredi düşme işlemi başarısız oldu.');
            }
            
            // Update user object
            $user->credit -= $finalPrice;
            
            // b) Coupon already decremented by CouponService (atomic operation)
            // No additional coupon update needed here
            
            // c) Create ticket with status='active'
            $ticket = Ticket::create([
                'user_id' => $user->id,
                'trip_id' => $trip->id,
                'seat_number' => $seatNumber
            ]);
            
            if (!$ticket || !$ticket->id) {
                throw new \Exception('Bilet oluşturulamadı.');
            }
            
            // 7. Commit transaction
            $commitResult = Database::commit();
            
            if (!$commitResult) {
                throw new \Exception('Transaction commit başarısız oldu.');
            }
            
            // Verify ticket was actually saved
            $verifyTicket = Ticket::find($ticket->id);
            if (!$verifyTicket) {
                error_log("Ticket ID {$ticket->id} created but not found after commit!");
                throw new \Exception('Bilet kaydedilemedi. Lütfen tekrar deneyin.');
            }
            
            $successMessage = 'Biletiniz başarıyla satın alındı!';
            if ($discount > 0 && $couponInfo) {
                $successMessage .= ' Kupon (' . htmlspecialchars($couponInfo['code'], ENT_QUOTES, 'UTF-8') . ') uygulandı: -' . number_format($discount, 2) . ' TL';
            }
            $successMessage .= ' Ödenen: ' . number_format($finalPrice, 2) . ' TL';
            
            Session::flash('success', $successMessage);
            Response::redirect('/tickets/' . $ticket->id);
            
        } catch (\Exception $e) {
            // Rollback on any error
            $db = Database::getInstance();
            if ($db->inTransaction()) {
                Database::rollback();
            }
            Session::flash('error', $e->getMessage());
            Response::back();
        }
    }
    
    public function cancel(string $id): void {
        Auth::requireRole('user');
        Csrf::validateOrFail();
        
        $ticket = Ticket::find((int) $id);
        
        if (!$ticket) {
            Response::notFound();
        }
        
        // IDOR Protection
        if ($ticket->user_id !== Auth::id()) {
            Response::forbidden();
        }
        
        if ($ticket->status !== 'active') {
            Session::flash('error', 'Bu bilet zaten iptal edilmiş.');
            Response::back();
        }
        
        if (!$ticket->canBeCancelled()) {
            Session::flash('error', 'Bilet iptal edilemez. İptal için seferden en az 1 saat önce işlem yapmalısınız.');
            Response::back();
        }
        
        try {
            Database::beginTransaction();
            
            $trip = $ticket->getTrip();
            $user = Auth::user();
            
            // Cancel ticket
            if (!$ticket->cancel()) {
                throw new \Exception('Bilet iptal edilemedi.');
            }
            
            // Refund to credit
            $user->addCredit($trip->price);
            
            Database::commit();
            
            Session::flash('success', 'Biletiniz iptal edildi ve ücret kredinize iade edildi.');
            Response::redirect('/me/tickets');
            
        } catch (\Exception $e) {
            Database::rollback();
            Session::flash('error', $e->getMessage());
            Response::back();
        }
    }
    
    /**
     * Download ticket as PDF
     * Route: GET /tickets/{id}/pdf
     * Access: User role only, must own the ticket
     * IDOR Protection: ticket.user_id === currentUser.id
     */
    public function downloadPdf(string $id): void {
        Auth::requireRole('user');
        
        $ticketId = (int) $id;
        $ticket = Ticket::find($ticketId);
        
        // 404 if ticket doesn't exist
        if (!$ticket) {
            Session::flash('error', 'Bilet bulunamadı.');
            Response::notFound();
        }
        
        // IDOR Protection: only ticket owner can download PDF
        // 403 Forbidden if trying to download someone else's ticket
        if ($ticket->user_id !== Auth::id()) {
            Session::flash('error', 'Bu bilete erişim yetkiniz yok.');
            http_response_code(403);
            Response::redirect('/me/tickets');
        }
        
        // Get related data
        $trip = $ticket->getTrip();
        $firm = \App\Models\Firm::find($trip->firma_id);
        $user = Auth::user();
        
        // Prepare ticket data for PDF
        // All data is sanitized inside PdfGenerator
        $ticketData = [
            'ticket_id' => (string) $ticket->id,
            'firma_name' => $firm->name ?? 'N/A',
            'from_city' => $trip->from_city,
            'to_city' => $trip->to_city,
            'date' => $trip->date,
            'time' => $trip->time,
            'seat_number' => (string) $ticket->seat_number,
            'price' => number_format($trip->price, 2),
            'passenger_name' => $user->username,
            'status' => $ticket->status,
            'purchase_date' => $ticket->created_at
        ];
        
        // Generate PDF using FPDF
        $pdfContent = PdfGenerator::generateTicketPdf($ticketData);
        
        // Sanitize ticket ID for filename (only allow digits)
        $safeTicketId = preg_replace('/[^0-9]/', '', (string) $ticket->id);
        $filename = 'bilet_' . $safeTicketId . '.pdf';
        
        // Send PDF response with secure headers
        PdfGenerator::sendPdfResponse($pdfContent, $filename);
    }
}

