<?php
include "db.php";
include "function.php";

if (!empty($_GET['Temp']) && !empty($_GET['Humi']) && !empty($_GET['sensor_id'])) {
    // Gelen değerleri float ve integer olarak al
    $TempGet = floatval($_GET['Temp']);
    $HumiGet = floatval($_GET['Humi']);
    $BoardGet = intval($_GET['sensor_id']);

    // Float değerleri ekrana yazdır
    echo "Board ID: " . htmlspecialchars($BoardGet);
    echo "<br>";
    echo "Temperature: " . htmlspecialchars($TempGet);
    echo "<br>";
    echo "Humidity: " . htmlspecialchars($HumiGet);
    echo "<br>";

    // Prepare statement ile güvenli veri ekleme
    $stmt = $baglanti->prepare("INSERT INTO sensor_readings (temperature, humidity, sensor_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ddi", $TempGet, $HumiGet, $BoardGet); // 'd' = double, 'i' = integer

    if ($stmt->execute()) {
        echo "Data successfully inserted.";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Temperature, Humidity, and Board ID values are required.";
}

// Veritabanından verileri çekme
$result = getData();

if ($result) {
    while ($data = mysqli_fetch_assoc($result)) {
        echo "Temperature: " . htmlspecialchars($data["temperature"]) . "<br>";
        echo "Humidity: " . htmlspecialchars($data["humidity"]) . "<br>";
        echo "Board ID: " . htmlspecialchars($data["sensor_id"]) . "<br>";
    }
} else {
    echo "Error retrieving data: " . mysqli_error($baglanti);
}

$baglanti->close();
?>