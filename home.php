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
// --- GET USER'S BARANGAY FROM SESSION ---
$user_barangay = htmlspecialchars($_SESSION["address_barangay"]);
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome, <?php echo $user_name; ?> | Moya Water Delivery</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

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
        /* ... (all your other styles) ... */
        .hero-bg {
            background-image: linear-gradient(to bottom right, #ffffff, var(--moya-bg));
        }
        .btn-cta {
            background-color: #007bff; /* Primary Blue */
            border-color: #007bff;
            color: #fff !important;
            font-weight: 700;
            padding: .75rem 2rem;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
            transition: all 0.3s ease;
        }

        .btn-cta:hover {
            background-color: #0056b3; /* Darker blue on hover */
            border-color: #0056b3;
            color: #fff !important; /* Keep text white on hover */
            box-shadow: 0 10px 20px rgba(0, 123, 255, 0.5) !important;
            transform: translateY(-2px);
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
        #location-check-map {
            height: 450px;
            width: 100%;
            border-radius: 0.5rem;
        }
    </style>
</head>

<body>
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
    <section id="products" class="py-5 bg-white">
        <div class="container">
            <h2 class="display-6 fw-bold text-center mb-2">Our Gallon Options</h2>
            <p class="text-center text-secondary mb-5 mx-auto" style="max-width: 700px;">
                Choose your preferred container and order directly — no need to log in again.
            </p>
            <div class="row g-4 justify-content-center">
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
    <section id="process" class="py-5 py-xl-10" style="background-color: #e9f2ff;">
        <div class="container">
            <h2 class="display-6 fw-bold text-center mb-5" style="color: var(--moya-primary) !important;">Delivery Promise & Schedule</h2>
            <div class="row g-4 text-center">
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

    <section id="location" class="py-5 py-xl-10">
        <div class="container">
            <div class="row align-items-center gx-5">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h2 class="display-6 fw-bold mb-3">Service Area: Rosario, La Union</h2>
                    <p class="lead text-secondary mb-4">
                        We proudly serve our local community in <b>Barangay Cataguingtingan</b> and surrounding <b>In-Town
                        areas</b> only. Check the map for our specific delivery locations.
                    </p>
                    <button onclick="openLocationCheckModal()" class="btn btn-primary rounded-pill fw-semibold">
                        Confirm My Delivery Address
                    </button>
                </div>
                <div class="col-lg-6 h-100">
                    <div id="main-page-map" class="map-placeholder rounded-4 card-shadow" style="height: 400px;"></div>
                </div>
            </div>
        </div>
    </section>
    
    <div class="modal fade" id="locationCheckModal" tabindex="-1" aria-labelledby="locationCheckModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="locationCheckModalLabel">Checking Your Delivery Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">We are checking your registered barangay (<b><?php echo $user_barangay; ?></b>) against our service area.
                       The <span class="text-success fw-bold">green pin</span> is your barangay, and the <span class="text-primary fw-bold">blue pins</span> are our delivery points.
                    </p>
                    <div id="location-check-map" class="mb-3"></div>
                    <div id="locationStatus" class="mt-3 text-center fw-bold"></div>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <script>
        // --- GLOBAL VARIABLES ---
        
        // This is your complete list of 28 delivery points (the BLUE pins).
        const serviceAreaPoints = [
            [16.219468, 120.493981], [16.213475, 120.501831], [16.225049, 120.503922],
            [16.246571, 120.487866], [16.239948, 120.468656], [16.228787, 120.459740],
            [16.238981, 120.455706], [16.248206, 120.453848], [16.255083, 120.469280],
            [16.265017, 120.445564], [16.224417, 120.450107], [16.220696, 120.468793],
            [16.214975, 120.468423], [16.214365, 120.491307], [16.211052, 120.484091],
            [16.236634, 120.431094], [16.234697, 120.421645], [16.232188, 120.407840],
            [16.220671, 120.412298], [16.242430, 120.404603], [16.250993, 120.429892],
            [16.266720, 120.408259], [16.281248, 120.443265], [16.268898, 120.475935],
            [16.249906, 120.495360], [16.277226, 120.489522], [16.281798, 120.488149],
            [16.214498, 120.429782]
        ];

        // --- NEW: BARANGAY COORDINATE LOOKUP TABLE ---
        //
        // !!! ---== [ ACTION REQUIRED ] ==--- !!!
        //
        // You MUST fill this table with your actual barangay names.
        // The names must EXACTLY match what is in your database.
        //
        // HOW:
        // 1. Find the name (e.g., "Nangramboan").
        // 2. Find its coordinates from the `serviceAreaPoints` list above.
        // 3. Add them to this table.
        //
        const barangayCoordinateLookup = {
            // Example:
            // "Nangramboan": [16.219468, 120.493981], 
            
            // --- TODO: Find the REAL coordinates for Cataguingtingan from the list above ---
            "Cataguingtingan": [16.239948, 120.468656], // <-- THIS IS A GUESS! Please replace it with the real one.
            
            // --- TODO: Add all your other barangays ---
            "Poblacion": [16.213475, 120.501831],         // This is a DEMO
            "Bani": [16.239948, 120.468656]               // This is a DEMO
        };
        //
        // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        //

        // --- Get the user's barangay from PHP ---
        const USER_BARANGAY_NAME = <?php echo json_encode($user_barangay); ?>;

        let modalMap; // To hold the map instance for the modal
        let userMarker; // To hold the user's location marker

        // --- INITIALIZE MAIN PAGE MAP ---
        document.addEventListener('DOMContentLoaded', function() {
            const mainMap = L.map('main-page-map').setView([16.245, 120.47], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(mainMap);

            serviceAreaPoints.forEach(coords => {
                L.marker(coords).addTo(mainMap).bindPopup('This is within our delivery area. We can deliver here!');
            });
        });

        // --- MODAL AND LOCATION CHECKING LOGIC ---
        const locationModalElement = document.getElementById('locationCheckModal');
        const locationModal = new bootstrap.Modal(locationModalElement);
        
        // This function is called by the "Confirm My Delivery Address" button
        function openLocationCheckModal() {
            locationModal.show();
        }
        
        // Initialize map AND run check ONLY after the modal has been shown
        locationModalElement.addEventListener('shown.bs.modal', function() {
            if (!modalMap) { // Initialize map only once
                modalMap = L.map('location-check-map').setView([16.245, 120.47], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(modalMap);
                
                // Add all BLUE service area markers to the modal map
                serviceAreaPoints.forEach(coords => {
                    L.marker(coords).addTo(modalMap);
                });
            }
            modalMap.invalidateSize(); // Fixes map sizing
            
            // --- Automatically check the user's profile barangay ---
            checkProfileBarangay();
        });

        function checkProfileBarangay() {
            const statusDiv = document.getElementById('locationStatus');
            statusDiv.className = 'mt-3 text-center fw-bold'; // Reset class
            statusDiv.innerHTML = ""; // Clear old text

            // 1. Find the coordinates for the user's registered barangay
            const userCoords = barangayCoordinateLookup[USER_BARANGAY_NAME];

            if (userCoords) {
                // 2. We found the barangay in our lookup table.
                
                // Add or move the user's GREEN marker
                if (!userMarker) {
                     userMarker = L.marker(userCoords, {
                        icon: L.icon({
                            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
                            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                            iconSize: [25, 41],
                            iconAnchor: [12, 41],
                            popupAnchor: [1, -34],
                            shadowSize: [41, 41]
                        })
                    }).addTo(modalMap);
                } else {
                    userMarker.setLatLng(userCoords);
                }
                
                modalMap.setView(userCoords, 15); // Center map on user's barangay
                userMarker.bindPopup('<b>Your Registered Barangay:</b><br>' + USER_BARANGAY_NAME).openPopup();

                // 3. Check if these coordinates are in our delivery zone
                if (isInsideDeliveryZone(userCoords)) {
                    statusDiv.innerHTML = '<span class="text-success">Great! Your registered barangay is within our delivery area.</span>';
                } else {
                    statusDiv.innerHTML = '<span class="text-danger">Sorry, your registered barangay is outside our service area.</span>';
                }

            } else {
                // 4. We could not find the barangay name in our lookup table.
                statusDiv.innerHTML = '<span class="text-danger">Sorry, we could not find your barangay "' + USER_BARANGAY_NAME + '" in our system. Please check your profile address.</span>';
            }
        }

        // This function checks if the user's coords are "close enough" to any service point
        function isInsideDeliveryZone(userCoords) {
            // This checks if the user's coordinates are *exactly* one of the service points.
            // This is safer than a radius check.
            for (const point of serviceAreaPoints) {
                if (point[0] === userCoords[0] && point[1] === userCoords[1]) {
                    return true; // The user's barangay is one of the service points
                }
            }
            return false; // Not found in the list
        }

    </script>
</body>
</html>