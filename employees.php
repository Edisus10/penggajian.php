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
                $query = "INSERT INTO employees (employee_code, name, position, basic_salary, commission_rate, phone, address, hire_date) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $commission_rate = $_POST['position'] == 'sales' ? $_POST['commission_rate'] : 0;
                $stmt = $db->prepare($query);
                if ($stmt->execute([
                    $_POST['employee_code'], $_POST['name'], $_POST['position'],
                    $_POST['basic_salary'], $commission_rate, $_POST['phone'],
                    $_POST['address'], $_POST['hire_date']
                ])) {
                    $message = '<div class="alert alert-success">Karyawan berhasil ditambahkan!</div>';
                }
                break;
                
            case 'edit':
                $query = "UPDATE employees SET name = ?, position = ?, basic_salary = ?, commission_rate = ?, 
                          phone = ?, address = ? WHERE id = ?";
                $commission_rate = $_POST['position'] == 'sales' ? $_POST['commission_rate'] : 0;
                $stmt = $db->prepare($query);
                if ($stmt->execute([
                    $_POST['name'], $_POST['position'], $_POST['basic_salary'],
                    $commission_rate, $_POST['phone'], $_POST['address'], $_POST['id']
                ])) {
                    $message = '<div class="alert alert-success">Data karyawan berhasil diupdate!</div>';
                }
                break;
                
            case 'delete':
                $query = "UPDATE employees SET status = 'inactive' WHERE id = ?";
                $stmt = $db->prepare($query);
                if ($stmt->execute([$_POST['id']])) {
                    $message = '<div class="alert alert-success">Karyawan berhasil dinonaktifkan!</div>';
                }
                break;
        }
    }
}

// Get employees
$query = "SELECT * FROM employees WHERE status = 'active' ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get employee for editing
$edit_employee = null;
if (isset($_GET['edit'])) {
    $query = "SELECT * FROM employees WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['edit']]);
    $edit_employee = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Karyawan - Sistem Penggajian</title>
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
                            <a class="nav-link text-white active" href="employees.php">
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
                    <h1 class="h2">Data Karyawan</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                        <i class="fas fa-plus me-1"></i> Tambah Karyawan
                    </button>
                </div>

                <?php echo $message; ?>

                <!-- Employees Table -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama</th>
                                        <th>Posisi</th>
                                        <th>Gaji Pokok</th>
                                        <th>Komisi</th>
                                        <th>Telepon</th>
                                        <th>Tanggal Masuk</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employees as $employee): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($employee['employee_code']); ?></td>
                                            <td><?php echo htmlspecialchars($employee['name']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $employee['position'] == 'sales' ? 'bg-success' : 'bg-primary'; ?>">
                                                    <?php echo ucfirst($employee['position']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatCurrency($employee['basic_salary']); ?></td>
                                            <td><?php echo $employee['commission_rate'] * 100; ?>%</td>
                                            <td><?php echo htmlspecialchars($employee['phone']); ?></td>
                                            <td><?php echo formatDate($employee['hire_date']); ?></td>
                                            <td>
                                                <a href="?edit=<?php echo $employee['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteEmployee(<?php echo $employee['id']; ?>)">
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

    <!-- Add Employee Modal -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $edit_employee ? 'edit' : 'add'; ?>">
                    <?php if ($edit_employee): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_employee['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <?php echo $edit_employee ? 'Edit Karyawan' : 'Tambah Karyawan'; ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <?php if (!$edit_employee): ?>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Kode Karyawan</label>
                                    <input type="text" class="form-control" name="employee_code" required>
                                </div>
                            <?php endif; ?>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" name="name" 
                                       value="<?php echo $edit_employee['name'] ?? ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Posisi</label>
                                <select class="form-select" name="position" onchange="toggleCommission()" required>
                                    <option value="regular" <?php echo ($edit_employee['position'] ?? '') == 'regular' ? 'selected' : ''; ?>>
                                        Karyawan Biasa
                                    </option>
                                    <option value="sales" <?php echo ($edit_employee['position'] ?? '') == 'sales' ? 'selected' : ''; ?>>
                                        Sales
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gaji Pokok</label>
                                <input type="number" class="form-control" name="basic_salary" 
                                       value="<?php echo $edit_employee['basic_salary'] ?? ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3" id="commissionField">
                                <label class="form-label">Rate Komisi (%)</label>
                                <input type="number" class="form-control" name="commission_rate" step="0.01" min="0" max="100"
                                       value="<?php echo ($edit_employee['commission_rate'] ?? 0) * 100; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telepon</label>
                                <input type="text" class="form-control" name="phone" 
                                       value="<?php echo $edit_employee['phone'] ?? ''; ?>">
                            </div>
                            <?php if (!$edit_employee): ?>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Tanggal Masuk</label>
                                    <input type="date" class="form-control" name="hire_date" required>
                                </div>
                            <?php endif; ?>
                            <div class="col-12 mb-3">
                                <label class="form-label">Alamat</label>
                                <textarea class="form-control" name="address" rows="3"><?php echo $edit_employee['address'] ?? ''; ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <?php echo $edit_employee ? 'Update' : 'Simpan'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleCommission() {
            const position = document.querySelector('select[name="position"]').value;
            const commissionField = document.getElementById('commissionField');
            commissionField.style.display = position === 'sales' ? 'block' : 'none';
        }

        function deleteEmployee(id) {
            if (confirm('Yakin ingin menonaktifkan karyawan ini?')) {
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

        // Show modal if editing
        <?php if ($edit_employee): ?>
            new bootstrap.Modal(document.getElementById('addEmployeeModal')).show();
            toggleCommission();
        <?php endif; ?>

        // Initialize commission field visibility
        document.addEventListener('DOMContentLoaded', function() {
            toggleCommission();
        });
    </script>
</body>
</html>