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
                $query = "INSERT INTO employee_bons (employee_id, amount, description, bon_date, deduction_month, deduction_year) 
                          VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                if ($stmt->execute([
                    $_POST['employee_id'], $_POST['amount'], $_POST['description'],
                    $_POST['bon_date'], $_POST['deduction_month'], $_POST['deduction_year']
                ])) {
                    $message = '<div class="alert alert-success">BON berhasil ditambahkan!</div>';
                }
                break;
                
            case 'delete':
                $query = "DELETE FROM employee_bons WHERE id = ? AND status = 'pending'";
                $stmt = $db->prepare($query);
                if ($stmt->execute([$_POST['id']])) {
                    $message = '<div class="alert alert-success">BON berhasil dihapus!</div>';
                }
                break;
        }
    }
}

// Get BONs with employee info
$query = "SELECT eb.*, e.name, e.employee_code 
          FROM employee_bons eb
          JOIN employees e ON eb.employee_id = e.id
          ORDER BY eb.status ASC, eb.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$bons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get active employees for dropdown
$query = "SELECT id, name, employee_code FROM employees WHERE status = 'active' ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BON Karyawan - Sistem Penggajian</title>
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
                            <a class="nav-link text-white active" href="bons.php">
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
                    <h1 class="h2">BON Karyawan</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBonModal">
                        <i class="fas fa-plus me-1"></i> Tambah BON
                    </button>
                </div>

                <?php echo $message; ?>

                <!-- BONs Table -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Karyawan</th>
                                        <th>Jumlah</th>
                                        <th>Tanggal BON</th>
                                        <th>Dipotong</th>
                                        <th>Keterangan</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bons as $bon): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($bon['name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($bon['employee_code']); ?></small>
                                            </td>
                                            <td><?php echo formatCurrency($bon['amount']); ?></td>
                                            <td><?php echo formatDate($bon['bon_date']); ?></td>
                                            <td><?php echo getMonthName($bon['deduction_month']) . ' ' . $bon['deduction_year']; ?></td>
                                            <td><?php echo htmlspecialchars($bon['description']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $bon['status'] == 'pending' ? 'bg-warning' : 'bg-success'; ?>">
                                                    <?php echo ucfirst($bon['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($bon['status'] == 'pending'): ?>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteBon(<?php echo $bon['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
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

    <!-- Add BON Modal -->
    <div class="modal fade" id="addBonModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah BON Karyawan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Karyawan</label>
                            <select class="form-select" name="employee_id" required>
                                <option value="">Pilih Karyawan</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo $employee['id']; ?>">
                                        <?php echo htmlspecialchars($employee['employee_code'] . ' - ' . $employee['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jumlah BON</label>
                            <input type="number" class="form-control" name="amount" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal BON</label>
                            <input type="date" class="form-control" name="bon_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Bulan Dipotong</label>
                                <select class="form-select" name="deduction_month" required>
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $i == date('n') ? 'selected' : ''; ?>>
                                            <?php echo getMonthName($i); ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tahun Dipotong</label>
                                <input type="number" class="form-control" name="deduction_year" 
                                       value="<?php echo date('Y'); ?>" min="<?php echo date('Y'); ?>" required>
                            </div>
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
        function deleteBon(id) {
            if (confirm('Yakin ingin menghapus BON ini?')) {
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