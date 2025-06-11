<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'config/database.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get statistics
$stats = [];

// Total karyawan
$query = "SELECT COUNT(*) as total FROM employees WHERE status = 'active'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_employees'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total sales
$query = "SELECT COUNT(*) as total FROM employees WHERE status = 'active' AND position = 'sales'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_sales'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total gaji bulan ini
$current_month = date('n');
$current_year = date('Y');
$query = "SELECT SUM(net_salary) as total FROM monthly_salaries WHERE month = ? AND year = ?";
$stmt = $db->prepare($query);
$stmt->execute([$current_month, $current_year]);
$stats['total_salary'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Total BON pending
$query = "SELECT SUM(amount) as total FROM employee_bons WHERE status = 'pending'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_bon'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Recent activities
$query = "SELECT e.name, eb.amount, eb.bon_date, eb.description
          FROM employee_bons eb
          JOIN employees e ON eb.employee_id = e.id
          WHERE eb.status = 'pending'
          ORDER BY eb.created_at DESC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_bons = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Penggajian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar text-white p-0">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <i class="fas fa-money-check-alt fa-2x mb-2"></i>
                        <h6>Sistem Penggajian</h6>
                        <small>Selamat datang, <?php echo $_SESSION['admin_name']; ?></small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white active" href="index.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="employees.php">
                                <i class="fas fa-users me-2"></i> Karyawan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="salaries.php">
                                <i class="fas fa-money-bill me-2"></i> Penggajian
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="bons.php">
                                <i class="fas fa-credit-card me-2"></i> BON Karyawan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="sales.php">
                                <i class="fas fa-chart-line me-2"></i> Data Sales
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="reports.php">
                                <i class="fas fa-file-alt me-2"></i> Laporan
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-white" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <span class="badge bg-primary"><?php echo date('d F Y'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                <h4 class="mb-0"><?php echo $stats['total_employees']; ?></h4>
                                <small class="text-muted">Total Karyawan</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-line fa-2x text-success mb-2"></i>
                                <h4 class="mb-0"><?php echo $stats['total_sales']; ?></h4>
                                <small class="text-muted">Total Sales</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="fas fa-money-bill fa-2x text-info mb-2"></i>
                                <h6 class="mb-0 small"><?php echo formatCurrency($stats['total_salary']); ?></h6>
                                <small class="text-muted">Gaji Bulan Ini</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="fas fa-credit-card fa-2x text-warning mb-2"></i>
                                <h6 class="mb-0 small"><?php echo formatCurrency($stats['total_bon']); ?></h6>
                                <small class="text-muted">Total BON Pending</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent BONs -->
                <div class="row">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-credit-card me-2"></i>
                                    BON Terbaru
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_bons)): ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                        <p>Tidak ada BON pending</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Karyawan</th>
                                                    <th>Jumlah</th>
                                                    <th>Tanggal BON</th>
                                                    <th>Keterangan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_bons as $bon): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($bon['name']); ?></td>
                                                        <td><?php echo formatCurrency($bon['amount']); ?></td>
                                                        <td><?php echo formatDate($bon['bon_date']); ?></td>
                                                        <td><?php echo htmlspecialchars($bon['description']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-center mt-3">
                                        <a href="bons.php" class="btn btn-outline-primary btn-sm">
                                            Lihat Semua BON
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>