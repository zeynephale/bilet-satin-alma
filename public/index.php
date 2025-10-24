<?php

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Error handling
if (($_ENV['APP_ENV'] ?? 'development') === 'production') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

use App\Core\Session;
use App\Core\SecurityHeaders;
use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\TripController;
use App\Controllers\TicketController;
use App\Controllers\CouponController;
use App\Controllers\FirmAdminController;
use App\Controllers\AdminController;
use App\Controllers\ProfileController;

// Start session
Session::start();

// Apply default security headers
SecurityHeaders::applyDefault();

// Initialize router
$router = new Router();

// Public routes
$router->get('/', [HomeController::class, 'index']);

// Auth routes
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout']);

// Profile routes (authenticated users)
$router->get('/profile/change-password', [ProfileController::class, 'showChangePassword']);
$router->post('/profile/change-password', [ProfileController::class, 'changePassword']);
$router->get('/profile/add-credit', [ProfileController::class, 'showAddCredit']);
$router->post('/profile/add-credit', [ProfileController::class, 'addCredit']);

// Trip routes
$router->get('/trips/:id', [TripController::class, 'show']);

// Ticket routes (User only)
$router->get('/me/tickets', [TicketController::class, 'index']);
$router->get('/tickets/:id', [TicketController::class, 'show']);
$router->post('/tickets/purchase', [TicketController::class, 'purchase']);
$router->post('/tickets/:id/cancel', [TicketController::class, 'cancel']);
$router->get('/tickets/:id/pdf', [TicketController::class, 'downloadPdf']);

// Coupon validation (AJAX)
$router->post('/coupons/validate', [CouponController::class, 'validate']);

// Firma Admin routes
$router->get('/firm-admin/trips', [FirmAdminController::class, 'trips']);
$router->post('/firm-admin/trips/create', [FirmAdminController::class, 'createTrip']);
$router->post('/firm-admin/trips/:id/delete', [FirmAdminController::class, 'deleteTrip']);

$router->get('/firm-admin/coupons', [FirmAdminController::class, 'coupons']);
$router->post('/firm-admin/coupons/create', [FirmAdminController::class, 'createCoupon']);
$router->post('/firm-admin/coupons/:id/delete', [FirmAdminController::class, 'deleteCoupon']);

// Admin routes
$router->get('/admin/firms', [AdminController::class, 'firms']);
$router->post('/admin/firms/create', [AdminController::class, 'createFirm']);
$router->post('/admin/firms/:id/delete', [AdminController::class, 'deleteFirm']);

$router->get('/admin/users', [AdminController::class, 'users']);
$router->post('/admin/users/create', [AdminController::class, 'createUser']);
$router->post('/admin/users/:id/update', [AdminController::class, 'updateUser']);
$router->post('/admin/users/:id/delete', [AdminController::class, 'deleteUser']);

$router->get('/admin/coupons', [AdminController::class, 'coupons']);
$router->post('/admin/coupons/create', [AdminController::class, 'createCoupon']);
$router->post('/admin/coupons/:id/delete', [AdminController::class, 'deleteCoupon']);

// Dispatch router
$router->dispatch();

