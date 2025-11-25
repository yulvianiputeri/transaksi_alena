<?php
session_start();
require_once 'config.php';

// Cek login dan role admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] != 'admin') {
    redirect('login.php');
}

$error = '';
$success = '';
$isEdit = false;
$editData = null;

// Menambahkan harga referensi
if (isset($_POST['add'])) {
    $jenis = sanitizeInput($_POST['jenis'] ?? '');
    $harga_beli = filter_var($_POST['harga_beli'] ?? 0, FILTER_VALIDATE_FLOAT);
    $harga_jual = filter_var($_POST['harga_jual'] ?? 0, FILTER_VALIDATE_FLOAT);

    // Validasi
    if (empty($jenis) || strlen($jenis) < 3) {
        $error = "Jenis produk harus diisi minimal 3 karakter!";
    } elseif ($harga_beli === false || $harga_beli <= 0) {
        $error = "Harga beli harus berupa angka positif!";
    } elseif ($harga_jual === false || $harga_jual <= 0) {
        $error = "Harga jual harus berupa angka positif!";
    } elseif ($harga_jual <= $harga_beli) {
        $error = "Harga jual harus lebih besar dari harga beli!";
    } else {
        // Cek duplikasi jenis
        $stmt_check = $conn->prepare("SELECT id FROM harga_referensi WHERE jenis = ?");
        $stmt_check->bind_param("s", $jenis);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $error = "Jenis produk '$jenis' sudah ada!";
        } else {
            $stmt = $conn->prepare("INSERT INTO harga_referensi (jenis, harga_beli, harga_jual) VALUES (?, ?, ?)");
            $stmt->bind_param("sdd", $jenis, $harga_beli, $harga_jual);
            
            if ($stmt->execute()) {
                $success = "Harga referensi berhasil ditambahkan!";
            } else {
                $error = "Terjadi kesalahan saat menyimpan data!";
                error_log("Insert error: " . $stmt->error);
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}

// Menghapus harga referensi
if (isset($_GET['delete'])) {
    $id = filter_var($_GET['delete'], FILTER_VALIDATE_INT);
    
    if ($id) {
        // Cek apakah ada transaksi yang menggunakan jenis ini
        $stmt_check = $conn->prepare("SELECT COUNT(*) as count FROM transaksi t 
                                       JOIN harga_referensi hr ON t.jenis = hr.jenis 
                                       WHERE hr.id = ?");
        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $row_check = $result_check->fetch_assoc();
        
        if ($row_check['count'] > 0) {
            $error = "Tidak dapat menghapus! Masih ada " . $row_check['count'] . " transaksi yang menggunakan jenis produk ini.";
        } else {
            $stmt = $conn->prepare("DELETE FROM harga_referensi WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $success = "Harga referensi berhasil dihapus!";
            } else {
                $error = "Terjadi kesalahan saat menghapus data!";
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}

// Mengambil data harga referensi untuk edit
if (isset($_GET['edit'])) {
    $id = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    
    if ($id) {
        $stmt = $conn->prepare("SELECT * FROM harga_referensi WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $editData = $result->fetch_assoc();
            $isEdit = true;
        }
        $stmt->close();
    }
}

// Update harga referensi setelah edit
if (isset($_POST['update'])) {
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    $jenis = sanitizeInput($_POST['jenis'] ?? '');
    $harga_beli = filter_var($_POST['harga_beli'] ?? 0, FILTER_VALIDATE_FLOAT);
    $harga_jual = filter_var($_POST['harga_jual'] ?? 0, FILTER_VALIDATE_FLOAT);

    // Validasi
    if (empty($jenis) || strlen($jenis) < 3) {
        $error = "Jenis produk harus diisi minimal 3 karakter!";
    } elseif ($harga_beli === false || $harga_beli <= 0) {
        $error = "Harga beli harus berupa angka positif!";
    } elseif ($harga_jual === false || $harga_jual <= 0) {
        $error = "Harga jual harus berupa angka positif!";
    } elseif ($harga_jual <= $harga_beli) {
        $error = "Harga jual harus lebih besar dari harga beli!";
    } else {
        // Cek duplikasi jenis (kecuali data yang sedang diedit)
        $stmt_check = $conn->prepare("SELECT id FROM harga_referensi WHERE jenis = ? AND id != ?");
        $stmt_check->bind_param("si", $jenis, $id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $error = "Jenis produk '$jenis' sudah ada!";
        } else {
            $stmt = $conn->prepare("UPDATE harga_referensi SET jenis=?, harga_beli=?, harga_jual=? WHERE id=?");
            $stmt->bind_param("sddi", $jenis, $harga_beli, $harga_jual, $id);

            if ($stmt->execute()) {
                $success = "Harga referensi berhasil diperbarui!";
                $isEdit = false;
                $editData = null;
                // Redirect untuk clear URL
                header("Location: crud_harga_referensi.php?success=updated");
                exit;
            } else {
                $error = "Terjadi kesalahan saat memperbarui data!";
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}

// Mengambil data harga referensi
$sql = "SELECT * FROM harga_referensi ORDER BY jenis ASC";
$result = $conn->query($sql);

// Handle success message dari redirect
if (isset($_GET['success']) && $_GET['success'] === 'updated') {
    $success = "Harga referensi berhasil diperbarui!";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Harga Referensi - Sistem Transaksi Alena Alena Alena</title>
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
            margin: 30px auto;
            max-width: 1200px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 30px;
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
        .table-container {
            overflow-x: auto;
        }
        .table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }
        .table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .table thead th {
            border: none;
            padding: 15px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        .table tbody tr {
            transition: all 0.3s;
        }
        .table tbody tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }
        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f3f5;
        }
        .badge-profit {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
        }
        .btn-action {
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            margin: 0 3px;
        }
        .btn-edit {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(240, 147, 251, 0.4);
            color: white;
        }
        .btn-delete {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }
        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(250, 112, 154, 0.4);
            color: white;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4rem;
            opacity: 0.3;
            margin-bottom: 20px;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        }
        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
        }
        .stats-card p {
            margin: 0;
            opacity: 0.9;
        }
        .profit-margin {
            font-size: 0.85rem;
            color: #28a745;
            font-weight: 600;
        }
        .edit-mode-indicator {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
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
                        <a class="nav-link active" href="crud_harga_referensi.php">
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
        <!-- Stats Card -->
        <div class="row">
            <div class="col-md-4">
                <div class="stats-card">
                    <p><i class="bi bi-tag-fill"></i> Total Produk</p>
                    <h3><?= $result->num_rows ?></h3>
                </div>
            </div>
        </div>

        <!-- Form Card -->
        <div class="card">
            <div class="card-header">
                <h4>
                    <i class="bi bi-<?= $isEdit ? 'pencil-square' : 'plus-circle' ?>"></i>
                    <?= $isEdit ? 'Edit' : 'Tambah' ?> Harga Referensi
                </h4>
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

                <?php if ($isEdit): ?>
                    <div class="edit-mode-indicator">
                        <span><i class="bi bi-pencil-square"></i> Mode Edit: <?= htmlspecialchars($editData['jenis']) ?></span>
                        <a href="crud_harga_referensi.php" class="btn btn-sm btn-light">
                            <i class="bi bi-x-circle"></i> Batal
                        </a>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="jenis" class="form-label">
                                <i class="bi bi-box-seam"></i> Jenis Produk
                            </label>
                            <input type="text" class="form-control" id="jenis" name="jenis" 
                                   value="<?= $isEdit ? htmlspecialchars($editData['jenis']) : '' ?>" 
                                   placeholder="Contoh: Kayu Bakar, Batako" required minlength="3">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="harga_beli" class="form-label">
                                <i class="bi bi-cash-stack"></i> Harga Beli (Rp/kg)
                            </label>
                            <input type="number" class="form-control" id="harga_beli" name="harga_beli" 
                                   value="<?= $isEdit ? $editData['harga_beli'] : '' ?>" 
                                   placeholder="0" step="100" min="1" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="harga_jual" class="form-label">
                                <i class="bi bi-cash-coin"></i> Harga Jual (Rp/kg)
                            </label>
                            <input type="number" class="form-control" id="harga_jual" name="harga_jual" 
                                   value="<?= $isEdit ? $editData['harga_jual'] : '' ?>" 
                                   placeholder="0" step="100" min="1" required>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <?php if ($isEdit): ?>
                            <button type="submit" class="btn btn-submit" name="update">
                                <i class="bi bi-save"></i> Update Harga Referensi
                            </button>
                            <a href="crud_harga_referensi.php" class="btn btn-cancel">
                                <i class="bi bi-x-circle"></i> Batal
                            </a>
                        <?php else: ?>
                            <button type="submit" class="btn btn-submit" name="add">
                                <i class="bi bi-plus-circle"></i> Tambah Harga Referensi
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table Card -->
        <div class="card">
            <div class="card-header">
                <h4><i class="bi bi-list-ul"></i> Daftar Harga Referensi</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="25%">Jenis Produk</th>
                                <th width="20%">Harga Beli</th>
                                <th width="20%">Harga Jual</th>
                                <th width="15%">Margin</th>
                                <th width="15%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result && $result->num_rows > 0) {
                                $no = 1;
                                $result->data_seek(0); // Reset pointer
                                while ($row = $result->fetch_assoc()) {
                                    $margin = $row['harga_jual'] - $row['harga_beli'];
                                    $margin_percent = ($margin / $row['harga_beli']) * 100;
                                    
                                    echo "<tr>
                                            <td><strong>$no</strong></td>
                                            <td>
                                                <i class='bi bi-box-seam text-primary'></i> 
                                                <strong>" . htmlspecialchars($row['jenis']) . "</strong>
                                            </td>
                                            <td>" . formatRupiah($row['harga_beli']) . "</td>
                                            <td>" . formatRupiah($row['harga_jual']) . "</td>
                                            <td>
                                                <span class='badge-profit'>
                                                    " . formatRupiah($margin) . "
                                                </span>
                                                <div class='profit-margin'>
                                                    <i class='bi bi-graph-up-arrow'></i> 
                                                    " . number_format($margin_percent, 1) . "%
                                                </div>
                                            </td>
                                            <td class='text-center'>
                                                <a href='crud_harga_referensi.php?edit=" . $row['id'] . "' 
                                                   class='btn btn-edit btn-action' 
                                                   title='Edit'>
                                                    <i class='bi bi-pencil'></i> Edit
                                                </a>
                                                <a href='crud_harga_referensi.php?delete=" . $row['id'] . "' 
                                                   class='btn btn-delete btn-action' 
                                                   onclick='return confirm(\"Yakin ingin menghapus data ini?\")' 
                                                   title='Hapus'>
                                                    <i class='bi bi-trash'></i> Hapus
                                                </a>
                                            </td>
                                        </tr>";
                                    $no++;
                                }
                            } else {
                                echo "<tr>
                                        <td colspan='6'>
                                            <div class='empty-state'>
                                                <i class='bi bi-inbox'></i>
                                                <h5>Belum Ada Data</h5>
                                                <p>Silakan tambahkan harga referensi produk Anda</p>
                                            </div>
                                        </td>
                                    </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Validasi harga jual harus lebih besar dari harga beli
        document.querySelector('form').addEventListener('submit', function(e) {
            const hargaBeli = parseFloat(document.getElementById('harga_beli').value);
            const hargaJual = parseFloat(document.getElementById('harga_jual').value);
            
            if (hargaJual <= hargaBeli) {
                e.preventDefault();
                alert('Harga jual harus lebih besar dari harga beli!');
                document.getElementById('harga_jual').focus();
            }
        });

        // Smooth scroll to form when edit
        <?php if ($isEdit): ?>
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
        <?php endif; ?>
    </script>
</body>
</html>