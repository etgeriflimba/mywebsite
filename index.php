<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config.php';

// Fungsi enkripsi dan dekripsi data
function encryptData($data, $key) {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
    return base64_encode($encrypted . '::' . base64_encode($iv));
}

function decryptData($data, $key) {
    $parts = explode('::', base64_decode($data), 2);
    if (count($parts) === 2) {
        $encrypted_data = $parts[0];
        $iv = base64_decode($parts[1]);
        if (strlen($iv) === openssl_cipher_iv_length('aes-256-cbc')) {
            return openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
        }
    }
    return null;
}

$show_users = false;

// Periksa apakah form untuk menampilkan pengguna telah disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_show_users'])) {
    $password = $_POST["password"];
    if ($password === 'Ujicoba123#') {
        $_SESSION['show_users'] = true; // Set session jika password benar
        $show_users = true; // Set variabel untuk menampilkan users
    } else {
        echo "<div class='error'>Incorrect password</div>";
    }
}

// Periksa session untuk menentukan apakah menampilkan users atau tidak
if (isset($_SESSION['show_users']) && $_SESSION['show_users'] === true) {
    $show_users = true;
}

// Proses form untuk menambah atau mengupdate data pengguna
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;
    $name = $_POST["name"];
    $email = encryptData($_POST["email"], ENCRYPTION_KEY);
    $phone = $_POST["phone"];
    $company = $_POST["company"];
    $city = $_POST["city"];
    $card_number = encryptData($_POST["card_number"], ENCRYPTION_KEY);

    if ($id) {
        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, company=?, city=?, card_number=? WHERE id=?");
        $stmt->bind_param("ssssssi", $name, $email, $phone, $company, $city, $card_number, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email, phone, company, city, card_number) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $email, $phone, $company, $city, $card_number);
    }

    if ($stmt->execute()) {
        echo "<div class='success'>Record " . ($id ? "updated" : "created") . " successfully</div>";
    } else {
        echo "<div class='error'>Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// Proses untuk menghapus pengguna
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "<div class='success'>Record deleted successfully</div>";
    } else {
        echo "<div class='error'>Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// Data untuk mengedit pengguna
$edit_data = ['id' => '', 'name' => '', 'email' => '', 'phone' => '', 'company' => '', 'city' => '', 'card_number' => ''];
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $edit_data = [
            'id' => $row['id'],
            'name' => $row['name'],
            'email' => decryptData($row['email'], ENCRYPTION_KEY),
            'phone' => $row['phone'],
            'company' => $row['company'],
            'city' => $row['city'],
            'card_number' => decryptData($row['card_number'], ENCRYPTION_KEY)
        ];
    }
    $stmt->close();
}

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

        <?php if (!$show_users): ?>
            <form method="POST" action="">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
                <input type="hidden" name="show_users" value="true">
                <button type="submit" name="submit_show_users">Show Users</button>
            </form>
        <?php else: ?>

            <form method="POST" action="">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_data['id']); ?>">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($edit_data['name']); ?>" required>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($edit_data['email']); ?>" required>
                <label for="phone">Phone:</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($edit_data['phone']); ?>" required>
                <label for="company">Company:</label>
                <input type="text" id="company" name="company" value="<?php echo htmlspecialchars($edit_data['company']); ?>" required>
                <label for="city">City:</label>
                <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($edit_data['city']); ?>" required>
                <label for="card_number">Card Number:</label>
                <input type="text" id="card_number" name="card_number" value="<?php echo htmlspecialchars($edit_data['card_number']); ?>" required>
                <button type="submit" name="submit">Submit</button>
            </form>

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
                        $decrypted_email = htmlspecialchars(decryptData($row["email"], ENCRYPTION_KEY) ?? '');
                        $decrypted_card_number = htmlspecialchars(decryptData($row["card_number"], ENCRYPTION_KEY) ?? '');
                        echo "<tr>
                                <td>" . $row["id"]. "</td>
                                <td>" . htmlspecialchars($row["name"]) . "</td>
                                <td>" . $decrypted_email . "</td>
                                <td>" . htmlspecialchars($row["phone"]) . "</td>
                                <td>" . htmlspecialchars($row["company"]) . "</td>
                                <td>" . htmlspecialchars($row["city"]) . "</td>
                                <td>" . $decrypted_card_number . "</td>
                                <td>
                                    <a href='?edit=" . $row["id"] . "'>Edit</a> |
                                    <a href='?delete=" . $row["id"] . "' onclick='return confirm(\"Are you sure you want to delete this record?\")'>Delete</a>
                                </td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='8'>No users found</td></tr>";
                }
                ?>
            </table>

        <?php endif; ?>
    </div>
</body>
</html>

<?php
$conn->close();
?>
