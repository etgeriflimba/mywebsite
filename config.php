<?php
define('ENCRYPTION_KEY', 'your-secret-key-here');

$servername = "localhost";
$username = "id22330104_ujicoba";
$password = "Ujicoba123#";
$dbname = "id22330104_ujicoba"; // 
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
