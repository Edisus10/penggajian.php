<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'config/database.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

$message = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $sales_date = $_POST['sales_date'];
                $month = date('n', strtotime($sales_date));
                $year = date('Y', strtotime($sales_date));
                
                $query = "INSERT INTO sales_records (employee_id, sales_amount, sales_date, description, month, year) 
                          VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                if ($stmt->execute([
                    $_POST['employee_id'], $_POST['sales_amount'], $sales_date,
                    $_POST['description'], $month, $year
                ])) {
                    $message = '<div class="alert alert-success">Data penjualan berhasil ditambahkan!</div>';
                }
                break;
                
            case 'delete':
                $query = "DELETE FROM sales_records WHERE id = ?";
                $stmt = $db->prepare($query);
                if ($stmt->execute([$_POST['id']])) {
                    $message = '<div class="alert alert-success">Data penjualan berhasil dihapus!</div>';
                }
                break;
        }
    }
}

// Get current month and year for filter
$current_month = isset($_GET['month']) ? $_GET['month'] : date('n');
$current_year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Get sales data
$query = "SELECT sr.*, e.name, e.employee_code, e.commission_rate
          FROM sales_records sr
          JOIN employees e ON sr.employee_id = e.id
          WHERE sr.month = ? AND sr.year = ?
          ORDER BY sr.sales_date DESC";
$stmt = $db->prepare($query);
$stmt->execute([$current_month, $current_year]);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get sales employees for dropdown
$query = "SELECT id, name, employee_code FROM employees WHERE status = 'active' AND position = 'sales' ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$sales_employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate monthly summary
$monthly_summary = [];
$query = "SELECT e.id, e.name, e.employee_code, e.commission_rate,
          COALESCE(SUM(sr.sales_amount), 0) as total_sales,
          COALESCE(SUM(sr.sales_amount * e.commission_rate), 0) as total_commission
          FROM employees e
          LEFT JOIN sales_records sr ON e.id = sr.employee_id AND sr.month = ? AND sr.year = ?
          WHERE e.status = 'active' AND e.position = 'sales'
          GROUP BY e.id
          ORDER BY total_sales DESC";
$stmt = $db->prepare($query);
$stmt->execute([$current_month, $current_year]);
$monthly_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Sales - Sistem Penggajian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="index.php">
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
                            <a class="nav-link text-white active" href="sales.php">
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
                    <h1 class="h2">Data Sales</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addSalesModal">
                            <i class="fas fa-plus me-1"></i> Tambah Sales
                        </button>
                        <form method="GET" class="d-flex">
                            <select name="month" class="form-select form-select-sm me-2">
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $i == $current_month ? 'selected' : ''; ?>>
                                        <?php echo getMonthName($i); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <input type="number" name="year" class="form-control form-control-sm me-2" 
                                   value="<?php echo $current_year; ?>" min="2020" max="2030" style="width: 100px;">
                            <button type="submit" class="btn btn-outline-primary btn-sm">Filter</button>
                        </form>
                    </div>
                </div>

                <?php echo $message; ?>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Menampilkan data sales untuk <strong><?php echo getMonthName($current_month) . ' ' . $current_year; ?></strong>
                </div>

                <!-- Monthly Summary -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>
                                    Ringkasan Bulanan
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Sales</th>
                                                <th>Total Penjualan</th>
                                                <th>Rate Komisi</th>
                                                <th>Total Komisi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($monthly_summary as $summary): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($summary['name']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($summary['employee_code']); ?></small>
                                                    </td>
                                                    <td><?php echo formatCurrency($summary['total_sales']); ?></td>
                                                    <td><?php echo $summary['commission_rate'] * 100; ?>%</td>
                                                    <td><strong class="text-success"><?php echo formatCurrency($summary['total_commission']); ?></strong></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales Records -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Data Penjualan
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sales</th>
                                        <th>Jumlah Penjualan</th>
                                        <th>Tanggal</th>
                                        <th>Komisi</th>
                                        <th>Keterangan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sales as $sale): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($sale['name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($sale['employee_code']); ?></small>
                                            </td>
                                            <td><?php echo formatCurrency($sale['sales_amount']); ?></td>
                                            <td><?php echo formatDate($sale['sales_date']); ?></td>
                                            <td class="text-success">
                                                <?php echo formatCurrency($sale['sales_amount'] * $sale['commission_rate']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($sale['description']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteSales(<?php echo $sale['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Sales Modal -->
    <div class="modal fade" id="addSalesModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Data Penjualan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Sales</label>
                            <select class="form-select" name="employee_id" required>
                                <option value="">Pilih Sales</option>
                                <?php foreach ($sales_employees as $employee): ?>
                                    <option value="<?php echo $employee['id']; ?>">
                                        <?php echo htmlspecialchars($employee['employee_code'] . ' - ' . $employee['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jumlah Penjualan</label>
                            <input type="number" class="form-control" name="sales_amount" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Penjualan</label>
                            <input type="date" class="form-control" name="sales_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteSales(id) {
            if (confirm('Yakin ingin menghapus data penjualan ini?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>