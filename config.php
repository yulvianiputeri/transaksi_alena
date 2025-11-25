<?php
// Pengaturan error reporting untuk development
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set ke 0 untuk production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Konfigurasi database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'transaksi_db');

// Fungsi untuk mendapatkan koneksi database
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Set charset ke UTF-8
        $conn->set_charset("utf8mb4");
        
        // Cek koneksi
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            die("Terjadi kesalahan koneksi database. Silakan hubungi administrator.");
        }
    }
    
    return $conn;
}

// Fungsi helper untuk sanitasi input
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Fungsi untuk format rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Fungsi untuk validasi tanggal
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Fungsi untuk redirect dengan message
function redirect($url, $message = null, $type = 'success') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: $url");
    exit;
}

// Fungsi untuk menampilkan flash message
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        
        $alertClass = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info'
        ][$type] ?? 'alert-info';
        
        echo "<div class='alert $alertClass alert-dismissible fade show' role='alert'>
                $message
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
        
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    }
}

// Inisialisasi koneksi
$conn = getDBConnection();
?>