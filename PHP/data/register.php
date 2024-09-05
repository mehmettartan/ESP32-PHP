<?php include 'config.php'; ?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="register.css">
</head>

<body>
    <div class="logo-wrapper">
        <a href="index.php">
            <img class="logo" src="images/alp.png" alt="alpNEXT logo" />
        </a>
    </div>
    <div class="register-container">
        <div class="register-header">
            <h2>Register</h2>
        </div>
        <br>
        
        <!-- Kayıt Formu -->
        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="username">Username :</label>
                <input type="text" id="username" name="username" required placeholder="Username">
            </div>
            <div class="form-group">
                <label for="password">Password :</label>
                <input type="password" id="password" name="password" required placeholder="Password">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password :</label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm Password">
            </div>
            <button type="submit" class="register-btn">Register</button>
        </form>

        <div class="signup">
            <br>
            <span>Do you have an account?</span>
            <a href="login.php">Login now.</a>
        </div>
    </div>

    <?php

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Şifre doğrulaması
        if ($password !== $confirm_password) {
            echo "Passwords do not match!";
            exit();
        }



        // Kullanıcıyı veritabanına kaydet
        $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $password);

        if ($stmt->execute()) {
            echo "Registration successful!";
            header("Location: login.php"); // Kayıt başarılı olunca login sayfasına yönlendir
            exit();
        } else {
            echo "An error occurred. Please try again.";
        }
    }
    ?>

</body>
</html>
