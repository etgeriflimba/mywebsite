<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config.php';

function encryptData($data, $key) {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
    return base64_encode($encrypted . '::' . base64_encode($iv));
}

function decryptData($data, $key) {
    $decoded_data = base64_decode($data);
    if ($decoded_data === false) {
        return null; // Return null if base64 decoding fails
    }
    $parts = explode('::', $decoded_data, 2);
    if (count($parts) === 2) {
        $encrypted_data = $parts[0];
        $iv = base64_decode($parts[1]);
        if ($iv !== false && strlen($iv) === openssl_cipher_iv_length('aes-256-cbc')) {
            $decrypted = openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
            return $decrypted !== false ? $decrypted : null; // Return decrypted data or null if decryption fails
        }
    }
    return null; // Return null if decryption fails
}

// Penambahan data
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $name = $_POST["name"]; // Name tidak dienkripsi
    $email = encryptData($_POST["email"], ENCRYPTION_KEY); // Email dienkripsi
    $phone = $_POST["phone"];
    $company = $_POST["company"];
    $city = $_POST["city"];
    $card_number = encryptData($_POST["card_number"], ENCRYPTION_KEY); // Card Number dienkripsi

    $sql = "INSERT INTO users (name, email, phone, company, city, card_number) 
            VALUES ('$name', '$email', '$phone', '$company', '$city', '$card_number')";

    if ($conn->query($sql) === TRUE) {
        echo "<div class='success'>New record created successfully</div>";
    } else {
        echo "<div class='error'>Error: " . $sql . "<br>" . $conn->error . "</div>";
    }
}

// Penghapusan data
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM users WHERE id=$id";

    if ($conn->query($sql) === TRUE) {
        echo "<div class='success'>Record deleted successfully</div>";
    } else {
        echo "<div class='error'>Error: " . $sql . "<br>" . $conn->error . "</div>";
    }
}

// Memeriksa password untuk menampilkan daftar pengguna
$show_users = false;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['show_users'])) {
    $password = $_POST["password"];
    if ($password === 'ujicoba') {  // Ganti dengan kata sandi Anda
        $_SESSION['show_users'] = true;
        $show_users = true;
    } else {
        echo "<div class='error'>Incorrect password</div>";
    }
}

if (isset($_SESSION['show_users']) && $_SESSION['show_users'] === true) {
    $show_users = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Website</title>
    <link rel="stylesheet" href="style.css"> <!-- Link ke file CSS eksternal -->
</head>
<body>
    <div class="container">
        <h1>Simple Website</h1>

        <h2>Add New User</h2>
        <form method="POST" action="">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required><br><br>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required><br><br>
            <label for="phone">Phone:</label>
            <input type="text" id="phone" name="phone"><br><br>
            <label for="company">Company:</label>
            <input type="text" id="company" name="company"><br><br>
            <label for="city">City:</label>
            <input type="text" id="city" name="city"><br><br>
            <label for="card_number">Card Number:</label>
            <input type="text" id="card_number" name="card_number" required><br><br>
            <button type="submit" name="submit">Submit</button>
        </form>

        <?php if (!$show_users): ?>
            <h2>Show Users</h2>
            <form method="POST" action="">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required><br><br>
                <button type="submit" name="show_users">Show Users</button>
            </form>
        <?php else: ?>
            <h2>Users List</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Company</th>
                    <th>City</th>
                    <th>Card Number</th>
                    <th>Action</th>
                </tr>
                <?php
                $sql = "SELECT id, name, email, phone, company, city, card_number FROM users";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        // Handle decryption only for encrypted fields
                        $decrypted_email = decryptData($row["email"], ENCRYPTION_KEY);
                        $decrypted_card_number = decryptData($row["card_number"], ENCRYPTION_KEY);

                        echo "<tr>
                                <td>" . $row["id"] . "</td>
                                <td>" . htmlspecialchars($row["name"]) . "</td>
                                <td>" . ($decrypted_email !== null ? htmlspecialchars($decrypted_email) : 'Decryption Error') . "</td>
                                <td>" . (isset($row["phone"]) ? htmlspecialchars($row["phone"]) : '') . "</td>
                                <td>" . (isset($row["company"]) ? htmlspecialchars($row["company"]) : '') . "</td>
                                <td>" . (isset($row["city"]) ? htmlspecialchars($row["city"]) : '') . "</td>
                                <td>" . ($decrypted_card_number !== null ? htmlspecialchars($decrypted_card_number) : 'Decryption Error') . "</td>
                                <td><a href='?delete=" . $row["id"] . "' onclick='return confirm(\"Are you sure you want to delete this record?\")'>Delete</a></td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='8'>No users found</td></tr>";
                }

                $conn->close();
                ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
