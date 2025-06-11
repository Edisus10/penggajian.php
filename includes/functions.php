<?php
function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function getMonthName($month) {
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    return $months[$month];
}

function calculateSalary($employee_id, $month, $year, $db) {
    // Get employee data
    $query = "SELECT * FROM employees WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $basic_salary = $employee['basic_salary'];
    $commission = 0;
    $sales_amount = 0;
    
    // Calculate commission for sales
    if ($employee['position'] == 'sales') {
        $query = "SELECT SUM(sales_amount) as total_sales FROM sales_records 
                  WHERE employee_id = ? AND month = ? AND year = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$employee_id, $month, $year]);
        $sales_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $sales_amount = $sales_data['total_sales'] ?? 0;
        $commission = $sales_amount * $employee['commission_rate'];
    }
    
    $gross_salary = $basic_salary + $commission;
    
    // Calculate total BON for this month
    $query = "SELECT SUM(amount) as total_bon FROM employee_bons 
              WHERE employee_id = ? AND deduction_month = ? AND deduction_year = ? AND status = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->execute([$employee_id, $month, $year]);
    $bon_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $total_bon = $bon_data['total_bon'] ?? 0;
    $net_salary = $gross_salary - $total_bon;
    
    return [
        'basic_salary' => $basic_salary,
        'sales_amount' => $sales_amount,
        'commission' => $commission,
        'total_bon' => $total_bon,
        'gross_salary' => $gross_salary,
        'net_salary' => $net_salary
    ];
}

function processSalaryPayment($employee_id, $month, $year, $db) {
    $salary_data = calculateSalary($employee_id, $month, $year, $db);
    
    // Insert or update monthly salary
    $query = "INSERT INTO monthly_salaries 
              (employee_id, month, year, basic_salary, sales_amount, commission, total_bon, gross_salary, net_salary, payment_date, status)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'paid')
              ON DUPLICATE KEY UPDATE
              basic_salary = VALUES(basic_salary),
              sales_amount = VALUES(sales_amount),
              commission = VALUES(commission),
              total_bon = VALUES(total_bon),
              gross_salary = VALUES(gross_salary),
              net_salary = VALUES(net_salary),
              payment_date = CURDATE(),
              status = 'paid'";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        $employee_id, $month, $year,
        $salary_data['basic_salary'],
        $salary_data['sales_amount'],
        $salary_data['commission'],
        $salary_data['total_bon'],
        $salary_data['gross_salary'],
        $salary_data['net_salary']
    ]);
    
    // Mark BONs as deducted
    $query = "UPDATE employee_bons SET status = 'deducted' 
              WHERE employee_id = ? AND deduction_month = ? AND deduction_year = ? AND status = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->execute([$employee_id, $month, $year]);
    
    return $salary_data;
}
?>