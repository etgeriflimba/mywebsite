<?php
//  database connection
define('DB_SERVER', 'localhost'); // Ganti dengan host database
define('DB_USERNAME', 'root'); // Ganti dengan username database 
define('DB_PASSWORD', ''); // Ganti dengan password database
define('DB_NAME', 'simple_website'); // Ganti dengan nama database

//  MySQLi connection
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

?>
