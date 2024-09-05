<?php
session_start();
include 'db.php';

date_default_timezone_set('Europe/Istanbul');
$server_time = date('Y-m-d H:i:s');

$sensor_id = '';
$start_date = '';
$end_date = '';
$results = [];

// Formun gönderilip gönderilmediğini kontrol et
if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $sensor_id = $_GET['sensor_id'];
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];

    // Sorguyu oluşturma
    $query = "SELECT * FROM sensor_readings WHERE 1=1";

    // Eğer sensor_id girildiyse sorguya ekliyoruz
    if ($sensor_id != '') {
        $query .= " AND sensor_id = '$sensor_id'";
    }

   // Tarih aralığını sorguya ekliyoruz
   if ($start_date != '' && $end_date != '') {
    $start_datetime = $start_date . " 00:00:00";
    $end_datetime = $end_date . " 23:59:59";
    $query .= " AND reading_time BETWEEN '$start_datetime' AND '$end_datetime'";
}

    // Verileri en yeni tarihten en eskiye doğru sıralama
    $query .= " ORDER BY reading_time DESC";

    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $results[] = $row;
        }
    }
}

// Mevcut uyarıları ve en güncel verileri karşılaştır
$sql_alerts = "SELECT * FROM alerts ORDER BY created_at DESC";
$result_alerts = $conn->query($sql_alerts);

$alerts = [];
if ($result_alerts->num_rows > 0) {
    while ($alert = $result_alerts->fetch_assoc()) {
        $sensor_id = $alert['sensor_id'];
        $alert_type = $alert['alert_type'];

        // Bu sensörün en güncel verisini al, isset ile kontrol et
        $current_temperature = isset($temperature_data[$sensor_id][0]) ? $temperature_data[$sensor_id][0] : null;
        $current_humidity = isset($humidity_data[$sensor_id][0]) ? $humidity_data[$sensor_id][0] : null;

        // Eğer sıcaklık/nem değeri hala kritikse, uyarıyı ekrana yazdır
        if (($alert_type == 'Temperature' && $current_temperature !== null && $current_temperature > 80) ||
            ($alert_type == 'Humidity' && $current_humidity !== null && $current_humidity > 70)) {
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
    <title>Filter</title>
    <link rel="stylesheet" href="filter.css" />
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

    <!-- CONTENT  -->
    <div class="container">
        <div class="content-wrapper">
            <div class="featured-content">
                <h1>Sensor Data Dashboard</h1>
                <form id="filter-form" method="GET" action="filter.php">
                    <div class="form-group">
                        <label for="sensor_id">Sensor ID (Optional):</label>
                        <input type="number" id="sensor_id" name="sensor_id"
                            value="<?= isset($_GET['sensor_id']) ? htmlspecialchars($_GET['sensor_id']) : ''; ?>">

                    </div>
                    <div class="form-group">
                        <label for="start_date">Start Date:</label>
                        <input type="date" id="start_date" name="start_date"
                            value="<?= htmlspecialchars($start_date) ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date:</label>
                        <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                    </div>
                    <button type="submit" class="filter-btn">Filter</button>
                </form>
                <br>
                <table>
                    <thead>
                        <tr>
                            <th>Sensor ID</th>
                            <th>Date</th>
                            <th>Hour</th>
                            <th>Temperature (°C)</th>
                            <th>Humidity (%)</th>
                        </tr>
                    </thead>
                    <tbody id="data-body">
                        <?php
                        if (!empty($results)) {
                            foreach ($results as $row) {
                                echo "<tr>";
                                echo "<td>{$row['sensor_id']}</td>";
                                echo "<td>" . date('Y-m-d', strtotime($row['reading_time'])) . "</td>";
                                echo "<td>" . date('H:i', strtotime($row['reading_time'])) . "</td>";
                                echo "<td>{$row['temperature']}°C</td>";
                                echo "<td>{$row['humidity']}%</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5'>No data found for the selected criteria.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>

            </div>
        </div>
    </div>

    <!-- Sunucu Saati ve Alert Mesajları -->
    <div class="footer">
        <p id="serverTime">Sunucu saati: <?= $server_time ?></p>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // PHP tarafından oluşturulan sunucu saatini alıyoruz
        let serverTime = new Date("<?= $server_time ?>");

        // Sunucu saatini her saniye bir saniye artıran fonksiyon
        function updateTime() {
            serverTime.setSeconds(serverTime.getSeconds() + 1);

            // Saat, dakika ve saniye değerlerini alıyoruz
            let hours = String(serverTime.getHours()).padStart(2, '0');
            let minutes = String(serverTime.getMinutes()).padStart(2, '0');
            let seconds = String(serverTime.getSeconds()).padStart(2, '0');

            // Yeni zamanı HTML'deki serverTime elementine yazıyoruz
            document.getElementById('serverTime').textContent =
                `Sunucu saati: ${serverTime.getFullYear()}-${String(serverTime.getMonth()+1).padStart(2, '0')}-${String(serverTime.getDate()).padStart(2, '0')} ${hours}:${minutes}:${seconds}`;
        }

        // updateTime fonksiyonunu her saniyede bir çalıştırıyoruz
        setInterval(updateTime, 1000);
    });

    window.addEventListener('DOMContentLoaded', function() {
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href.split("?")[0]);
        }
    });
    </script>
</body>

</html>