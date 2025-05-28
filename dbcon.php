<?php
// dbcon.php
// Specifies the database connection parameters.

// --- Database Configuration ---
$servername = "localhost"; // Usually 'localhost' if the database is on the same server.
$username = "root";        // Your MySQL username (default is often 'root' for local development).
$password = "";            // Your MySQL password (default is often empty for local development).
$dbname = "pantri_arif_cleaned"; // The name of your database.

// --- Create Connection ---
// Create a new mysqli object to establish a connection.
$conn = new mysqli($servername, $username, $password, $dbname);

// --- Check Connection ---
// Check if the connection was successful.
if ($conn->connect_error) {
    // If connection fails, terminate the script and display an error message.
    // It's generally better to log errors than to die() in a production environment,
    // but for simplicity in development, die() is used here.
    die("Connection failed: " . $conn->connect_error);
}

// --- Set Character Set ---
// It's good practice to set the character set to utf8mb4 for broader character support.
if (!$conn->set_charset("utf8mb4")) {
    // If setting charset fails, you might want to log this error.
    // For now, we'll print a message if in a debugging mode.
    // printf("Error loading character set utf8mb4: %s\n", $conn->error);
}

// The $conn object will now be available for use in any script that includes this file.
// Example: include 'dbcon.php';
?>