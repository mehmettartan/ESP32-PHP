<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Kullanıcı bilgilerini al
$sql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo 'Could not retrieve user information.';
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account</title>
    <link rel="stylesheet" href="account.css">
    <style>
    body {
        font-family: 'Roboto', sans-serif;
        background-color: #e0e0e0;
        color: #333;
        padding: 20px;
        margin: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }

    .container {
        width: 100%;
        max-width: 400px;
        background-color: #2c2c2c;
        padding: 30px 40px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        border-radius: 12px;
        text-align: center;
    }

    h1 {
        color: #E50914;
        font-size: 2.4em;
        margin-bottom: 25px;
    }

    .profile-info {
        margin-bottom: 25px;
        text-align: left;
        display: flex;
        align-items: center;
    }

    .profile-info label {
        font-weight: 700;
        font-size: large;
        color: #ddd;
        margin-right: 10px;
        margin-bottom: 0px;
    }

    .profile-info p {
        color: #ffffff;
        font-size: 1.2em;
        font-weight: 700;
        margin: 0;
    }

    form {
        margin-bottom: 20px;
        text-align: left;
    }

    label {
        font-weight: 700;
        font-size: 1.1em;
        color: #ddd;
        margin-bottom: 8px;
        display: block;
    }

    input[type="date"] {
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        box-sizing: border-box;
        border: 1px solid #444;
        border-radius: 8px;
        background-color: #444;
        color: #ffffff;
        font-size: 1em;
        font-weight: 600;
    }

    button {
        width: 100%;
        background-color: #E50914;
        color: white;
        padding: 12px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 1.1em;
        transition: background-color 0.3s ease;
        font-weight: 700;
    }

    button:hover {
        background-color: #d40812;
    }

    a {
        display: block;
        text-decoration: none;
        color: #E50914;
        margin-top: 20px;
        font-weight: bold;
        transition: color 0.3s ease;
    }

    a:hover {
        color: #d40812;
    }
    </style>
</head>

<body>
    <div class="container">
        <h1>My Account</h1>
        <div class="profile-info">
            <label>Username:</label>
            <p><?php echo htmlspecialchars($user['username']); ?></p>
        </div>

        <!-- Veritabanına erişim butonu -->
        <form action="http://localhost/phpmyadmin/index.php?route=/database/structure&db=data" method="get"
            target="_blank">
            <button type="submit">Access Database</button>
        </form>

        <!-- Tarih aralığı seçimi ve Excel indirme butonu -->
        <form action="export_excel.php" method="post">
            <div class="form-group">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" required>
            </div>
            <div class="form-group">
                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" required>
            </div>
            <button type="submit">Download Sensor Data as CSV</button>
        </form>

        <a href="index.php">Return to Homepage</a>
    </div>
</body>

</html>