<?php
// Initialize the session and include config
session_start();
require_once "config.php"; // Assuming config.php is in the same directory

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.html");
    exit;
}

$user_id = $_SESSION["id"];
$user_name = htmlspecialchars($_SESSION["full_name"]);
$user_barangay = htmlspecialchars($_SESSION["address_barangay"]);

// --- 1. Fetch User's Full Details (including phone and full address) ---
$user_details = [];
$sql_user = "SELECT full_name, email, phone_number, address_barangay, address_detail FROM users WHERE id = ?";

if ($stmt_user = mysqli_prepare($conn, $sql_user)) {
    mysqli_stmt_bind_param($stmt_user, "i", $user_id);
    if (mysqli_stmt_execute($stmt_user)) {
        $result_user = mysqli_stmt_get_result($stmt_user);
        if ($row = mysqli_fetch_assoc($result_user)) {
            $user_details = $row;
        }
        mysqli_free_result($result_user);
    }
    mysqli_stmt_close($stmt_user);
}

// --- 2. Fetch User's Order History (Joining with Products table) ---
$order_history = [];
$sql_orders = "
    SELECT 
        o.id AS order_id, 
        o.order_date, 
        o.quantity, 
        o.total_amount, 
        o.status,
        p.name AS product_name
    FROM orders o
    JOIN products p ON o.product_id = p.id
    WHERE o.user_id = ?
    ORDER BY o.order_date DESC
";

if ($stmt_orders = mysqli_prepare($conn, $sql_orders)) {
    mysqli_stmt_bind_param($stmt_orders, "i", $user_id);
    if (mysqli_stmt_execute($stmt_orders)) {
        $result_orders = mysqli_stmt_get_result($stmt_orders);
        while ($row = mysqli_fetch_assoc($result_orders)) {
            $order_history[] = $row;
        }
        mysqli_free_result($result_orders);
    }
    mysqli_stmt_close($stmt_orders);
}

// Close database connection
mysqli_close($conn);

// --- Handle Order Confirmation Message ---
$order_success = isset($_GET['order']) && $_GET['order'] == 'success';
$order_total = isset($_GET['total']) ? (float)$_GET['total'] : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moya - My Profile & Orders</title>
    <!-- Inter Font and Bootstrap CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --moya-primary: #008080; --moya-secondary: #00bfff; }
        body { font-family: 'Inter', sans-serif; background-color: #f5fcfc; }
        .bg-primary { background-color: var(--moya-primary) !important; }
        .text-primary { color: var(--moya-primary) !important; }
        .card-shadow { box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); }
        .status-badge-pending { background-color: #ffc107; color: #343a40; }
        .status-badge-delivered { background-color: #28a745; color: #fff; }
        .status-badge-cancelled { background-color: #dc3545; color: #fff; }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="display-5 text-primary fw-bold">My Moya Profile</h1>
                <div>
                    <a href="order.php" class="btn btn-outline-primary me-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart3 me-1" viewBox="0 0 16 16">
                            <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.401l-7.293 2.185a.5.5 0 0 1-.093.004L5.5 12.001A.5.5 0 0 1 5 11.5v-1a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-3.483l1.102 3.307a.5.5 0 0 1-.42.693H2.5a.5.5 0 0 1-.497-.435L.938 10.379A.5.5 0 0 1 1 10h11a.5.5 0 0 1 0 1H5.5a.5.5 0 0 0-.497.435l-.01.077-.008.058a.5.5 0 0 0 .497.435H12a.5.5 0 0 0 .497-.435l.01-.077.008-.058a.5.5 0 0 0-.497-.435H5.5a.5.5 0 0 1-.497-.435L3.89 3H.5a.5.5 0 0 1-.5-.5z"/>
                        </svg>
                        New Order
                    </a>
                    <a href="logout.php" class="btn btn-danger">Log Out</a>
                </div>
            </div>

            <?php if ($order_success): ?>
                <div class="alert alert-success d-flex align-items-center rounded-3 p-4 mb-4" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-check-circle-fill me-2" viewBox="0 0 16 16">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.497 5.385 7.399a.75.75 0 0 0-1.071.028l-2 2a.75.75 0 0 0 1.07 1.053l2.843-2.843 4.293-4.293a.75.75 0 0 0-.02-1.08z"/>
                    </svg>
                    <div>
                        <h5 class="mb-0 fw-bold">Order Confirmed!</h5>
                        Your order was successfully placed for **₱<?php echo number_format($order_total, 2); ?>**. It is now **Pending** and will be delivered shortly!
                    </div>
                </div>
            <?php endif; ?>

            <!-- User Profile Details -->
            <div class="card p-4 rounded-4 card-shadow border-0 mb-5">
                <h2 class="h4 fw-bold mb-3 border-bottom pb-2">Account Information</h2>
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-2"><span class="fw-semibold text-secondary">Full Name:</span> <span class="fw-medium text-dark"><?php echo htmlspecialchars($user_details['full_name'] ?? $user_name); ?></span></p>
                        <p class="mb-2"><span class="fw-semibold text-secondary">Email:</span> <span class="fw-medium text-dark"><?php echo htmlspecialchars($user_details['email'] ?? 'N/A'); ?></span></p>
                        <p class="mb-2"><span class="fw-semibold text-secondary">Phone:</span> <span class="fw-medium text-dark"><?php echo htmlspecialchars($user_details['phone_number'] ?? 'N/A'); ?></span></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2"><span class="fw-semibold text-secondary">Barangay:</span> <span class="fw-medium text-dark text-primary"><?php echo htmlspecialchars($user_details['address_barangay'] ?? $user_barangay); ?></span></p>
                        <p class="mb-2"><span class="fw-semibold text-secondary">Address Details:</span> <span class="fw-medium text-dark"><?php echo htmlspecialchars($user_details['address_detail'] ?? 'N/A'); ?></span></p>
                        <p class="mb-2 small text-muted">You can request an address update by contacting us.</p>
                    </div>
                </div>
            </div>

            <!-- Order History Table -->
            <div class="card p-4 rounded-4 card-shadow border-0">
                <h2 class="h4 fw-bold mb-3 border-bottom pb-2">Order History (<?php echo count($order_history); ?> Orders)</h2>

                <?php if (empty($order_history)): ?>
                    <div class="alert alert-info text-center mt-3 mb-0">
                        You haven't placed any orders yet. <a href="order.php" class="alert-link fw-bold">Click here to start your first order!</a>
                    </div>
                <?php else: ?>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th scope="col">Order ID</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Item Type</th>
                                    <th scope="col">Quantity</th>
                                    <th scope="col">Total Amount</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_history as $order): ?>
                                    <tr>
                                        <th scope="row">#<?php echo htmlspecialchars($order['order_id']); ?></th>
                                        <td><?php echo date("M d, Y", strtotime(htmlspecialchars($order['order_date']))); ?></td>
                                        <td>
                                            <span class="fw-medium"><?php echo htmlspecialchars($order['product_name']); ?></span>
                                            <br><span class="badge bg-light text-secondary border border-secondary"><?php echo htmlspecialchars($order['product_type']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($order['quantity']); ?> gal</td>
                                        <td class="fw-bold text-success">₱<?php echo number_format(htmlspecialchars($order['total_amount']), 2); ?></td>
                                        <td>
                                            <?php 
                                                $status = htmlspecialchars($order['status']);
                                                $badge_class = '';
                                                switch ($status) {
                                                    case 'Pending':
                                                        $badge_class = 'status-badge-pending';
                                                        break;
                                                    case 'Delivered':
                                                        $badge_class = 'status-badge-delivered';
                                                        break;
                                                    case 'Cancelled':
                                                        $badge_class = 'status-badge-cancelled';
                                                        break;
                                                    default:
                                                        $badge_class = 'bg-secondary';
                                                }
                                            ?>
                                            <span class="badge rounded-pill <?php echo $badge_class; ?> p-2"><?php echo $status; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
