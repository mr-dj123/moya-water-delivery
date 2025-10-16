<?php
// Initialize the session.
session_start();

// Unset all of the session variables.
$_SESSION = array();

// Destroy the session completely.
session_destroy();

// Redirect to the homepage/login page
header("location: index.html");
exit;
?>
