<?php
// Start session
session_start();

// Redirect if not logged in or no successful order data is present
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['order_success'])) {
    // If accessed directly or no order placed, redirect back to order page
    header("location: order.php");
    exit;
}

// Retrieve summary data from the session
$order_total = $_SESSION['order_total'] ?? '0.00';
$item_count = $_SESSION['order_item_count'] ?? 'your items';
$user_name = htmlspecialchars($_SESSION["full_name"]) ?? 'Client';

// Clear the success message so it doesn't show again on refresh or direct access
unset($_SESSION['order_success']);
unset($_SESSION['order_total']);
unset($_SESSION['order_item_count']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Moya - Order Confirmed</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { 
            --moya-primary: #008080; /* Teal */
            --moya-light: #f5fcfc;
        }
        body { background-color: var(--moya-light); font-family: 'Inter', sans-serif; }
        .container { margin-top: 50px; }
        .success-card { 
            border-left: 5px solid var(--moya-primary);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.07);
        }
        .btn-primary { 
            background-color: var(--moya-primary); 
            border-color: var(--moya-primary);
        }
        .btn-primary:hover {
            background-color: #006666; /* Darker teal */
            border-color: #006666;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card p-5 success-card rounded-4">
                <h1 class="card-title mb-4 text-center" style="color: var(--moya-primary);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" class="bi bi-check-circle-fill me-2" viewBox="0 0 16 16">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.42 10.32l-.99-1.04a.75.75 0 1 0-1.09 1.05l1.5 1.5a.75.75 0 0 0 1.08-.022L12.55 6.05a.75.75 0 0 0-.012-1.08z"/>
                    </svg>
                    Order Confirmed!
                </h1>
                
                <p class="lead text-center">Hello **<?php echo $user_name; ?>**, your order has been successfully placed. </p>
                
                <div class="text-center mt-3 mb-4">
                    <span class="badge bg-success fs-5 fw-bold p-3">
                        Total Items: <?php echo $item_count; ?>
                    </span>
                </div>

                <div class="alert text-center fw-bold fs-3 mb-4" style="background-color: #e6ffff; color: #008080;">
                    Grand Total: ₱<?php echo $order_total; ?>
                </div>

                <p class="text-muted text-center">
                    We are now processing your request. Please check your profile for updates on your order status.
                </p>
                
                <div class="d-flex justify-content-center gap-3 mt-4">
                    <a href="order.php" class="btn btn-outline-secondary w-50">Place Another Order</a>
                    <a href="profile.php" class="btn btn-primary w-50">View Order Status</a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>