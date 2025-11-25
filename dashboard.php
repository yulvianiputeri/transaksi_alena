<?php
session_start();
require_once 'config.php';

// Cek login dan role admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] != 'admin') {
    redirect('login.php');
}

// Ambil statistik umum
$stmt_total = $conn->prepare("SELECT 
    COUNT(*) as total_transaksi,
    SUM(laba) as total_laba,
    SUM(jual) as total_pendapatan,
    SUM(beli) as total_pengeluaran,
    SUM(bongkar) as total_bongkar
FROM transaksi");
$stmt_total->execute();
$stats = $stmt_total->get_result()->fetch_assoc();
$stmt_total->close();

// Ambil data transaksi untuk grafik laba per jenis
$stmt_chart = $conn->prepare("SELECT jenis, SUM(laba) AS total_laba FROM transaksi GROUP BY jenis ORDER BY total_laba DESC");
$stmt_chart->execute();
$result_chart = $stmt_chart->get_result();

$labels = [];
$data = [];
$colors = ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe', '#00f2fe'];
$colorIndex = 0;

while ($row = $result_chart->fetch_assoc()) {
    $labels[] = $row['jenis'];
    $data[] = $row['total_laba'];
}
$stmt_chart->close();

// Ambil transaksi terbaru (10 terakhir)
$stmt_recent = $conn->prepare("SELECT * FROM transaksi ORDER BY id DESC LIMIT 10");
$stmt_recent->execute();
$result_recent = $stmt_recent->get_result();

// Ambil produk terlaris
$stmt_terlaris = $conn->prepare("SELECT jenis, COUNT(*) as jumlah, SUM(berat) as total_berat 
    FROM transaksi GROUP BY jenis ORDER BY jumlah DESC LIMIT 5");
$stmt_terlaris->execute();
$result_terlaris = $stmt_terlaris->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sistem Transaksi Alena Alena</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
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
            max-width: 1400px;
        }
        .stats-card {
            border: none;
            border-radius: 15px;
            padding: 25px;
            color: white;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            position: relative;
            overflow: hidden;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }
        .stats-card-primary { background: var(--primary-gradient); }
        .stats-card-success { background: var(--success-gradient); }
        .stats-card-warning { background: var(--warning-gradient); }
        .stats-card-info { background: var(--info-gradient); }
        
        .stats-icon {
            font-size: 2.5rem;
            opacity: 0.8;
            margin-bottom: 10px;
        }
        .stats-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 10px 0;
        }
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        .card-header {
            background: var(--primary-gradient);
            color: white;
            padding: 20px;
            border: none;
            border-radius: 15px 15px 0 0 !important;
        }
        .card-header h5 {
            margin: 0;
            font-weight: 600;
        }
        .table {
            margin-bottom: 0;
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
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        .table tbody tr {
            transition: all 0.3s;
        }
        .table tbody tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
        }
        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f3f5;
        }
        .btn-action {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            margin: 0 2px;
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
        .badge-laba {
            background: var(--success-gradient);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
        }
        .badge-rugi {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
        }
        .chart-container {
            position: relative;
            height: 350px;
            padding: 20px;
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
        .product-rank {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 10px;
            transition: all 0.3s;
        }
        .product-rank:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        .rank-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
            margin-right: 15px;
            min-width: 40px;
        }
        .rank-info {
            flex: 1;
        }
        .rank-name {
            font-weight: 600;
            color: #495057;
        }
        .rank-detail {
            font-size: 0.85rem;
            color: #6c757d;
        }
        @media (max-width: 768px) {
            .stats-value { font-size: 1.5rem; }
            .table { font-size: 0.85rem; }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-receipt"></i> Sistem Transaksi Alena Alena
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
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
        <!-- Welcome Message -->
        <div class="mb-4">
            <h2>Selamat Datang, <?= htmlspecialchars($_SESSION['username']) ?>! 👋</h2>
            <p class="text-muted">Berikut adalah ringkasan bisnis Anda hari ini</p>
        </div>

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card stats-card-primary">
                    <div class="stats-icon"><i class="bi bi-receipt"></i></div>
                    <div class="stats-label">Total Transaksi</div>
                    <div class="stats-value"><?= number_format($stats['total_transaksi'] ?? 0) ?></div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card stats-card-success">
                    <div class="stats-icon"><i class="bi bi-graph-up-arrow"></i></div>
                    <div class="stats-label">Total Laba</div>
                    <div class="stats-value"><?= formatRupiah($stats['total_laba'] ?? 0) ?></div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card stats-card-info">
                    <div class="stats-icon"><i class="bi bi-cash-stack"></i></div>
                    <div class="stats-label">Total Pendapatan</div>
                    <div class="stats-value"><?= formatRupiah($stats['total_pendapatan'] ?? 0) ?></div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card stats-card-warning">
                    <div class="stats-icon"><i class="bi bi-wallet2"></i></div>
                    <div class="stats-label">Total Pengeluaran</div>
                    <div class="stats-value"><?= formatRupiah(($stats['total_pengeluaran'] ?? 0) + ($stats['total_bongkar'] ?? 0)) ?></div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Chart Section -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-bar-chart-fill"></i> Grafik Laba per Jenis Produk</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($labels) > 0): ?>
                            <div class="chart-container">
                                <canvas id="labaChart"></canvas>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-graph-up"></i>
                                <h5>Belum Ada Data Transaksi</h5>
                                <p>Mulai tambahkan transaksi untuk melihat grafik</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Top Products -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-trophy-fill"></i> Produk Terlaris</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($result_terlaris->num_rows > 0): ?>
                            <?php 
                            $rank = 1;
                            while ($row = $result_terlaris->fetch_assoc()): 
                            ?>
                                <div class="product-rank">
                                    <div class="rank-number">#<?= $rank ?></div>
                                    <div class="rank-info">
                                        <div class="rank-name"><?= htmlspecialchars($row['jenis']) ?></div>
                                        <div class="rank-detail">
                                            <i class="bi bi-cart-check"></i> <?= $row['jumlah'] ?> transaksi • 
                                            <i class="bi bi-speedometer"></i> <?= number_format($row['total_berat'], 2) ?> kg
                                        </div>
                                    </div>
                                </div>
                            <?php 
                            $rank++;
                            endwhile; 
                            ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <p>Belum ada data produk</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="bi bi-clock-history"></i> Transaksi Terbaru</h5>
                <a href="index.php" class="btn btn-light btn-sm">
                    <i class="bi bi-plus-circle"></i> Tambah Transaksi
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Nama</th>
                                <th>Jenis</th>
                                <th>Berat</th>
                                <th>Beli</th>
                                <th>Jual</th>
                                <th>Bongkar</th>
                                <th>Laba</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result_recent && $result_recent->num_rows > 0) {
                                $no = 1;
                                while ($row = $result_recent->fetch_assoc()) {
                                    $labaClass = $row['laba'] >= 0 ? 'badge-laba' : 'badge-rugi';
                                    echo "<tr>
                                            <td><strong>$no</strong></td>
                                            <td>" . date('d/m/Y', strtotime($row['tanggal'])) . "</td>
                                            <td>" . htmlspecialchars($row['nama']) . "</td>
                                            <td><i class='bi bi-box-seam text-primary'></i> " . htmlspecialchars($row['jenis']) . "</td>
                                            <td>" . number_format($row['berat'], 2) . " kg</td>
                                            <td>" . formatRupiah($row['beli']) . "</td>
                                            <td>" . formatRupiah($row['jual']) . "</td>
                                            <td>" . formatRupiah($row['bongkar']) . "</td>
                                            <td><span class='$labaClass'>" . formatRupiah($row['laba']) . "</span></td>
                                            <td class='text-center'>
                                                <a href='edit_transaksi.php?id=" . $row['id'] . "' 
                                                   class='btn btn-edit btn-action' title='Edit'>
                                                    <i class='bi bi-pencil'></i>
                                                </a>
                                                <a href='delete_transaksi.php?id=" . $row['id'] . "' 
                                                   class='btn btn-delete btn-action' 
                                                   onclick='return confirm(\"Yakin ingin menghapus transaksi ini?\")' 
                                                   title='Hapus'>
                                                    <i class='bi bi-trash'></i>
                                                </a>
                                            </td>
                                        </tr>";
                                    $no++;
                                }
                            } else {
                                echo "<tr>
                                        <td colspan='10'>
                                            <div class='empty-state'>
                                                <i class='bi bi-inbox'></i>
                                                <h5>Belum Ada Transaksi</h5>
                                                <p>Mulai tambahkan transaksi pertama Anda</p>
                                                <a href='index.php' class='btn btn-primary mt-3'>
                                                    <i class='bi bi-plus-circle'></i> Tambah Transaksi
                                                </a>
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
        <?php if (count($labels) > 0): ?>
        // Data untuk grafik laba per jenis produk
        const labels = <?= json_encode($labels) ?>;
        const data = <?= json_encode($data) ?>;

        // Membuat grafik menggunakan Chart.js
        const ctx = document.getElementById('labaChart').getContext('2d');
        const labaChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Laba',
                    data: data,
                    backgroundColor: [
                        'rgba(102, 126, 234, 0.8)',
                        'rgba(118, 75, 162, 0.8)',
                        'rgba(240, 147, 251, 0.8)',
                        'rgba(245, 87, 108, 0.8)',
                        'rgba(79, 172, 254, 0.8)',
                        'rgba(0, 242, 254, 0.8)'
                    ],
                    borderColor: [
                        'rgba(102, 126, 234, 1)',
                        'rgba(118, 75, 162, 1)',
                        'rgba(240, 147, 251, 1)',
                        'rgba(245, 87, 108, 1)',
                        'rgba(79, 172, 254, 1)',
                        'rgba(0, 242, 254, 1)'
                    ],
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return 'Laba: Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        // Display flash messages
        <?php displayFlashMessage(); ?>
    </script>
</body>
</html>