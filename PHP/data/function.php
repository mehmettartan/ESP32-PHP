<?php
function getData() {
    include "db.php";

    $query = "SELECT * from sensor_readings ORDER BY id";
    $result = mysqli_query($conn, $query);
    mysqli_close($conn);
    return $result;
}
?>