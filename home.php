<?php
session_start();
require_once "config.php";

// Disable caching so "Back" won't show logged-in pages after logout
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

// Redirect if not logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.html");
    exit;
}

$user_name = htmlspecialchars($_SESSION["full_name"]);
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome, <?php echo $user_name; ?> | Moya Water Delivery</title>

    <!-- Fonts and Bootstrap -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.5/dist/leaflet.css" integrity="sha256-sA+4RmXyFQfT5Vwy3Q8Z3FzZ1LhPQ2R9gqM1kz8A+R0=" crossorigin=""/>
    <style>
        :root {
            --moya-primary: #008080;
            --moya-secondary: #00bfff;
            --moya-cta: #ff9900;
            --moya-bg: #f5fcfc;
            --bs-primary: var(--moya-primary);
            --bs-primary-rgb: 0, 128, 128;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--moya-bg);
            color: #1f2937;
        }

        .hero-bg {
            background-image: linear-gradient(to bottom right, #ffffff, var(--moya-bg));
        }

        .btn-cta {
            background-color: var(--moya-cta);
            border-color: var(--moya-cta);
            color: #fff;
            font-weight: 700;
            padding: .75rem 2rem;
            box-shadow: 0 4px 10px rgba(255, 153, 0, 0.4);
            transition: all 0.3s ease;
        }

        .btn-cta:hover {
            background-color: #e68a00;
            border-color: #e68a00;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 153, 0, 0.6);
        }

        .card-shadow {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 10px 15px rgba(0, 0, 0, 0.03);
        }

        .product-img {
            max-width: 100%;
            height: 250px;
            object-fit: contain;
        }

        .hover-border-primary:hover {
            border-color: var(--moya-primary) !important;
        }
        #profileDropdown {
        background-color: #008080 !important;
        }

    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
        <div class="container py-2">
            <a class="navbar-brand fw-bold text-primary d-flex align-items-center" href="#">
                <svg class="me-2 text-info" width="28" height="28" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M12 2C6.477 2 2 6.477 2 12c0 3.924 2.222 7.375 5.508 9.062a1 1 0 001.037-.091l1.545-1.545a1 1 0 00-.091-1.037C7.545 17.022 6 14.73 6 12c0-3.314 2.686-6 6-6s6 2.686 6 6c0 2.73-.545 5.022-2.991 7.429a1 1 0 00-.091 1.037l1.545 1.545a1 1 0 001.037.091C19.778 19.375 22 15.924 22 12 22 6.477 17.523 2 12 2z" />
                </svg>
                Moya
            </a>
            <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto fw-semibold align-items-center">
                    <li class="nav-item"><a class="nav-link" href="#hero">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#products">Containers</a></li>
                    <li class="nav-item"><a class="nav-link" href="#process">Delivery</a></li>
                    <li class="nav-item"><a class="nav-link" href="#location">Area</a></li>

                    <!-- Profile Dropdown -->
                    <li class="nav-item dropdown ms-lg-3">
                        <a class="nav-link dropdown-toggle btn btn-primary rounded-pill px-3 text-white" href="#"
                           id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo $user_name; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="profileDropdown">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="orders.php">My Orders</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger fw-semibold" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="hero" class="hero-bg py-5 py-xl-10">
        <div class="container">
            <div class="row align-items-center gx-5">
                <div class="col-md-7 text-center text-md-start pt-5">
                    <h1 class="display-5 fw-bold lh-1 mb-3">
                        Welcome back, <span class="text-primary"><?php echo $user_name; ?>!</span>
                    </h1>
                    <p class="lead text-secondary mb-4" style="max-width: 600px;">
                        Ready to order? Enjoy fast delivery of premium mineral water in <b>Rosario, La Union</b> — straight to your door in 1–2 hours.
                    </p>
                    <div class="d-grid d-sm-flex gap-3 justify-content-center justify-content-md-start">
                        <a href="order.php" class="btn btn-cta btn-lg rounded-pill shadow-lg">Place an Order</a>
                        <a href="#products" class="btn btn-outline-primary btn-lg rounded-pill fw-bold">View Containers</a>
                    </div>
                </div>
                <div class="col-md-5 text-center">
                    <img src="img/delivery.png" class="img-fluid rounded-4 shadow-lg"
                        alt="Moya Delivery Van">
                </div>
            </div>
        </div>
    </section>

    <!-- Product Section -->
    <section id="products" class="py-5 bg-white">
        <div class="container">
            <h2 class="display-6 fw-bold text-center mb-2">Our Gallon Options</h2>
            <p class="text-center text-secondary mb-5 mx-auto" style="max-width: 700px;">
                Choose your preferred container and order directly — no need to log in again.
            </p>

            <div class="row g-4 justify-content-center">
                <!-- Product 1 -->
                <div class="col-md-5">
                    <div class="card p-4 rounded-4 card-shadow text-center h-100 hover-border-primary">
                        <img src="img/round-water-jug.png" class="mx-auto mb-3 product-img" alt="Round Jug">
                        <h3 class="h4 fw-semibold mb-2">Standard Round Container</h3>
                        <p class="text-muted mb-3">Traditional and robust 5-gallon container.</p>
                        <p class="h5 fw-bold text-primary mb-0">₱20.00 per refill</p>
                        <a href="order.php?item=round" class="btn btn-primary rounded-pill fw-semibold shadow-sm w-100 mt-4">
                            Order Now
                        </a>
                    </div>
                </div>

                <!-- Product 2 -->
                <div class="col-md-5">
                    <div class="card p-4 rounded-4 card-shadow text-center h-100 hover-border-primary">
                        <img src="img/slim-water-gallon.jpg" class="mx-auto mb-3 product-img" alt="Slim Jug">
                        <h3 class="h4 fw-semibold mb-2">Slim Container with Faucet</h3>
                        <p class="text-muted mb-3">Space-saving and easy to use.</p>
                        <p class="h5 fw-bold text-primary mb-0">₱20.00 per refill</p>
                        <a href="order.php?item=slim" class="btn btn-primary rounded-pill fw-semibold shadow-sm w-100 mt-4">
                            Order Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Delivery Process & Schedule Section -->
    <section id="process" class="py-5 py-xl-10" style="background-color: #e9f2ff;">
        <div class="container">
            <h2 class="display-6 fw-bold text-center mb-5" style="color: var(--moya-primary) !important;">Delivery Promise & Schedule</h2>
            <div class="row g-4 text-center">

                <!-- Step 1: Schedule -->
                <div class="col-md-4">
                    <div class="p-4 bg-white rounded-4 card-shadow h-100">
                        <svg class="mb-3 text-primary" width="48" height="48" fill="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zM11 15h2v2h-2zm0-8h2v6h-2z" />
                        </svg>
                        <h3 class="h5 fw-bold text-gray-800">Operating Hours</h3>
                        <p class="text-secondary mb-0">
                            <b>8:00 AM - 4:00 PM</b> daily. <br>
                            <b>Last orders accepted at 4:00 PM.</b>
                        </p>
                    </div>
                </div>

                <!-- Step 2: Delivery Speed -->
                <div class="col-md-4">
                    <div class="p-4 bg-white rounded-4 card-shadow h-100">
                        <svg class="mb-3 text-cta" width="48" height="48" fill="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M21 13v-2h-3V8h-2v3h-2v-3h-2v3h-2v-3H8v5h2v-3h2v3h2v-3h2v3h3zm-8-5h2V6h-2v2zm-4 0h2V6H9v2zm8 0h2V6h-2v2z" />
                            <path
                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" />
                        </svg>
                        <h3 class="h5 fw-bold text-gray-800">Guaranteed Fast Delivery</h3>
                        <p class="text-secondary mb-0">
                            Delivery is <b>within the day</b>, typically arriving <b>1-2 hours</b> after your order is
                            confirmed. <br>
                            <b>Last delivery run ends at 5:00 PM.</b>
                        </p>
                    </div>
                </div>

                <!-- Step 3: Payment -->
                <div class="col-md-4">
                    <div class="p-4 bg-white rounded-4 card-shadow h-100">
                        <svg class="mb-3 text-success" width="48" height="48" fill="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M21 6H3c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm0 10H3V8h18v8zm-5-3c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z" />
                        </svg>
                        <h3 class="h5 fw-bold text-gray-800">Payment Method</h3>
                        <p class="text-secondary mb-0">
                            We accept <b>Cash on Delivery (COD)</b> only. Please prepare the exact amount for a smooth
                            transaction.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Service Area Section (Map Integration Placeholder) -->
    <section id="location" class="py-5 py-xl-10">
        <div class="container">
            <div class="row align-items-center gx-5">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h2 class="display-6 fw-bold mb-3">Service Area: Rosario, La Union</h2>
                    <p class="lead text-secondary mb-4">
                        We proudly serve our local community in <b>Barangay Cataguingtingan</b> and surrounding <b>In-Town
                        areas</b> only. Please check the map to ensure your location is within our service zone.
                    </p>
                    <button onclick="openLoginModal('check-location')" class="btn btn-primary rounded-pill fw-semibold">
                        Confirm My Delivery Address
                    </button>
                    <p class="small text-muted mt-3"><b>Map integration will be fully functional in the backend phase.</b>
                    </p>
                </div>
                <div class="col-lg-6 h-100">
                    <!-- Map Placeholder -->
                    <div id="checkout-map" class="map-placeholder rounded-4 card-shadow" style="height: 400px;">
                        <!-- Map will appear here -->
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.5/dist/leaflet.js" integrity="sha256-VL+0+rrJbMcYDf4uNlhE+FJpUkaWkjP8XjCl8dT/2eM=" crossorigin=""></script>
    <script>
        // Initialize map
        var map = L.map('checkout-map').setView([14.5995, 120.9842], 13); // Example: Manila coordinates

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);

        // Add a marker at the center
        var marker = L.marker([14.5995, 120.9842]).addTo(map)
            .bindPopup('Your selected location.')
            .openPopup();

        // Optional: Allow user to click to move the marker
        map.on('click', function(e) {
            marker.setLatLng(e.latlng)
                    .bindPopup('Selected location: ' + e.latlng.lat.toFixed(5) + ', ' + e.latlng.lng.toFixed(5))
                    .openPopup();
        });
    </script>
</body>
</html>
