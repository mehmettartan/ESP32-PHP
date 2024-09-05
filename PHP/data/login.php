<?php include 'db.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="login.css">
</head>

<body>
    <div class="logo-wrapper">
        <a href="index.php">
            <img class="logo" src="images/alp.png" alt="alpNEXT logo" />
        </a>
    </div>
    <div class="login-container">
        <div class="login-header">
            <h2>Login</h2>
        </div>
        <br>

        <!-- Giriş Formu -->
        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required placeholder="Username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Password" minlength="3" maxlength="60">
            </div>
            <button type="submit" class="login-btn">Login</button>
        </form>

        <div class="signup">
            <br>
            <span>Don't have an account?</span>
            <a href="register.php">Register now.</a>
        </div>
    </div>

    <?php

    session_start();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Kullanıcı adı ve şifreyi veritabanında kontrol et
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $db_password);
            $stmt->fetch();

            // Düz metin karşılaştırma (password_verify yerine direkt karşılaştırma)
            if ($password === $db_password) {
                $_SESSION['user_id'] = $id;
                header("Location: index.php");
                exit();
            } else {
                echo "Incorrect password.";
            }
        } else {
            echo "No user found with that username.";
        }
    }
    ?>

</body>
</html>
