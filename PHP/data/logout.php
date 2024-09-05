<?php
session_start();
session_destroy();
require_once 'config.php';

echo '<div class="loading-text">Exiting, please wait...</div>';
header("Refresh: 2; url=index.php");
exit();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exiting</title>
    <style>
        /* logout.css */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f4f4f4;
            font-family: Arial, sans-serif;
        }

        .loading-text {
            font-size: 20px;
            color: #333;
        }
    </style>
</head>
<body>
</body>
</html>
