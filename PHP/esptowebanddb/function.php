<?php
    function getData()
    {
        include "db.php";

        $query = "SELECT * from sensor_readings ORDER BY id";
        $result = mysqli_query($baglanti, $query);
        mysqli_close($baglanti);
        return $result;
    }
?>