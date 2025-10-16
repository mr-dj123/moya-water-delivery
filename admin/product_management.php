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

// --- 1. Handle Price Update ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['product_id']) && isset($_POST['new_price'])) {
    
    $product_id = (int)$_POST['product_id'];
    $new_price = (float)$_POST['new_price'];
    
    if ($new_price <= 0) {
        $error_message = "Price must be a positive number.";
    } else {
        $sql_update = "UPDATE products SET price = ? WHERE id = ?";
        if ($stmt_update = mysqli_prepare($conn, $sql_update)) {
            mysqli_stmt_bind_param($stmt_update, "di", $new_price, $product_id); // 'd' for double/float
            if (mysqli_stmt_execute($stmt_update)) {
                $success_message = "Product ID #$product_id price updated to **₱" . number_format($new_price, 2) . "** successfully. Please note: This does not automatically update customer side JS logic yet.";
            } else {
                $error_message = "Error updating product price: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt_update);
        } else {
            $error_message = "Database error during price update.";
        }
    }
}

// --- 2. Fetch All Products ---
$products = [];
$sql_products = "SELECT id, name, type, price FROM products ORDER BY type, name";

if ($result = mysqli_query($conn, $sql_products)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
    mysqli_free_result($result);
} else {
    $error_message = "Error fetching products: " . mysqli_error($conn);
}

// Close database connection
mysqli_close($conn);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moya - Product Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --moya-primary: #008080; }
        body { font-family: 'Inter', sans-serif; background-color: #f5fcfc; }
        .bg-primary { background-color: var(--moya-primary) !important; }
        .text-primary { color: var(--moya-primary) !important; }
        .card-shadow { box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white rounded-3 card-shadow">
                <h1 class="h2 text-primary fw-bold">Manage Product Prices</h1>
                <div>
                    <a href="dashboard.php" class="btn btn-outline-primary me-2">← Back to Dashboard</a>
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
                <h2 class="h4 fw-bold mb-4 border-bottom pb-2">Product List</h2>
                
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Product Name</th>
                                <th scope="col">Type</th>
                                <th scope="col">Current Price (₱)</th>
                                <th scope="col">Update Price (₱)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <th scope="row"><?php echo htmlspecialchars($product['id']); ?></th>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($product['type']); ?></span></td>
                                    <td class="fw-bold">₱<?php echo number_format(htmlspecialchars($product['price']), 2); ?></td>
                                    <td>
                                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="d-flex">
                                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                                            <input type="number" step="0.01" name="new_price" class="form-control form-control-sm me-2 rounded-3" 
                                                   value="<?php echo htmlspecialchars($product['price']); ?>" required style="max-width: 120px;">
                                            <button type="submit" class="btn btn-sm btn-primary rounded-3">Set</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-warning mt-4 small">
                    **Note on Customer Pricing:** The current customer order page (`order.php`) has hardcoded JavaScript prices for the promo logic (`REFILL_PRICE = 20.00`, `NEW_CONTAINER_PRICE = 120.00`). If you change these prices here, you must also manually update the prices in the **`order.php`** file for the customer-facing calculations to reflect the changes!
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
