<?php
    $server = "localhost";
    $username = "root";
    $password = "";
    $database = "data";

    $baglanti = mysqli_connect($server, $username, $password, $database);
    mysqli_set_charset($baglanti, "UTF8");

    if (mysqli_connect_errno() > 0)
    {
        die("error: ".mysqli_connect_errno());
    }
?>