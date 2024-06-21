<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$encryptionKey = $_ENV['ENCRYPTION_KEY'];
if (!$encryptionKey) {
    die("Kunci enkripsi tidak diatur dalam file .env.");
}

include 'config.php';
include 'function.php';

$show_users = false;




// Penambahan data
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $name = $_POST["name"]; // Nama tidak dienkripsi
    $email = encryptData($_POST["email"], $encryptionKey); // Email dienkripsi
    $phone = $_POST["phone"];
    $company = $_POST["company"];
    $city = $_POST["city"];
    $card_number = encryptData($_POST["card_number"], $encryptionKey); // Nomor Kartu dienkripsi

    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, company, city, card_number) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $name, $email, $phone, $company, $city, $card_number);

    if ($stmt->execute()) {
        echo "<div class='success'>Data baru berhasil ditambahkan</div>";
    } else {
        echo "<div class='error'>Error: " . $stmt->error . "</div>";
    }

    $stmt->close();
}

// Penghapusan data
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "<div class='success'>Data berhasil dihapus</div>";
    } else {
        echo "<div class='error'>Error: " . $stmt->error . "</div>";
    }

    $stmt->close();
}

// Memeriksa password untuk menampilkan daftar admin
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['show_users'])) {
    $password = $_POST["password"];
    if ($password === 'ujicoba') {  
        $_SESSION['show_users'] = true;
    } else {
        echo "<div class='error'>Password salah</div>";
    }
}

if (isset($_SESSION['show_users']) && $_SESSION['show_users'] === true) {
    $show_users = true;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Sederhana</title>
    <link rel="stylesheet" href="style.css"> <!-- Link ke file CSS eksternal -->
</head>
<body>
    <div class="container">
        <h1>KEAMANAN SISTEM</h1>

        <h2>INPUT DATA</h2>
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
            <h2>DAFTAR DATA BERHASIL DIINPUT</h2>
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
                    while ($row = $result->fetch_assoc()) {
                        $decrypted_email = decryptData($row["email"], $encryptionKey);
                        $decrypted_card_number = decryptData($row["card_number"], $encryptionKey);

                        echo "<tr>
                                <td>" . $row["id"] . "</td>
                                <td>" . htmlspecialchars($row["name"]) . "</td>
                                <td>" . ($decrypted_email !== null ? htmlspecialchars($decrypted_email) : 'Decryption Error') . "</td>
                                <td>" . (isset($row["phone"]) ? htmlspecialchars($row["phone"]) : '') . "</td>
                                <td>" . (isset($row["company"]) ? htmlspecialchars($row["company"]) : '') . "</td>
                                <td>" . (isset($row["city"]) ? htmlspecialchars($row["city"]) : '') . "</td>
                                <td>" . ($decrypted_card_number !== null ? htmlspecialchars($decrypted_card_number) : 'Decryption Error') . "</td>
                                <td><a href='?delete=" . $row["id"] . "' onclick='return confirm(\"Anda yakin ingin menghapus data ini?\")'>Delete</a></td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='8'>Tidak ada pengguna ditemukan</td></tr>";
                }

                $conn->close();
                ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
