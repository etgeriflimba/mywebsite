<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Website</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Simple Website</h1>

        <?php
        include 'config.php';

        function encryptData($data, $key) {
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
            $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
            return base64_encode($encrypted . '::' . $iv);
        }

        function decryptData($data, $key) {
            list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
            return openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
        }

        // Menambahkan data & melakukan enkripsi pada name dan email
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
            $name = encryptData($_POST["name"], ENCRYPTION_KEY);
            $email = encryptData($_POST["email"], ENCRYPTION_KEY);

            $sql = "INSERT INTO users (name, email) VALUES ('$name', '$email')";

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

        <form method="POST" action="">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required><br><br>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required><br><br>
            <button type="submit" name="submit">Submit</button>
        </form>

        <h2>Users List</h2>
        <?php if (!$show_users): ?>
            <form method="POST" action="">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required><br><br>
                <button type="submit" name="show_users">Show Users</button>
            </form>
        <?php else: ?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Action</th>
                </tr>
                <?php
                $sql = "SELECT id, name, email FROM users";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $decrypted_name = decryptData($row["name"], ENCRYPTION_KEY);
                        $decrypted_email = decryptData($row["email"], ENCRYPTION_KEY);
                        echo "<tr>
                                <td>" . $row["id"]. "</td>
                                <td>" . htmlspecialchars($decrypted_name) . "</td>
                                <td>" . htmlspecialchars($decrypted_email) . "</td>
                                <td><a href='?delete=" . $row["id"] . "' onclick='return confirm(\"Are you sure you want to delete this record?\")'>Delete</a></td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No users found</td></tr>";
                }

                $conn->close();
                ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
