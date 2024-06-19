<?php
// Constants for database connection
define('DB_SERVER', 'localhost'); // Ganti dengan host database Anda
define('DB_USERNAME', 'id22330104_ujicoba'); // Ganti dengan username database Anda
define('DB_PASSWORD', 'Ujicoba123#'); // Ganti dengan password database Anda
define('DB_NAME', 'id22330104_ujicoba'); // Ganti dengan nama database Anda

// Create a new MySQLi connection
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
define('ENCRYPTION_KEY', 'Ujicoba123#'); // Ganti dengan kunci enkripsi Anda
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
