<?php
session_start();
require_once 'config.php';

// Cek login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    redirect('login.php');
}

$error = '';
$success = '';

// Proses input transaksi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_transaksi'])) {
    $tanggal = sanitizeInput($_POST['tanggal'] ?? '');
    $nama = sanitizeInput($_POST['nama'] ?? '');
    $jenis = sanitizeInput($_POST['jenis'] ?? '');
    $berat = filter_var($_POST['berat'] ?? 0, FILTER_VALIDATE_FLOAT);
    $bongkar = filter_var($_POST['bongkar'] ?? 0, FILTER_VALIDATE_FLOAT);
    
    // Validasi input
    if (!validateDate($tanggal)) {
        $error = "Format tanggal tidak valid!";
    } elseif (empty($nama) || strlen($nama) < 3) {
        $error = "Nama harus diisi minimal 3 karakter!";
    } elseif ($berat === false || $berat <= 0) {
        $error = "Berat harus berupa angka positif!";
    } elseif ($bongkar === false || $bongkar < 0) {
        $error = "Bongkar harus berupa angka positif atau 0!";
    } else {
        // Ambil harga beli dan harga jual dari harga referensi menggunakan prepared statement
        $stmt = $conn->prepare("SELECT harga_beli, harga_jual FROM harga_referensi WHERE jenis = ?");
        $stmt->bind_param("s", $jenis);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $harga_beli = $row['harga_beli'];
            $harga_jual = $row['harga_jual'];

            // Hitung beli, jual, dan laba
            $beli = $harga_beli * $berat;
            $jual = $harga_jual * $berat;
            $laba = $jual - $beli - $bongkar; // Laba = jual - beli - biaya bongkar

            // Ambil nomor transaksi terakhir
            $stmt_nomor = $conn->prepare("SELECT MAX(nomor_transaksi) AS max_nomor FROM transaksi");
            $stmt_nomor->execute();
            $result_nomor = $stmt_nomor->get_result();
            $row_nomor = $result_nomor->fetch_assoc();
            $nomor_transaksi = ($row_nomor['max_nomor'] ?? 0) + 1;
            $stmt_nomor->close();

            // Insert data transaksi dengan prepared statement
            $stmt_insert = $conn->prepare(
                "INSERT INTO transaksi (tanggal, nama, jenis, berat, beli, jual, laba, bongkar, nomor_transaksi) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt_insert->bind_param(
                "sssdddddi", 
                $tanggal, $nama, $jenis, $berat, $beli, $jual, $laba, $bongkar, $nomor_transaksi
            );
            
            if ($stmt_insert->execute()) {
                $success = "Data transaksi berhasil ditambahkan dengan nomor transaksi #$nomor_transaksi!";
                // Reset form
                $_POST = array();
            } else {
                $error = "Terjadi kesalahan saat menyimpan data!";
                error_log("Insert error: " . $stmt_insert->error);
            }
            $stmt_insert->close();
        } else {
            $error = "Jenis produk tidak ditemukan dalam daftar harga referensi!";
        }
        $stmt->close();
    }
}

// Ambil semua jenis produk dari harga referensi untuk dropdown
$sql_jenis = "SELECT jenis, harga_beli, harga_jual FROM harga_referensi ORDER BY jenis";
$result_jenis = $conn->query($sql_jenis);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Transaksi - Sistem Transaksi Alena Alena Alena</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background: var(--primary-gradient) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .container-main {
            max-width: 800px;
            margin: 30px auto;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .card-header {
            background: var(--primary-gradient);
            color: white;
            padding: 20px;
            border: none;
        }
        .card-header h4 {
            margin: 0;
            font-weight: 600;
        }
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        .form-control, .form-select {
            border-radius: 10px;
            padding: 12px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        }
        .btn-submit {
            background: var(--primary-gradient);
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .info-badge {
            background-color: #e7f3ff;
            color: #0066cc;
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 0.9rem;
            display: inline-block;
            margin-top: 5px;
        }
        .product-info {
            display: none;
            margin-top: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-receipt"></i> Sistem Transaksi Alena Alena Alena
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="bi bi-plus-circle"></i> Input Transaksi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="crud_harga_referensi.php">
                            <i class="bi bi-tag"></i> Harga Referensi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-main">
        <div class="card">
            <div class="card-header">
                <h4><i class="bi bi-file-earmark-plus"></i> Form Input Transaksi</h4>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill"></i> <?= $success ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="transaksiForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tanggal" class="form-label">
                                <i class="bi bi-calendar3"></i> Tanggal
                            </label>
                            <input type="date" class="form-control" id="tanggal" name="tanggal" 
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="nama" class="form-label">
                                <i class="bi bi-person"></i> Nama
                            </label>
                            <input type="text" class="form-control" id="nama" name="nama" 
                                   placeholder="Masukkan nama pelanggan" required minlength="3">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="jenis" class="form-label">
                            <i class="bi bi-box-seam"></i> Jenis Produk
                        </label>
                        <select class="form-select" name="jenis" id="jenis" required>
                            <option value="">-- Pilih Jenis Produk --</option>
                            <?php
                            if ($result_jenis && $result_jenis->num_rows > 0) {
                                while ($row = $result_jenis->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($row['jenis']) . "' 
                                          data-beli='" . $row['harga_beli'] . "' 
                                          data-jual='" . $row['harga_jual'] . "'>" 
                                          . htmlspecialchars($row['jenis']) . "</option>";
                                }
                            } else {
                                echo "<option value='' disabled>Tidak ada produk tersedia</option>";
                            }
                            ?>
                        </select>
                        <div id="productInfo" class="product-info">
                            <small>
                                <strong>Harga Beli:</strong> <span id="hargaBeli">-</span> | 
                                <strong>Harga Jual:</strong> <span id="hargaJual">-</span>
                            </small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="berat" class="form-label">
                                <i class="bi bi-speedometer"></i> Berat (kg)
                            </label>
                            <input type="number" class="form-control" id="berat" name="berat" 
                                   placeholder="0" step="0.01" min="0.01" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="bongkar" class="form-label">
                                <i class="bi bi-truck"></i> Biaya Bongkar (Rp)
                            </label>
                            <input type="number" class="form-control" id="bongkar" name="bongkar" 
                                   placeholder="0" step="1000" min="0" required>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-submit" name="submit_transaksi">
                            <i class="bi bi-save"></i> Simpan Transaksi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tampilkan info harga saat produk dipilih
        document.getElementById('jenis').addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const hargaBeli = selected.getAttribute('data-beli');
            const hargaJual = selected.getAttribute('data-jual');
            
            if (hargaBeli && hargaJual) {
                document.getElementById('hargaBeli').textContent = formatRupiah(hargaBeli);
                document.getElementById('hargaJual').textContent = formatRupiah(hargaJual);
                document.getElementById('productInfo').style.display = 'block';
            } else {
                document.getElementById('productInfo').style.display = 'none';
            }
        });

        function formatRupiah(angka) {
            return 'Rp ' + parseInt(angka).toLocaleString('id-ID');
        }

        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>