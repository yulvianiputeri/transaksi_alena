<?php
session_start();
require_once 'config.php';

// Cek login
if (!isset($_SESSION['loggedin'])) {
    redirect('login.php');
}

// Cek apakah ID transaksi ada
if (!isset($_GET['id'])) {
    redirect('dashboard.php', 'ID transaksi tidak valid!', 'error');
}

$id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

if (!$id) {
    redirect('dashboard.php', 'ID transaksi tidak valid!', 'error');
}

// Cek apakah data transaksi ada
$stmt_check = $conn->prepare("SELECT nomor_transaksi FROM transaksi WHERE id = ?");
$stmt_check->bind_param("i", $id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    $stmt_check->close();
    redirect('dashboard.php', 'Data transaksi tidak ditemukan!', 'error');
}

$row_check = $result_check->fetch_assoc();
$nomor_transaksi = $row_check['nomor_transaksi'];
$stmt_check->close();

// Hapus data transaksi berdasarkan ID
$stmt_delete = $conn->prepare("DELETE FROM transaksi WHERE id = ?");
$stmt_delete->bind_param("i", $id);

if ($stmt_delete->execute()) {
    $affected_rows = $stmt_delete->affected_rows;
    $stmt_delete->close();
    
    if ($affected_rows > 0) {
        redirect('dashboard.php', "Transaksi #$nomor_transaksi berhasil dihapus!", 'success');
    } else {
        redirect('dashboard.php', 'Tidak ada data yang dihapus!', 'warning');
    }
} else {
    error_log("Delete error: " . $stmt_delete->error);
    $stmt_delete->close();
    redirect('dashboard.php', 'Terjadi kesalahan saat menghapus data!', 'error');
}
?>