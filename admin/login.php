<?php
// Start the session and include config
session_start();
require_once "../config.php"; // Path is relative to the admin directory

// If admin is already logged in, redirect to dashboard
if (isset($_SESSION["is_admin"]) && $_SESSION["is_admin"] === true) {
    header("location: dashboard.php");
    exit;
}

$email = $password = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = sanitize_input($conn, $_POST["email"] ?? '');
    $password = $_POST["password"] ?? '';

    if (empty($email) || empty($password)) {
        $error_message = "Please enter both email and password.";
    }

    if (empty($error_message)) {
        // We assume the 'users' table has an 'is_admin' column set to 1 for administrators.
        // **IMPORTANT:** You must run an SQL ALTER TABLE command or manually update your admin account
        // to have is_admin = 1 for this to work properly.
        $sql = "SELECT id, full_name, password_hash, is_admin FROM users WHERE email = ?";

        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = $email;

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $id, $full_name, $hashed_password, $is_admin);
                    
                    if (mysqli_stmt_fetch($stmt)) {
                        // 1. Verify Password
                        if (password_verify($password, $hashed_password)) {
                            // 2. Verify Admin Status
                            if ($is_admin == 1) {
                                
                                // Success: Set session variables
                                $_SESSION["loggedin"] = true;
                                $_SESSION["is_admin"] = true;
                                $_SESSION["id"] = $id;
                                $_SESSION["full_name"] = $full_name;

                                // Redirect to admin dashboard
                                header("location: dashboard.php"); 
                                exit;
                            } else {
                                $error_message = "Access Denied: You are not authorized as an administrator.";
                            }
                        } else {
                            $error_message = "Invalid email or password.";
                        }
                    }
                } else {
                    $error_message = "Invalid email or password.";
                }
            } else {
                $error_message = "Oops! Something went wrong with the database. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moya - Admin Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --moya-primary: #008080; }
        body { font-family: 'Inter', sans-serif; background-color: #f0f8ff; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card-admin { max-width: 400px; width: 100%; box-shadow: 0 0 25px rgba(0, 0, 0, 0.1); }
        .text-primary { color: var(--moya-primary) !important; }
        .btn-primary { background-color: var(--moya-primary); border-color: var(--moya-primary); }
        .btn-primary:hover { background-color: #006666; border-color: #006666; }
    </style>
</head>
<body>

<div class="card card-admin p-4 rounded-4">
    <h1 class="text-center mb-4 text-primary fw-bold">Moya Admin Panel</h1>
    <p class="text-center text-muted mb-4">Sign in to manage orders and users.</p>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger rounded-3" role="alert">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
        <div class="mb-3">
            <label for="email" class="form-label fw-semibold">Email Address</label>
            <input type="email" class="form-control p-2 rounded-3" id="email" name="email" required>
        </div>
        <div class="mb-4">
            <label for="password" class="form-label fw-semibold">Password</label>
            <input type="password" class="form-control p-2 rounded-3" id="password" name="password" required>
        </div>
        
        <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold">Log In</button>
        <p class="text-center mt-3 small"><a href="../index.html" class="text-muted">← Go back to Customer Site</a></p>
    </form>
</div>

</body>
</html>
