<?php
// Initialize the session and include config
session_start();
require_once "config.php"; // Assuming config.php is in the same directory

// Disable caching so "Back" won't show logged-in pages after logout
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

// Check if the user is logged in, if not then redirect to login page (index.html)
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.html");
    exit;
}

$user_id = $_SESSION["id"];
$user_name = htmlspecialchars($_SESSION["full_name"]);
$user_barangay = htmlspecialchars($_SESSION["address_barangay"]);

// --- 1. Fetch Product Prices from Database ---
$prices = [];
$sql_prices = "SELECT id, name, price FROM products WHERE name IN ('Refill', 'New Container')";

if ($result_prices = mysqli_query($conn, $sql_prices)) {
    while ($row = mysqli_fetch_assoc($result_prices)) {
        $prices[$row['name']] = [
            'id' => $row['id'],
            'price' => (float)$row['price']
        ];
    }
    mysqli_free_result($result_prices);
}

// Assign fetched prices (using defaults as fallback if DB read fails)
$refill_product_id = $prices['Refill']['id'] ?? 1;
$refill_price = $prices['Refill']['price'] ?? 20.00;

$new_container_product_id = $prices['New Container']['id'] ?? 2;
$new_container_price = $prices['New Container']['price'] ?? 120.00;

// Close database connection
mysqli_close($conn);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moya - Place Your Order</title>
    <!-- Inter Font and Bootstrap CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --moya-primary: #008080; --moya-secondary: #00bfff; }
        body { font-family: 'Inter', sans-serif; background-color: #f5fcfc; }
        .bg-primary { background-color: var(--moya-primary) !important; }
        .text-primary { color: var(--moya-primary) !important; }
        .card-shadow { box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); }
        .product-card { transition: all 0.3s ease; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1); }
        .qty-input { width: 70px; text-align: center; }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="display-5 text-primary fw-bold">Order Mineral Water</h1>
                <div class="d-flex align-items-center">
                    <p class="mb-0 me-3 text-muted">Welcome, **<?php echo $user_name; ?>**!</p>
                    <a href="profile.php" class="btn btn-outline-primary me-2">
                         <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16">
                            <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>
                            <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.52 10.515 4.983 10 8 10s4.48 0.515 5.468 1.37A7 7 0 0 0 8 1"/>
                        </svg>
                        Profile
                    </a>
                    <a href="logout.php" class="btn btn-danger">Log Out</a>
                </div>
            </div>

            <p class="lead text-muted mb-4">You are ordering for delivery to **<?php echo $user_barangay; ?>**. Select your quantity below.</p>

            <form id="orderForm" action="place_order.php" method="POST">
                <input type="hidden" name="product_id" id="productIdInput" value="<?php echo $refill_product_id; ?>"> 
                <input type="hidden" name="total_amount" id="totalAmountInput" value="0.00">
                <input type="hidden" name="quantity" id="quantityInput" value="0">

                <div class="row">
                    <!-- PRODUCT CARD: REFILL -->
                    <div class="col-md-6 mb-4">
                        <div class="card p-4 rounded-4 product-card border border-primary" id="cardRefill" onclick="selectProduct('refill')">
                            <div class="d-flex align-items-center">
                                <h2 class="h4 fw-bold mb-0 me-auto">Water Refill (Gal)</h2>
                                <span class="badge bg-primary fs-5 fw-bold">₱<?php echo number_format($refill_price, 2); ?></span>
                            </div>
                            <p class="text-muted mb-3">Bring your own empty container. This is for the water only.</p>
                            
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="input-group input-group-sm w-auto">
                                    <button class="btn btn-outline-secondary btn-minus" type="button" data-product="refill">-</button>
                                    <input type="number" class="form-control qty-input" name="refill_qty" id="refill_qty" value="0" min="0" readonly>
                                    <button class="btn btn-outline-secondary btn-plus" type="button" data-product="refill">+</button>
                                </div>
                                <span class="text-secondary fw-semibold">Quantity</span>
                            </div>
                        </div>
                    </div>

                    <!-- PRODUCT CARD: NEW CONTAINER + WATER -->
                    <div class="col-md-6 mb-4">
                        <div class="card p-4 rounded-4 product-card border" id="cardNewContainer" onclick="selectProduct('new')">
                            <div class="d-flex align-items-center">
                                <h2 class="h4 fw-bold mb-0 me-auto">New Container (Gal)</h2>
                                <span class="badge bg-secondary fs-5 fw-bold">₱<?php echo number_format($new_container_price, 2); ?></span>
                            </div>
                            <p class="text-muted mb-3">Includes a brand new 5-gallon container + the first refill.</p>
                            
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="input-group input-group-sm w-auto">
                                    <button class="btn btn-outline-secondary btn-minus" type="button" data-product="new">-</button>
                                    <input type="number" class="form-control qty-input" name="new_qty" id="new_qty" value="0" min="0" readonly>
                                    <button class="btn btn-outline-secondary btn-plus" type="button" data-product="new">+</button>
                                </div>
                                <span class="text-secondary fw-semibold">Quantity</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary and Order Button -->
                <div class="card p-4 rounded-4 card-shadow border-0 mt-3">
                    <h3 class="h5 fw-bold mb-3 border-bottom pb-2">Order Summary</h3>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Refill Total (₱<?php echo number_format($refill_price, 2); ?> each):</span>
                        <span id="refillSummary" class="fw-semibold">₱0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>New Container Total (₱<?php echo number_format($new_container_price, 2); ?> each):</span>
                        <span id="newContainerSummary" class="fw-semibold">₱0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 pt-2 border-top border-2">
                        <span class="h4 fw-bold text-primary">GRAND TOTAL:</span>
                        <span id="grandTotal" class="h4 fw-bold text-primary">₱0.00</span>
                    </div>

                    <p class="text-success fw-medium small text-center mb-3">
                        **Promo:** All new containers include the first water refill for free!
                    </p>

                    <button type="submit" id="placeOrderBtn" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold" disabled>
                        Place Order (0 items)
                    </button>
                    
                    <div id="error-message" class="alert alert-danger mt-3 d-none">Please select a product type and quantity greater than 0.</div>
                </div>

            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // --- DYNAMICALLY LOAD PRICES FROM PHP ---
    const REFILL_PRODUCT_ID = <?php echo $refill_product_id; ?>;
    const REFILL_PRICE = <?php echo $refill_price; ?>;

    const NEW_CONTAINER_PRODUCT_ID = <?php echo $new_container_product_id; ?>;
    const NEW_CONTAINER_PRICE = <?php echo $new_container_price; ?>;
    // --- END DYNAMIC PRICES ---

    let selectedProductId = null; // Stores the ID of the product being ordered
    let currentTotalQuantity = 0;

    const refillQtyInput = document.getElementById('refill_qty');
    const newQtyInput = document.getElementById('new_qty');
    const placeOrderBtn = document.getElementById('placeOrderBtn');
    const productIdInput = document.getElementById('productIdInput');
    const totalAmountInput = document.getElementById('totalAmountInput');
    const quantityInput = document.getElementById('quantityInput');
    const cardRefill = document.getElementById('cardRefill');
    const cardNewContainer = document.getElementById('cardNewContainer');
    const errorMessage = document.getElementById('error-message');


    function updateCalculation() {
        const refillQty = parseInt(refillQtyInput.value) || 0;
        const newQty = parseInt(newQtyInput.value) || 0;
        
        // Calculate costs
        const refillCost = refillQty * REFILL_PRICE;
        const newContainerCost = newQty * NEW_CONTAINER_PRICE; 

        const grandTotal = refillCost + newContainerCost;
        currentTotalQuantity = refillQty + newQty;

        // Update Summary Display
        document.getElementById('refillSummary').textContent = `₱${refillCost.toFixed(2)}`;
        document.getElementById('newContainerSummary').textContent = `₱${newContainerCost.toFixed(2)}`;
        document.getElementById('grandTotal').textContent = `₱${grandTotal.toFixed(2)}`;

        // Update form submission inputs
        totalAmountInput.value = grandTotal.toFixed(2);
        quantityInput.value = currentTotalQuantity;

        // Determine which product ID to submit (Refill or New Container)
        // For simplicity in this structure, we only allow ordering one type per transaction.
        // If both are present, we prioritize Refill for submission as it's the most common transaction.
        if (refillQty > 0) {
            selectedProductId = REFILL_PRODUCT_ID;
            productIdInput.value = REFILL_PRODUCT_ID;
        } else if (newQty > 0) {
            selectedProductId = NEW_CONTAINER_PRODUCT_ID;
            productIdInput.value = NEW_CONTAINER_PRODUCT_ID;
        } else {
            selectedProductId = null;
            productIdInput.value = ""; // Clear if nothing is selected
        }

        // Update Button State
        if (currentTotalQuantity > 0) {
            placeOrderBtn.disabled = false;
            placeOrderBtn.textContent = `Place Order (${currentTotalQuantity} gal) - ₱${grandTotal.toFixed(2)}`;
            errorMessage.classList.add('d-none');
        } else {
            placeOrderBtn.disabled = true;
            placeOrderBtn.textContent = `Place Order (0 items)`;
        }
    }

    function selectProduct(type) {
        // Simple logic: if a card is clicked, ensure its quantity is at least 1,
        // and reset the other to zero, enforcing single-product ordering per transaction.
        if (type === 'refill') {
            if (parseInt(refillQtyInput.value) === 0) {
                refillQtyInput.value = 1;
            }
            newQtyInput.value = 0;
            cardRefill.classList.add('border-primary');
            cardNewContainer.classList.remove('border-primary');
        } else { // type === 'new'
            if (parseInt(newQtyInput.value) === 0) {
                newQtyInput.value = 1;
            }
            refillQtyInput.value = 0;
            cardNewContainer.classList.add('border-primary');
            cardRefill.classList.remove('border-primary');
        }
        updateCalculation();
    }


    // Event listeners for quantity buttons
    document.querySelectorAll('.btn-plus, .btn-minus').forEach(button => {
        button.addEventListener('click', function() {
            const productType = this.getAttribute('data-product');
            const input = productType === 'refill' ? refillQtyInput : newQtyInput;
            let qty = parseInt(input.value) || 0;

            if (this.classList.contains('btn-plus')) {
                qty++;
            } else if (qty > 0) {
                qty--;
            }
            
            // Enforce single-product ordering by resetting the other if the current one is incremented
            if (productType === 'refill' && qty > 0) {
                newQtyInput.value = 0;
                cardRefill.classList.add('border-primary');
                cardNewContainer.classList.remove('border-primary');
            } else if (productType === 'new' && qty > 0) {
                refillQtyInput.value = 0;
                cardNewContainer.classList.add('border-primary');
                cardRefill.classList.remove('border-primary');
            }
            
            input.value = qty;
            updateCalculation();
        });
    });
    
    // Initial calculation on load
    updateCalculation();
</script>
</body>
</html>
