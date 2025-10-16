<?php
// Start the session and include config
session_start();
require_once "../config.php"; // Path is relative to the admin directory

// Check if user is logged in AND is an admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("location: login.php");
    exit;
}

$admin_name = htmlspecialchars($_SESSION["full_name"]);
$success_message = "";
$error_message = "";

// --- 1. Handle Order Status Update ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['order_id']) && isset($_POST['new_status'])) {
    
    $order_id = (int)$_POST['order_id'];
    $new_status = sanitize_input($conn, $_POST['new_status']);
    
    // Validate status value (simple check)
    if (!in_array($new_status, ['Pending', 'Processing', 'Delivered', 'Cancelled'])) {
        $error_message = "Invalid status selected.";
    } else {
        $sql_update = "UPDATE orders SET status = ? WHERE id = ?";
        if ($stmt_update = mysqli_prepare($conn, $sql_update)) {
            mysqli_stmt_bind_param($stmt_update, "si", $new_status, $order_id);
            if (mysqli_stmt_execute($stmt_update)) {
                $success_message = "Order #$order_id status updated to **$new_status** successfully.";
            } else {
                $error_message = "Error updating order status: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt_update);
        } else {
            $error_message = "Database error during status update.";
        }
    }
}


// --- 2. Fetch All Orders (Joined with Users and Products) ---
$all_orders = [];
$sql_orders = "
    SELECT 
        o.id AS order_id, 
        o.order_date, 
        o.quantity, 
        o.total_amount, 
        o.status,
        p.name AS product_name,
        p.type AS product_type,
        u.full_name AS customer_name,
        u.phone_number,
        o.delivery_address_snapshot
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN users u ON o.user_id = u.id
    ORDER BY o.order_date DESC
";

if ($result = mysqli_query($conn, $sql_orders)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $all_orders[] = $row;
    }
    mysqli_free_result($result);
} else {
    $error_message = "Error fetching orders: " . mysqli_error($conn);
}

// Close database connection
mysqli_close($conn);

// Helper function for status badge styling
function get_status_badge_class($status) {
    switch ($status) {
        case 'Pending': return 'bg-warning text-dark';
        case 'Processing': return 'bg-info text-dark';
        case 'Delivered': return 'bg-success';
        case 'Cancelled': return 'bg-danger';
        default: return 'bg-secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moya - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --moya-primary: #008080; }
        body { font-family: 'Inter', sans-serif; background-color: #f5fcfc; }
        .bg-primary { background-color: var(--moya-primary) !important; }
        .text-primary { color: var(--moya-primary) !important; }
        .card-shadow { box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); }
        .table-responsive { max-height: 80vh; overflow-y: auto; }
    </style>
</head>
<body>

<div class="container-fluid my-4">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white rounded-3 card-shadow">
                <h1 class="h2 text-primary fw-bold">Welcome, <?php echo $admin_name; ?> (Admin Dashboard)</h1>
                <div>
                    <a href="product_management.php" class="btn btn-outline-secondary me-2">Manage Products</a>
                    <a href="../logout.php" class="btn btn-danger">Log Out</a>
                </div>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success rounded-3 mb-4 fw-bold" role="alert"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger rounded-3 mb-4 fw-bold" role="alert"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="card p-4 rounded-4 card-shadow border-0">
                <h2 class="h4 fw-bold mb-3 border-bottom pb-2">All Customer Orders (Total: <?php echo count($all_orders); ?>)</h2>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="sticky-top bg-light">
                            <tr>
                                <th scope="col"># ID</th>
                                <th scope="col">Order Date</th>
                                <th scope="col">Customer</th>
                                <th scope="col">Contact/Address</th>
                                <th scope="col">Item & Qty</th>
                                <th scope="col">Total (₱)</th>
                                <th scope="col">Current Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($all_orders)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No orders have been placed yet.</td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($all_orders as $order): ?>
                                <tr>
                                    <th scope="row">#<?php echo htmlspecialchars($order['order_id']); ?></th>
                                    <td><?php echo date("M d, H:i", strtotime(htmlspecialchars($order['order_date']))); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td>
                                        <small class="fw-semibold">Phone:</small> <?php echo htmlspecialchars($order['phone_number']); ?><br>
                                        <small class="fw-semibold">Delivery:</small> <span class="text-primary"><?php echo htmlspecialchars($order['delivery_address_snapshot']); ?></span>
                                    </td>
                                    <td>
                                        <span class="fw-medium"><?php echo htmlspecialchars($order['product_name']); ?></span> (<?php echo htmlspecialchars($order['product_type']); ?>)<br>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($order['quantity']); ?> gal</span>
                                    </td>
                                    <td class="fw-bold text-success">₱<?php echo number_format(htmlspecialchars($order['total_amount']), 2); ?></td>
                                    <td>
                                        <span class="badge rounded-pill <?php echo get_status_badge_class($order['status']); ?> p-2"><?php echo htmlspecialchars($order['status']); ?></span>
                                    </td>
                                    <td>
                                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="d-flex">
                                            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                                            <select name="new_status" class="form-select form-select-sm me-2 rounded-3">
                                                <option value="Pending" <?php echo $order['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="Processing" <?php echo $order['status'] == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                                <option value="Delivered" <?php echo $order['status'] == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                <option value="Cancelled" <?php echo $order['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-primary rounded-3">Update</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>

        </div>
    </div>
</div>

</body>
</html>
