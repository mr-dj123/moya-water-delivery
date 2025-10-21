<?php
// Initialize the session and include config
session_start();
require_once "config.php"; // Ensure this path is correct

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.html");
    exit;
}

// Check for POST request
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("location: order.php");
    exit;
}

// --- 1. Gather & Sanitize Submission Data ---

$user_id = $_SESSION["id"];
$order_status = "Pending";

// Define the expected product fields and their names from order.php
$product_fields = [
    'refill_round' => ['id_name' => 'refill_round_id', 'qty_name' => 'refill_round_qty'],
    'refill_slim' => ['id_name' => 'refill_slim_id', 'qty_name' => 'refill_slim_qty'],
    'new_round' => ['id_name' => 'new_round_id', 'qty_name' => 'new_round_qty'],
    'new_slim' => ['id_name' => 'new_slim_id', 'qty_name' => 'new_slim_qty'],
];

$items_to_insert = [];
$product_ids_to_fetch = [];
$total_overall_quantity = 0;

// Collect all ordered items and their IDs
foreach ($product_fields as $key => $fields) {
    $product_id = filter_input(INPUT_POST, $fields['id_name'], FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, $fields['qty_name'], FILTER_VALIDATE_INT);
    
    // Only proceed if a valid ID and a positive quantity were sent
    if ($product_id !== false && $product_id > 0 && $quantity > 0) {
        $items_to_insert[] = [
            'product_id' => $product_id,
            'quantity' => $quantity
        ];
        $product_ids_to_fetch[] = $product_id;
        $total_overall_quantity += $quantity;
    }
}

// --- 2. Final Validation ---

if ($total_overall_quantity <= 0 || empty($items_to_insert)) {
    // This is the error you were seeing, caused by no items being selected/processed.
    $_SESSION['order_error'] = "Error: Invalid quantity or product selection. Please select at least one item.";
    header("location: order.php");
    exit;
}


// --- 3. Fetch Prices from Database (Security) ---

$product_prices = [];
// Get current prices for all ordered product IDs
$ids_placeholder = implode(',', array_fill(0, count($product_ids_to_fetch), '?'));

$sql_prices = "SELECT id, price FROM products WHERE id IN ($ids_placeholder)";

if ($stmt_prices = mysqli_prepare($conn, $sql_prices)) {
    // Bind the dynamically created list of IDs
    $types = str_repeat('i', count($product_ids_to_fetch));
    mysqli_stmt_bind_param($stmt_prices, $types, ...$product_ids_to_fetch);
    mysqli_stmt_execute($stmt_prices);
    $result_prices = mysqli_stmt_get_result($stmt_prices);
    
    while ($row = mysqli_fetch_assoc($result_prices)) {
        $product_prices[$row['id']] = (float)$row['price'];
    }
    mysqli_stmt_close($stmt_prices);
}


// --- 4. Process Each Order Item and Insert into the 'orders' Table ---

$success_count = 0;
$total_charged_amount = 0.00;

// SQL to insert into your existing 'orders' table structure
$sql_insert = "INSERT INTO orders (user_id, product_id, quantity, total_amount, status) 
               VALUES (?, ?, ?, ?, ?)";

if ($stmt_insert = mysqli_prepare($conn, $sql_insert)) {
    
    foreach ($items_to_insert as $item) {
        $product_id = $item['product_id'];
        $quantity = $item['quantity'];
        $unit_price = $product_prices[$product_id] ?? 0.00;
        
        // Calculate the total amount for THIS specific product (Subtotal)
        $subtotal = $quantity * $unit_price;
        $total_charged_amount += $subtotal; // Accumulate for the final success message
        
        if ($unit_price > 0 && $subtotal > 0) {
            // Bind parameters for THIS row insertion
            mysqli_stmt_bind_param($stmt_insert, "iiids", $user_id, $product_id, $quantity, $subtotal, $order_status);
            
            if (mysqli_stmt_execute($stmt_insert)) {
                $success_count++;
            } else {
                error_log("Order item insertion failed for product ID $product_id: " . mysqli_error($conn));
            }
        }
    }
    
    mysqli_stmt_close($stmt_insert);
} else {
    // General database preparation error
    $_SESSION['order_error'] = "Order failed: Database system error.";
    header("location: order.php");
    exit;
}

mysqli_close($conn);


// --- 5. Final Outcome Check and Redirection ---

if ($success_count > 0) {
    // Success: Redirect to a confirmation page with summary data
    $_SESSION['order_success'] = true;
    $_SESSION['order_total'] = number_format($total_charged_amount, 2);
    $_SESSION['order_item_count'] = $total_overall_quantity;
    
    // In a real application, you would generate a single order ID, but here 
    // we use the total amount and count for the confirmation message.
    header("location: order_success.php"); 
    exit;
} else {
    // Failure: No items were successfully inserted
    $_SESSION['order_error'] = "Order failed. Please check your selections and try again.";
    header("location: order.php"); 
    exit;
}
?>