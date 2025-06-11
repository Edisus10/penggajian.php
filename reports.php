<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'config/database.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$report_type = isset($_GET['type']) ? $_GET['type'] : 'salary';
$month = isset($_GET['month']) ? $_GET['month'] : date('n');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

$report_data = [];

switch ($report_type) {
    case 'salary':
        $query = "SELECT e.employee_code, e.name, e.position, ms.basic_salary, ms.sales_amount, 
                  ms.commission, ms.total_bon, ms.gross_salary, ms.net_salary, ms.payment_date
                  FROM monthly_salaries ms
                  JOIN employees e ON ms.employee_id = e.id
                  WHERE ms.month = ? AND ms.year = ? AND ms.status = 'paid'
                  ORDER BY e.name";
        $stmt = $db->prepare($query);
        $stmt->execute([$month, $year]);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'sales':
        $query = "SELECT e.employee_code, e.name, sr.sales_amount, sr.sales_date, 
                  (sr.sales_amount * e.commission_rate) as commission, sr.description
                  FROM sales_records sr
                  JOIN employees e ON sr.employee_id = e.id
                  WHERE sr.month = ? AND sr.year = ?
                  ORDER BY sr.sales_date DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$month, $year]);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'bon':
        $query = "SELECT e.employee_code, e.name, eb.amount, eb.bon_date, eb.description, 
                  eb.deduction_month, eb.deduction_year, eb.status
                  FROM employee_bons eb
                  JOIN employees e ON eb.employee_id = e.id
                  WHERE eb.deduction_month = ? AND eb.deduction_year = ?
                  ORDER BY eb.bon_date DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$month, $year]);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Sistem Penggajian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        @media print {
            .sidebar, .btn, .no-print { display: none !important; }
            .main-content { margin: 0 !important; }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar text-white p-0 no-print">
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
                            <a class="nav-link text-white" href="sales.php">
                                <i class="fas fa-chart-line me-2"></i> Data Sales
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white active" href="reports.php">
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
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom no-print">
                    <h1 class="h2">Laporan</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-success me-2" onclick="window.print()">
                            <i class="fas fa-print me-1"></i> Cetak
                        </button>
                        <form method="GET" class="d-flex">
                            <select name="type" class="form-select form-select-sm me-2">
                                <option value="salary" <?php echo $report_type == 'salary' ? 'selected' : ''; ?>>
                                    Laporan Gaji
                                </option>
                                <option value="sales" <?php echo $report_type == 'sales' ? 'selected' : ''; ?>>
                                    Laporan Sales
                                </option>
                                <option value="bon" <?php echo $report_type == 'bon' ? 'selected' : ''; ?>>
                                    Laporan BON
                                </option>
                            </select>
                            <select name="month" class="form-select form-select-sm me-2">
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $i == $month ? 'selected' : ''; ?>>
                                        <?php echo getMonthName($i); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <input type="number" name="year" class="form-control form-control-sm me-2" 
                                   value="<?php echo $year; ?>" min="2020" max="2030" style="width: 100px;">
                            <button type="submit" class="btn btn-outline-primary btn-sm">Filter</button>
                        </form>
                    </div>
                </div>

                <!-- Report Header -->
                <div class="text-center mb-4">
                    <h2>PT. SISTEM PENGGAJIAN</h2>
                    <h4>
                        <?php
                        switch ($report_type) {
                            case 'salary': echo 'LAPORAN PENGGAJIAN'; break;
                            case 'sales': echo 'LAPORAN PENJUALAN'; break;
                            case 'bon': echo 'LAPORAN BON KARYAWAN'; break;
                        }
                        ?>
                    </h4>
                    <p>Periode: <?php echo getMonthName($month) . ' ' . $year; ?></p>
                </div>

                <!-- Report Content -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <?php if (empty($report_data)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <p>Tidak ada data untuk periode yang dipilih</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <?php if ($report_type == 'salary'): ?>
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>No</th>
                                                <th>Kode</th>
                                                <th>Nama</th>
                                                <th>Posisi</th>
                                                <th>Gaji Pokok</th>
                                                <th>Penjualan</th>
                                                <th>Komisi</th>
                                                <th>BON</th>
                                                <th>Gaji Kotor</th>
                                                <th>Gaji Bersih</th>
                                                <th>Tgl Bayar</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $no = 1;
                                            $total_gross = 0;
                                            $total_net = 0;
                                            foreach ($report_data as $row): 
                                                $total_gross += $row['gross_salary'];
                                                $total_net += $row['net_salary'];
                                            ?>
                                                <tr>
                                                    <td><?php echo $no++; ?></td>
                                                    <td><?php echo htmlspecialchars($row['employee_code']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                    <td><?php echo ucfirst($row['position']); ?></td>
                                                    <td><?php echo formatCurrency($row['basic_salary']); ?></td>
                                                    <td><?php echo formatCurrency($row['sales_amount']); ?></td>
                                                    <td><?php echo formatCurrency($row['commission']); ?></td>
                                                    <td><?php echo formatCurrency($row['total_bon']); ?></td>
                                                    <td><?php echo formatCurrency($row['gross_salary']); ?></td>
                                                    <td><?php echo formatCurrency($row['net_salary']); ?></td>
                                                    <td><?php echo formatDate($row['payment_date']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th colspan="8">TOTAL</th>
                                                <th><?php echo formatCurrency($total_gross); ?></th>
                                                <th><?php echo formatCurrency($total_net); ?></th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                <?php elseif ($report_type == 'sales'): ?>
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>No</th>
                                                <th>Kode Sales</th>
                                                <th>Nama Sales</th>
                                                <th>Jumlah Penjualan</th>
                                                <th>Komisi</th>
                                                <th>Tanggal</th>
                                                <th>Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $no = 1;
                                            $total_sales = 0;
                                            $total_commission = 0;
                                            foreach ($report_data as $row): 
                                                $total_sales += $row['sales_amount'];
                                                $total_commission += $row['commission'];
                                            ?>
                                                <tr>
                                                    <td><?php echo $no++; ?></td>
                                                    <td><?php echo htmlspecialchars($row['employee_code']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                    <td><?php echo formatCurrency($row['sales_amount']); ?></td>
                                                    <td><?php echo formatCurrency($row['commission']); ?></td>
                                                    <td><?php echo formatDate($row['sales_date']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th colspan="3">TOTAL</th>
                                                <th><?php echo formatCurrency($total_sales); ?></th>
                                                <th><?php echo formatCurrency($total_commission); ?></th>
                                                <th colspan="2"></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                <?php else: // BON report ?>
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>No</th>
                                                <th>Kode</th>
                                                <th>Nama</th>
                                                <th>Jumlah BON</th>
                                                <th>Tanggal BON</th>
                                                <th>Status</th>
                                                <th>Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $no = 1;
                                            $total_bon = 0;
                                            foreach ($report_data as $row): 
                                                $total_bon += $row['amount'];
                                            ?>
                                                <tr>
                                                    <td><?php echo $no++; ?></td>
                                                    <td><?php echo htmlspecialchars($row['employee_code']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                    <td><?php echo formatCurrency($row['amount']); ?></td>
                                                    <td><?php echo formatDate($row['bon_date']); ?></td>
                                                    <td><?php echo ucfirst($row['status']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th colspan="3">TOTAL</th>
                                                <th><?php echo formatCurrency($total_bon); ?></th>
                                                <th colspan="3"></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Report Footer -->
                <div class="row mt-4">
                    <div class="col-md-6"></div>
                    <div class="col-md-6 text-center">
                        <p>Jakarta, <?php echo date('d F Y'); ?></p>
                        <br><br><br>
                        <p><strong>Manager HRD</strong></p>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>