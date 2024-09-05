<?php
session_start();
include 'db.php';

// Sunucu saati için İstanbul saatini ayarlıyoruz
date_default_timezone_set('Europe/Istanbul');
$server_time = date('Y-m-d H:i:s');

// Verilerin doğru alındığını kontrol edin
$sensor_id = isset($_POST['sensor_id']) ? (int)$_POST['sensor_id'] : 0;
$temperature = isset($_POST['temperature']) ? (float)$_POST['temperature'] : 0.0;
$humidity = isset($_POST['humidity']) ? (float)$_POST['humidity'] : 0.0;

// Eğer sensor_id veya diğer veriler geçerli değilse işlem yapmayın
if ($sensor_id > 0 && $temperature != 0.0 && $humidity != 0.0) {
    $sensor_result = add_sensor_reading($sensor_id, $temperature, $humidity);
    echo $sensor_result;
} else {

}
$result = getData();

$readings = array_fill(1, 6, ['temperature' => 0, 'humidity' => 0, 'reading_time' => null]);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sensor_id = $row['sensor_id'];
        if ($readings[$sensor_id]['reading_time'] === null || $readings[$sensor_id]['reading_time'] < $row['reading_time']) {
            $readings[$sensor_id] = $row;
        }
    }
}

// Uyarı ekleme işlemleri (veritabanına kaydedilecek ama sayfada gösterilmeyecek)
foreach ($readings as $sensor_id => $reading) {
    if ($reading['temperature'] > 80 || $reading['humidity'] > 70) {
        $alert_type = $reading['temperature'] > 80 ? 'Temperature' : 'Humidity';
        $alert_message = $alert_type . ' exceeded the threshold at ' . $reading['reading_time'];
        echo add_alert($sensor_id, $alert_type, $alert_message);
    }
}

$sql_alerts = "SELECT * FROM alerts ORDER BY created_at DESC";
$result_alerts = $conn->query($sql_alerts);

$alerts = [];
if ($result_alerts && $result_alerts->num_rows > 0) {
    while ($alert = $result_alerts->fetch_assoc()) {
        $sensor_id = $alert['sensor_id'];
        $alert_type = $alert['alert_type'];

        $current_reading = $readings[$sensor_id];

        if (($alert_type == 'Temperature' && $current_reading['temperature'] > 80) ||
            ($alert_type == 'Humidity' && $current_reading['humidity'] > 70)) {
            $alerts[] = "<p style='color: red; font-weight: bold;'>Alert for Sensor {$alert['sensor_id']}: {$alert['alert_type']} - {$alert['alert_message']} ({$alert['created_at']})</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage</title>
    <link rel="stylesheet" href="style.css" />
</head>

<body>
    <!-- NAVBAR -->
    <div class="navbar">
        <div class="navbar-wrapper">
            <div class="logo-wrapper">
                <a href="index.php">
                    <img class="logo" src="images/alp.png" alt="alpNEXT logo" />
                </a>
            </div>

            <!-- MENU -->
            <div class="menu-container">
                <ul class="menu-list">
                    <li class="menu-list-item"><a href="index.php">Homepage</a></li>
                    <li class="menu-list-item"><a href="graphics.php">Graphics</a></li>
                    <li class="menu-list-item"><a href="filter.php">Filter</a></li>
                </ul>
            </div>

            <!-- Profile Container -->
            <div class="profile-container">
                <div class="profile-text-container">
                    <?php if (!isset($_SESSION['user_id'])) : ?>
                    <a href="login.php" class="navbar-link" id="loginLink">Login</a>
                    <a href="register.php" class="navbar-link" id="registerLink">Register</a>
                    <?php else : ?>
                    <a href="account.php" class="navbar-link">Account</a>
                    <a href="logout.php" class="navbar-link">Exit</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Gauges - Temperature and Humidity -->
    <div class="gauges">
        <?php for ($i = 1; $i <= 6; $i++): ?>
        <div class="area">
            <h2 class="area-title">AREA <?php echo $i; ?></h2>
            <div class="gauge-row">
                <!-- Sıcaklık Göstergesi -->
                <div class="gauge-container">
                    <label class="gauge-label-temperature">TEMPERATURE</label>
                    <div class="gauge">
                        <div class="gauge-fill"
                            style="background: conic-gradient(red 0deg, red <?php echo $readings[$i]['temperature'] * 3.6; ?>deg, black <?php echo $readings[$i]['temperature'] * 3.6; ?>deg 360deg);">
                        </div>
                        <div class="gauge-cover-temperature"></div>
                    </div>
                    <div class="gauge-text">
                        <h3><?php echo $readings[$i]['temperature']; ?><span>°C</span></h3>
                    </div>
                </div>

                <!-- Nem Göstergesi -->
                <div class="gauge-container">
                    <label class="gauge-label-humidity">HUMIDITY</label>
                    <div class="gauge">
                        <div class="gauge-fill"
                            style="background: conic-gradient(blue 0deg, blue <?php echo $readings[$i]['humidity'] * 3.6; ?>deg, black <?php echo $readings[$i]['humidity'] * 3.6; ?>deg 360deg);">
                        </div>
                        <div class="gauge-cover-humidity"></div>
                    </div>
                    <div class="gauge-text">
                        <h3><?php echo $readings[$i]['humidity']; ?><span>%</span></h3>
                    </div>
                </div>
            </div>
        </div>
        <?php endfor; ?>
    </div>

    <!-- Sunucu Saati ve Alert Mesajları -->
    <div class="footer">
        <p id="serverTime">Sunucu saati: <?= $server_time ?></p>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let serverTime = new Date("<?= $server_time ?>");
        function updateTime() {
            serverTime.setSeconds(serverTime.getSeconds() + 1);
            let hours = String(serverTime.getHours()).padStart(2, '0');
            let minutes = String(serverTime.getMinutes()).padStart(2, '0');
            let seconds = String(serverTime.getSeconds()).padStart(2, '0');
            document.getElementById('serverTime').textContent =
                `Sunucu saati: ${serverTime.getFullYear()}-${String(serverTime.getMonth()+1).padStart(2, '0')}-${String(serverTime.getDate()).padStart(2, '0')} ${hours}:${minutes}:${seconds}`;
        }
        setInterval(updateTime, 1000);
        setTimeout(function() {
            location.reload();
        }, 5000);
    });
    </script>
</body>

</html>
