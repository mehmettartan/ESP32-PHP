<?php
include 'config.php'; // Burada config.php içindeki $conn bağlantısını kullanacağız

function add_sensor_reading($sensor_id, $temperature, $humidity) {
    global $conn;

    $sql = "INSERT INTO sensor_readings (sensor_id, temperature, humidity) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("idd", $sensor_id, $temperature, $humidity);

    if ($stmt->execute()) {
        return "New record created successfully";
    } else {
        return "Error: " . $stmt->error;
    }
}

function getData() {
    global $conn;

    $query = "SELECT * FROM sensor_readings ORDER BY id";
    $result = mysqli_query($conn, $query);

    return $result;
}

function add_alert($sensor_id, $alert_type, $alert_message) {
    global $conn;

    $check_sql = "SELECT * FROM alerts WHERE sensor_id = ? AND alert_type = ? AND alert_message = ?";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("iss", $sensor_id, $alert_type, $alert_message);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows == 0) {
        $sql = "INSERT INTO alerts (sensor_id, alert_type, alert_message) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $sensor_id, $alert_type, $alert_message);

        if ($stmt->execute()) {
            return "Alert added successfully";
        } else {
            return "Error: " . $stmt->error;
        }
    } else {
        return "Alert already exists";
    }
}
?>