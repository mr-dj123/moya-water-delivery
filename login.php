<?php
// Start the session for user authentication
session_start();

// Include the database connection configuration
require_once "config.php";

// Initialize variables
$email = $password = "";
$error_message = "";

if (!function_exists('sanitize_input')) {
    function sanitize_input($conn, $data) {
        return htmlspecialchars(mysqli_real_escape_string($conn, trim($data)));
    }
}


// Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = sanitize_input($conn, $_POST["email"] ?? '');
    $password = $_POST["password"] ?? '';

    // Basic validation
    if (empty($email)) {
        $error_message = "Please enter your email.";
    } elseif (empty($password)) {
        $error_message = "Please enter your password.";
    }

    // Attempt to process login if no basic errors
    if (empty($error_message)) {
        // Select statement to fetch the user by email
        $sql = "SELECT id, full_name, password_hash, address_barangay, is_admin FROM users WHERE email = ?";


        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = $email;

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                // Check if email exists
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $id, $full_name, $hashed_password, $address_barangay, $is_admin);
                    
                    if (mysqli_stmt_fetch($stmt)) {
                        // Verify the password
                        if (password_verify($password, $hashed_password)) {
                            // Store session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["full_name"] = $full_name;
                            $_SESSION["address_barangay"] = $address_barangay;
                            $_SESSION["is_admin"] = $is_admin;

                            // Check if the user is an admin
                            $_SESSION["is_admin"] = $is_admin; // assuming $is_admin comes from your DB table
                            if ($is_admin) {
                                header("location: admin/dashboard.php");
                            } else {
                                header("location: home.php");
                            }
                            exit;
                        } else {
                            $error_message = "The email or password you entered is incorrect.";
                        }
                    }
                } else {
                    $error_message = "The email or password you entered is incorrect.";
                }
            } else {
                $error_message = "Oops! Something went wrong with the database. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    }

    // If login failed, show the error message (for simple testing)
    if (!empty($error_message)) {
        echo "<h2>Login Failed</h2>";
        echo "<p style='color:red;'>$error_message</p>";
        echo "<p><a href='index.html'>Go back to Login</a></p>";
    }
}

// Close connection
mysqli_close($conn);
?>
