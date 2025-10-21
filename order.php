<?php
// Initialize the session and include config
session_start();
require_once "config.php"; // Assuming config.php is in the same directory

// Disable caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.html");
    exit;
}

$user_id = $_SESSION["id"];
$user_name = htmlspecialchars($_SESSION["full_name"]);
$user_barangay = htmlspecialchars($_SESSION["address_barangay"]);

// --- 1. Fetch Product Data (ID and Price) from Database ---
$product_data = [];
$product_names = [
    'Standard Round Refill', 
    'Slim Container Refill', 
    'New Standard Round', 
    'New Slim Container'
];
$product_names_str = "'" . implode("','", $product_names) . "'"; // Formats: 'Name1','Name2',...

// Fetch the prices and IDs for all four specific product names
$sql_prices = "SELECT id, name, price FROM products WHERE name IN ($product_names_str)";

if ($result_prices = mysqli_query($conn, $sql_prices)) {
    while ($row = mysqli_fetch_assoc($result_prices)) {
        // Store product ID and price using the full name as the key
        $product_data[$row['name']] = [
            'id' => $row['id'],
            'price' => (float)$row['price']
        ];
    }
    mysqli_free_result($result_prices);
}

// Assign fetched prices (for display) and IDs (for submission)
$refill_price = $product_data['Standard Round Refill']['price'] ?? 20.00;
$new_container_price = $product_data['New Standard Round']['price'] ?? 120.00;

// Assign IDs (CRITICAL for place_order.php to identify the items)
$id_refill_round = $product_data['Standard Round Refill']['id'] ?? 1;
$id_refill_slim = $product_data['Slim Container Refill']['id'] ?? 2;
$id_new_round = $product_data['New Standard Round']['id'] ?? 3;
$id_new_slim = $product_data['New Slim Container']['id'] ?? 4;

// Close database connection
mysqli_close($conn);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moya - Place Your Order</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { 
            --moya-primary: #008080; /* Teal */
            --moya-secondary: #00bfff; /* Sky Blue */
            --moya-light: #f5fcfc;
            --moya-dark: #333;
        }
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--moya-light); 
        }

        /* --- Custom Color Utilities --- */
        .bg-primary { background-color: var(--moya-primary) !important; }
        .text-primary { color: var(--moya-primary) !important; }
        
        .bg-secondary { background-color: var(--moya-secondary) !important; }
        .text-secondary { color: var(--moya-secondary) !important; }

        .btn-primary { 
            background-color: var(--moya-primary); 
            border-color: var(--moya-primary);
        }
        .btn-primary:hover {
            background-color: #006666; /* Darker teal */
            border-color: #006666;
        }
        
        .btn-outline-primary {
            color: var(--moya-primary);
            border-color: var(--moya-primary);
        }
        .btn-outline-primary:hover {
            background-color: var(--moya-primary);
            color: #fff;
        }

        .btn-outline-secondary {
            color: var(--moya-secondary);
            border-color: var(--moya-secondary);
        }
        .btn-outline-secondary:hover {
            background-color: var(--moya-secondary);
            color: #fff;
        }
        
        /* --- Page & Card Styling --- */
        .card-shadow { box-shadow: 0 4px 12px rgba(0, 0, 0, 0.07); }
        
        .product-card { 
            transition: all 0.3s ease; 
            border: 2px solid transparent; /* Placeholder for active state */
            background-color: #fff;
        }
        .product-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1); 
        }
        
        /* Active state for cards */
        .product-card.active-primary {
            border-color: var(--moya-primary);
            box-shadow: 0 6px 15px rgba(0, 128, 128, 0.2);
        }
        .product-card.active-secondary {
            border-color: var(--moya-secondary);
            box-shadow: 0 6px 15px rgba(0, 191, 255, 0.2);
        }

        .qty-input { 
            width: 70px; 
            text-align: center; 
            border-color: #ced4da;
        }
        .qty-input:focus {
            box-shadow: none;
            border-color: var(--moya-primary);
        }

        .welcome-text {
            font-size: 1.1rem;
            color: #555;
        }

        .summary-card {
            background-color: #fff;
            border-top: 4px solid var(--moya-primary);
        }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h1 text-primary fw-bolder">Order Mineral Water</h1>
                <div class="d-flex align-items-center">
                    <p class="mb-0 me-3 welcome-text">Welcome, <strong><?php echo $user_name; ?></strong>!</p>
                    <a href="profile.php" class="btn btn-outline-primary me-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16" class="me-1">
                            <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>
                            <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.52 10.515 4.983 10 8 10s4.48 0.515 5.468 1.37A7 7 0 0 0 8 1"/>
                        </svg>
                        Profile
                    </a>
                    <a href="home.php" class="btn btn-primary">Home</a>
                </div>
            </div>

            <p class="lead text-muted mb-4">You are ordering for delivery to <strong><?php echo $user_barangay; ?></strong>. Select your quantities below.</p>

            <form id="orderForm" action="place_order.php" method="POST">
                <input type="hidden" name="total_amount" id="totalAmountInput" value="0.00">
                <input type="hidden" name="quantity" id="quantityInput" value="0">

                <input type="hidden" name="refill_round_id" value="<?php echo $id_refill_round; ?>">
                <input type="hidden" name="refill_slim_id" value="<?php echo $id_refill_slim; ?>">
                <input type="hidden" name="new_round_id" value="<?php echo $id_new_round; ?>">
                <input type="hidden" name="new_slim_id" value="<?php echo $id_new_slim; ?>">
                <h3 class="text-primary fw-bold mb-3">Water Refills</h3>
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card p-4 rounded-4 product-card card-shadow" id="cardRefillRound">
                            <div class="d-flex align-items-center">
                                <h2 class="h4 fw-bold mb-0 me-auto">Standard Round Refill</h2>
                                <span class="badge bg-primary fs-5 fw-bold">₱<?php echo number_format($refill_price, 2); ?></span>
                            </div>
                            <p class="text-muted mb-3">For your existing Standard Round 5-gallon container.</p>
                            
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="input-group input-group-sm w-auto">
                                    <button class="btn btn-outline-primary btn-minus" type="button" data-product="refill_round">-</button>
                                    <input type="number" class="form-control qty-input" name="refill_round_qty" id="refill_round_qty" value="0" min="0" readonly>
                                    <button class="btn btn-outline-primary btn-plus" type="button" data-product="refill_round">+</button>
                                </div>
                                <span class="text-primary fw-semibold">Quantity</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card p-4 rounded-4 product-card card-shadow" id="cardRefillSlim">
                            <div class="d-flex align-items-center">
                                <h2 class="h4 fw-bold mb-0 me-auto">Slim Container Refill</h2>
                                <span class="badge bg-primary fs-5 fw-bold">₱<?php echo number_format($refill_price, 2); ?></span>
                            </div>
                            <p class="text-muted mb-3">For your existing Slim Container with Faucet.</p>
                            
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="input-group input-group-sm w-auto">
                                    <button class="btn btn-outline-primary btn-minus" type="button" data-product="refill_slim">-</button>
                                    <input type="number" class="form-control qty-input" name="refill_slim_qty" id="refill_slim_qty" value="0" min="0" readonly>
                                    <button class="btn btn-outline-primary btn-plus" type="button" data-product="refill_slim">+</button>
                                </div>
                                <span class="text-primary fw-semibold">Quantity</span>
                            </div>
                        </div>
                    </div>
                </div>

                <h3 class="text-primary fw-bold mt-3 mb-3">New Containers</h3>
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card p-4 rounded-4 product-card card-shadow" id="cardNewRound">
                            <div class="d-flex align-items-center">
                                <h2 class="h4 fw-bold mb-0 me-auto">New Standard Round</h2>
                                <span class="badge bg-secondary fs-5 fw-bold">₱<?php echo number_format($new_container_price, 2); ?></span>
                            </div>
                            <p class="text-muted mb-3">A new 5-gallon Round container, with its first water refill.</p>
                            
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="input-group input-group-sm w-auto">
                                    <button class="btn btn-outline-secondary btn-minus" type="button" data-product="new_round">-</button>
                                    <input type="number" class="form-control qty-input" name="new_round_qty" id="new_round_qty" value="0" min="0" readonly>
                                    <button class="btn btn-outline-secondary btn-plus" type="button" data-product="new_round">+</button>
                                </div>
                                <span class="text-secondary fw-semibold">Quantity</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card p-4 rounded-4 product-card card-shadow" id="cardNewSlim">
                            <div class="d-flex align-items-center">
                                <h2 class="h4 fw-bold mb-0 me-auto">New Slim Container</h2>
                                <span class="badge bg-secondary fs-5 fw-bold">₱<?php echo number_format($new_container_price, 2); ?></span>
                            </div>
                            <p class="text-muted mb-3">A new Slim Container with Faucet, with its first water refill.</p>
                            
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="input-group input-group-sm w-auto">
                                    <button class="btn btn-outline-secondary btn-minus" type="button" data-product="new_slim">-</button>
                                    <input type="number" class="form-control qty-input" name="new_slim_qty" id="new_slim_qty" value="0" min="0" readonly>
                                    <button class="btn btn-outline-secondary btn-plus" type="button" data-product="new_slim">+</button>
                                </div>
                                <span class="text-secondary fw-semibold">Quantity</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card p-4 rounded-4 card-shadow border-0 mt-3 summary-card">
                    <h3 class="h5 fw-bold mb-3 border-bottom pb-2">Order Summary</h3>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Round Refill Total:</span>
                        <span id="refillRoundSummary" class="fw-semibold">₱0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Slim Refill Total:</span>
                        <span id="refillSlimSummary" class="fw-semibold">₱0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>New Round Total:</span>
                        <span id="newRoundSummary" class="fw-semibold">₱0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>New Slim Total:</span>
                        <span id="newSlimSummary" class="fw-semibold">₱0.00</span>
                    </div>

                    <div class="d-flex justify-content-between mb-3 pt-2 border-top border-2">
                        <span class="h4 fw-bold text-primary">GRAND TOTAL:</span>
                        <span id="grandTotal" class="h4 fw-bold text-primary">₱0.00</span>
                    </div>

                    <p class="text-success fw-medium small text-center mb-3">
                        <strong>Promo:</strong> All new containers include the first water refill for free!
                    </p>

                    <button type="submit" id="placeOrderBtn" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold" disabled>
                        Place Order (0 items)
                    </button>
                    
                    <div id="error-message" class="alert alert-danger mt-3 d-none">Please select a quantity greater than 0 for at least one item.</div>
                </div>

            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // --- DYNAMICALLY LOAD PRICES FROM PHP ---
    const REFILL_PRICE = <?php echo $refill_price; ?>;
    const NEW_CONTAINER_PRICE = <?php echo $new_container_price; ?>;
    // --- END DYNAMIC PRICES ---

    let currentTotalQuantity = 0;

    // Get all 4 quantity inputs
    const refillRoundQtyInput = document.getElementById('refill_round_qty');
    const refillSlimQtyInput = document.getElementById('refill_slim_qty');
    const newRoundQtyInput = document.getElementById('new_round_qty');
    const newSlimQtyInput = document.getElementById('new_slim_qty');

    // Get all 4 card elements
    const cardRefillRound = document.getElementById('cardRefillRound');
    const cardRefillSlim = document.getElementById('cardRefillSlim');
    const cardNewRound = document.getElementById('cardNewRound');
    const cardNewSlim = document.getElementById('cardNewSlim');

    // Get other form elements
    const placeOrderBtn = document.getElementById('placeOrderBtn');
    const totalAmountInput = document.getElementById('totalAmountInput');
    const quantityInput = document.getElementById('quantityInput');
    const errorMessage = document.getElementById('error-message');


    function updateCalculation() {
        // Read quantities from all 4 inputs
        const refillRoundQty = parseInt(refillRoundQtyInput.value) || 0;
        const refillSlimQty = parseInt(refillSlimQtyInput.value) || 0;
        const newRoundQty = parseInt(newRoundQtyInput.value) || 0;
        const newSlimQty = parseInt(newSlimQtyInput.value) || 0;
        
        // Calculate costs for all 4 items
        const refillRoundCost = refillRoundQty * REFILL_PRICE;
        const refillSlimCost = refillSlimQty * REFILL_PRICE;
        const newRoundCost = newRoundQty * NEW_CONTAINER_PRICE;
        const newSlimCost = newSlimQty * NEW_CONTAINER_PRICE;

        const grandTotal = refillRoundCost + refillSlimCost + newRoundCost + newSlimCost;
        currentTotalQuantity = refillRoundQty + refillSlimQty + newRoundQty + newSlimQty;

        // Update Summary Display
        document.getElementById('refillRoundSummary').textContent = `₱${refillRoundCost.toFixed(2)}`;
        document.getElementById('refillSlimSummary').textContent = `₱${refillSlimCost.toFixed(2)}`;
        document.getElementById('newRoundSummary').textContent = `₱${newRoundCost.toFixed(2)}`;
        document.getElementById('newSlimSummary').textContent = `₱${newSlimCost.toFixed(2)}`;
        document.getElementById('grandTotal').textContent = `₱${grandTotal.toFixed(2)}`;

        // Update hidden form submission inputs
        totalAmountInput.value = grandTotal.toFixed(2);
        quantityInput.value = currentTotalQuantity;

        // Update Card Active States
        cardRefillRound.classList.toggle('active-primary', refillRoundQty > 0);
        cardRefillSlim.classList.toggle('active-primary', refillSlimQty > 0);
        cardNewRound.classList.toggle('active-secondary', newRoundQty > 0);
        cardNewSlim.classList.toggle('active-secondary', newSlimQty > 0);

        // Update Button State
        if (currentTotalQuantity > 0) {
            placeOrderBtn.disabled = false;
            placeOrderBtn.textContent = `Place Order (${currentTotalQuantity} items) - ₱${grandTotal.toFixed(2)}`;
            errorMessage.classList.add('d-none');
        } else {
            placeOrderBtn.disabled = true;
            placeOrderBtn.textContent = `Place Order (0 items)`;
        }
    }

    // Event listeners for ALL quantity buttons
    document.querySelectorAll('.btn-plus, .btn-minus').forEach(button => {
        button.addEventListener('click', function() {
            const productType = this.getAttribute('data-product');
            let input;

            // Find the correct input field based on the button's data-product
            switch(productType) {
                case 'refill_round':
                    input = refillRoundQtyInput;
                    break;
                case 'refill_slim':
                    input = refillSlimQtyInput;
                    break;
                case 'new_round':
                    input = newRoundQtyInput;
                    break;
                case 'new_slim':
                    input = newSlimQtyInput;
                    break;
                default:
                    return; // Should not happen
            }

            if (!input) return; // Safety check

            let qty = parseInt(input.value) || 0;

            if (this.classList.contains('btn-plus')) {
                qty++;
            } else if (qty > 0) {
                qty--;
            }
            
            input.value = qty;
            
            // Recalculate everything
            updateCalculation();
        });
    });
    
    // Initial calculation on load
    updateCalculation();
</script>
</body>
</html>