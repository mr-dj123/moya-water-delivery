<?php
// Start session and include config
session_start();
require_once "config.php";

// Redirect if not logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.html");
    exit;
}

// Check for POST request
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("location: order.php"); // Send back if accessed directly
    exit;
}

// Define Key Prices (Must match the price defined in the database/frontend for security)
define('REFILL_PRICE', 20.00);
define('NEW_CONTAINER_PRICE', 120.00);

$user_id = $_SESSION["id"];
$product_name_type = sanitize_input($conn, $_POST['product_name'] ?? '');
$container_option = sanitize_input($conn, $_POST['container_option'] ?? '');
$quantity = (int)($_POST['quantity'] ?? 0);
$client_calculated_total = (float)($_POST['calculated_total'] ?? 0);

$refill_product_id = (int)($_POST['refill_product_id'] ?? 0);
$new_container_product_id = (int)($_POST['new_container_product_id'] ?? 0);


// --- 1. Server-Side Price Recalculation (Security Check) ---
$server_total = 0;
$promo_applied_server = false;
$product_id_to_use = 0;

if ($container_option == 'buy') {
    // New Container Purchase
    $product_id_to_use = $new_container_product_id;
    
    // First gallon is 120 (Container + Fill)
    $server_total = NEW_CONTAINER_PRICE; 

    if ($quantity > 1) {
        // Remaining gallons are refills
        $refill_qty = $quantity - 1;
        $product_id_to_use = $refill_product_id; // For the order item itself (easier tracking of *what* was delivered)

        // Apply refill promo logic to the rest
        $promo_bundles = floor($refill_qty / 6);
        $remainder = $refill_qty % 6;
        
        if ($promo_bundles > 0) {
            $server_total += ($promo_bundles * ((5 * REFILL_PRICE) + 100)); // ₱200 per 6-pack
            $promo_applied_server = true;
        }
        $server_total += $remainder * REFILL_PRICE;

    }

} else {
    // Refill Purchase
    $product_id_to_use = $refill_product_id;

    // Apply promo logic to all refills
    $promo_bundles = floor($quantity / 6);
    $remainder = $quantity % 6;
    
    if ($promo_bundles > 0) {
        $server_total += ($promo_bundles * ((5 * REFILL_PRICE) + 100)); // ₱200 per 6-pack
        $promo_applied_server = true;
    }
    $server_total += $remainder * REFILL_PRICE;
}

// Final check: Quantity and Price validation
if ($quantity <= 0 || $product_id_to_use <= 0) {
    die("Error: Invalid quantity or product selection.");
}

// Optionally, check if the client-side calculated total matches the server total closely (e.g., within 0.01 tolerance)
if (abs($server_total - $client_calculated_total) > 0.01) {
    // Log this error for potential fraud investigation, but use the server-side total
    error_log("Price mismatch detected for User ID: $user_id. Client: $client_calculated_total, Server: $server_total");
}

// --- 2. Insert Order into Database ---
$order_status = "Pending";
// Use the address stored in the session for the snapshot (in a real app, you'd fetch the full address)
$address_snapshot = "Barangay: " . $_SESSION["address_barangay"] . " | Details: (Refer to user profile for specifics)"; 

$sql = "INSERT INTO orders (user_id, product_id, quantity, total_amount, status, delivery_address_snapshot) VALUES (?, ?, ?, ?, ?, ?)";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "iiidss", $user_id, $product_id_to_use, $quantity, $server_total, $order_status, $address_snapshot);
    
    if (mysqli_stmt_execute($stmt)) {
        // Success: Redirect to a confirmation or order history page
        header("location: profile.php?order=success&total=" . $server_total);
        exit();
    } else {
        // Failure: Display error
        echo "<h2>Order Failed</h2>";
        echo "<p style='color:red;'>Error placing order: " . mysqli_error($conn) . "</p>";
        echo "<p><a href='order.php'>Try ordering again</a></p>";
    }
    mysqli_stmt_close($stmt);
} else {
    echo "<h2>Order Failed</h2>";
    echo "<p style='color:red;'>Database prepare error: " . mysqli_error($conn) . "</p>";
}

mysqli_close($conn);
?>
