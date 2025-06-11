<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'config/database.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

$message = '';

// Handle salary processing
if ($_POST && isset($_POST['process_salary'])) {
    $employee_id = $_POST['employee_id'];
    $month = $_POST['month'];
    $year = $_POST['year'];
    
    try {
        $salary_data = processSalaryPayment($employee_id, $month, $year, $db);
        $message = '<div class="alert alert-success">Gaji berhasil diproses!</div>';
    } catch (Exception $e) {
        $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

// Get current month and year
$current_month = isset($_GET['month']) ? $_GET['month'] : date('n');
$current_year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Get employees with salary calculations
$query = "SELECT e.*, 
          COALESCE(ms.basic_salary, e.basic_salary) as current_basic_salary,
          COALESCE(ms.sales_amount, 0) as sales_amount,
          COALESCE(ms.commission, 0) as commission,
          COALESCE(ms.total_bon, 0) as total_bon,
          COALESCE(ms.gross_salary, 0) as gross_salary,
          COALESCE(ms.net_salary, 0) as net_salary,
          ms.payment_date,
          ms.status as salary_status
          FROM employees e
          LEFT JOIN monthly_salaries ms ON e.id = ms.employee_id 
                                       AND ms.month = ? AND ms.year = ?
          WHERE e.status = 'active'
          ORDER BY e.name";
$stmt = $db->prepare($query);
$stmt->execute([$current_month, $current_year]);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate pending salary data for employees without processed salary
foreach ($employees as &$employee) {
    if (!$employee['salary_status']) {
        $salary_calc = calculateSalary($employee['id'], $current_month, $current_year, $db);
        $employee['current_basic_salary'] = $salary_calc['basic_salary'];
        $employee['sales_amount'] = $salary_calc['sales_amount'];
        $employee['commission'] = $salary_calc['commission'];
        $employee['total_bon'] = $salary_calc['total_bon'];
        $employee['gross_salary'] = $salary_calc['gross_salary'];
        $employee['net_salary'] = $salary_calc['net_salary'];
        $employee['salary_status'] = 'pending';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penggajian - Sistem Penggajian</title>
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
                            <a class="nav-link text-white active" href="salaries.php">
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
                    <h1 class="h2">Penggajian</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
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
                    Menampilkan data gaji untuk <strong><?php echo getMonthName($current_month) . ' ' . $current_year; ?></strong>
                </div>

                <!-- Salaries Table -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Karyawan</th>
                                        <th>Posisi</th>
                                        <th>Gaji Pokok</th>
                                        <th>Penjualan</th>
                                        <th>Komisi</th>
                                        <th>BON</th>
                                        <th>Gaji Kotor</th>
                                        <th>Gaji Bersih</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employees as $employee): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($employee['name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($employee['employee_code']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $employee['position'] == 'sales' ? 'bg-success' : 'bg-primary'; ?>">
                                                    <?php echo ucfirst($employee['position']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatCurrency($employee['current_basic_salary']); ?></td>
                                            <td>
                                                <?php if ($employee['position'] == 'sales'): ?>
                                                    <?php echo formatCurrency($employee['sales_amount']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($employee['position'] == 'sales'): ?>
                                                    <?php echo formatCurrency($employee['commission']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($employee['total_bon'] > 0): ?>
                                                    <span class="text-danger"><?php echo formatCurrency($employee['total_bon']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><strong><?php echo formatCurrency($employee['gross_salary']); ?></strong></td>
                                            <td><strong class="text-success"><?php echo formatCurrency($employee['net_salary']); ?></strong></td>
                                            <td>
                                                <?php if ($employee['salary_status'] == 'paid'): ?>
                                                    <span class="badge bg-success">Dibayar</span><br>
                                                    <small class="text-muted"><?php echo formatDate($employee['payment_date']); ?></small>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($employee['salary_status'] != 'paid'): ?>
                                                    <button class="btn btn-sm btn-success" 
                                                            onclick="processSalary(<?php echo $employee['id']; ?>, <?php echo $current_month; ?>, <?php echo $current_year; ?>)">
                                                        <i class="fas fa-check me-1"></i> Bayar
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">Selesai</span>
                                                <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function processSalary(employeeId, month, year) {
            if (confirm('Yakin ingin memproses pembayaran gaji karyawan ini?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="process_salary" value="1">
                    <input type="hidden" name="employee_id" value="${employeeId}">
                    <input type="hidden" name="month" value="${month}">
                    <input type="hidden" name="year" value="${year}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>