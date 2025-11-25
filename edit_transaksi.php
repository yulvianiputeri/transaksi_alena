<?php
session_start();
require_once 'config.php';

// Cek login
if (!isset($_SESSION['loggedin'])) {
    redirect('login.php');
}

$error = '';
$row = null;

// Cek apakah ID transaksi ada
if (!isset($_GET['id'])) {
    redirect('dashboard.php', 'ID transaksi tidak valid!', 'error');
}

$id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if (!$id) {
    redirect('dashboard.php', 'ID transaksi tidak valid!', 'error');
}

// Ambil data transaksi berdasarkan ID menggunakan prepared statement
$stmt = $conn->prepare("SELECT * FROM transaksi WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
} else {
    redirect('dashboard.php', 'Data transaksi tidak ditemukan!', 'error');
}
$stmt->close();

// Proses update data transaksi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_transaksi'])) {
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
        // Ambil harga beli dan harga jual dari harga referensi
        $stmt_harga = $conn->prepare("SELECT harga_beli, harga_jual FROM harga_referensi WHERE jenis = ?");
        $stmt_harga->bind_param("s", $jenis);
        $stmt_harga->execute();
        $result_harga = $stmt_harga->get_result();

        if ($result_harga->num_rows > 0) {
            $row_harga = $result_harga->fetch_assoc();
            $harga_beli = $row_harga['harga_beli'];
            $harga_jual = $row_harga['harga_jual'];

            // Hitung beli, jual, dan laba
            $beli = $harga_beli * $berat;
            $jual = $harga_jual * $berat;
            $laba = $jual - $beli - $bongkar;

            // Update data transaksi
            $stmt_update = $conn->prepare(
                "UPDATE transaksi SET tanggal=?, nama=?, jenis=?, berat=?, beli=?, jual=?, laba=?, bongkar=? WHERE id=?"
            );
            $stmt_update->bind_param(
                "sssdddddi",
                $tanggal, $nama, $jenis, $berat, $beli, $jual, $laba, $bongkar, $id
            );

            if ($stmt_update->execute()) {
                redirect('dashboard.php', 'Transaksi berhasil diperbarui!', 'success');
            } else {
                $error = "Terjadi kesalahan saat memperbarui data!";
                error_log("Update error: " . $stmt_update->error);
            }
            $stmt_update->close();
        } else {
            $error = "Jenis produk tidak ditemukan dalam daftar harga referensi!";
        }
        $stmt_harga->close();
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
    <title>Edit Transaksi - Sistem Transaksi Alena Alena Alena</title>
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
            max-width: 900px;
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
        .breadcrumb {
            background: transparent;
            padding: 0;
            margin-bottom: 20px;
        }
        .breadcrumb-item a {
            color: #667eea;
            text-decoration: none;
        }
        .breadcrumb-item.active {
            color: #6c757d;
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
            color: white;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .btn-cancel {
            background: #6c757d;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: transform 0.2s;
            color: white;
        }
        .btn-cancel:hover {
            background: #5a6268;
            transform: translateY(-2px);
            color: white;
        }
        .info-box {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .info-box i {
            color: #667eea;
            margin-right: 10px;
        }
        .product-info {
            display: none;
            margin-top: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .calculation-preview {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .calculation-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .calculation-row:last-child {
            border-bottom: none;
            font-weight: 700;
            color: #667eea;
            font-size: 1.1rem;
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
                        <a class="nav-link" href="index.php">
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
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item active">Edit Transaksi</li>
            </ol>
        </nav>

        <div class="card">
            <div class="card-header">
                <h4><i class="bi bi-pencil-square"></i> Edit Transaksi #<?= $row['nomor_transaksi'] ?></h4>
            </div>
            <div class="card-body p-4">
                <div class="info-box">
                    <i class="bi bi-info-circle-fill"></i>
                    <strong>Perhatian:</strong> Mengubah data transaksi akan menghitung ulang nilai beli, jual, dan laba berdasarkan harga referensi saat ini.
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="editForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tanggal" class="form-label">
                                <i class="bi bi-calendar3"></i> Tanggal
                            </label>
                            <input type="date" class="form-control" id="tanggal" name="tanggal" 
                                   value="<?= $row['tanggal'] ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="nama" class="form-label">
                                <i class="bi bi-person"></i> Nama
                            </label>
                            <input type="text" class="form-control" id="nama" name="nama" 
                                   value="<?= htmlspecialchars($row['nama']) ?>" 
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
                                while ($row_jenis = $result_jenis->fetch_assoc()) {
                                    $selected = ($row_jenis['jenis'] == $row['jenis']) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($row_jenis['jenis']) . "' 
                                          data-beli='" . $row_jenis['harga_beli'] . "' 
                                          data-jual='" . $row_jenis['harga_jual'] . "' $selected>" 
                                          . htmlspecialchars($row_jenis['jenis']) . "</option>";
                                }
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
                                   value="<?= $row['berat'] ?>" 
                                   placeholder="0" step="0.01" min="0.01" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="bongkar" class="form-label">
                                <i class="bi bi-truck"></i> Biaya Bongkar (Rp)
                            </label>
                            <input type="number" class="form-control" id="bongkar" name="bongkar" 
                                   value="<?= $row['bongkar'] ?>" 
                                   placeholder="0" step="1000" min="0" required>
                        </div>
                    </div>

                    <!-- Calculation Preview -->
                    <div class="calculation-preview" id="calcPreview" style="display: none;">
                        <h6 class="mb-3"><i class="bi bi-calculator"></i> Preview Perhitungan</h6>
                        <div class="calculation-row">
                            <span>Nilai Beli:</span>
                            <span id="previewBeli">Rp 0</span>
                        </div>
                        <div class="calculation-row">
                            <span>Nilai Jual:</span>
                            <span id="previewJual">Rp 0</span>
                        </div>
                        <div class="calculation-row">
                            <span>Biaya Bongkar:</span>
                            <span id="previewBongkar">Rp 0</span>
                        </div>
                        <div class="calculation-row">
                            <span>Estimasi Laba:</span>
                            <span id="previewLaba">Rp 0</span>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-submit" name="update_transaksi">
                            <i class="bi bi-save"></i> Update Transaksi
                        </button>
                        <a href="dashboard.php" class="btn btn-cancel">
                            <i class="bi bi-x-circle"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Format Rupiah
        function formatRupiah(angka) {
            return 'Rp ' + parseInt(angka).toLocaleString('id-ID');
        }

        // Update product info dan calculation preview
        function updateCalculation() {
            const jenisSelect = document.getElementById('jenis');
            const selected = jenisSelect.options[jenisSelect.selectedIndex];
            const hargaBeli = parseFloat(selected.getAttribute('data-beli')) || 0;
            const hargaJual = parseFloat(selected.getAttribute('data-jual')) || 0;
            const berat = parseFloat(document.getElementById('berat').value) || 0;
            const bongkar = parseFloat(document.getElementById('bongkar').value) || 0;

            // Update product info
            if (hargaBeli && hargaJual) {
                document.getElementById('hargaBeli').textContent = formatRupiah(hargaBeli);
                document.getElementById('hargaJual').textContent = formatRupiah(hargaJual);
                document.getElementById('productInfo').style.display = 'block';
            } else {
                document.getElementById('productInfo').style.display = 'none';
            }

            // Update calculation preview
            if (berat > 0 && hargaBeli && hargaJual) {
                const nilaiBeli = hargaBeli * berat;
                const nilaiJual = hargaJual * berat;
                const laba = nilaiJual - nilaiBeli - bongkar;

                document.getElementById('previewBeli').textContent = formatRupiah(nilaiBeli);
                document.getElementById('previewJual').textContent = formatRupiah(nilaiJual);
                document.getElementById('previewBongkar').textContent = formatRupiah(bongkar);
                document.getElementById('previewLaba').textContent = formatRupiah(laba);
                document.getElementById('calcPreview').style.display = 'block';
            } else {
                document.getElementById('calcPreview').style.display = 'none';
            }
        }

        // Event listeners
        document.getElementById('jenis').addEventListener('change', updateCalculation);
        document.getElementById('berat').addEventListener('input', updateCalculation);
        document.getElementById('bongkar').addEventListener('input', updateCalculation);

        // Initial calculation on page load
        updateCalculation();

        // Auto-dismiss alerts
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