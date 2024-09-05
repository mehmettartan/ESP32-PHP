<?php
session_start();
include 'db.php';

date_default_timezone_set('Europe/Istanbul');
$server_time = date('Y-m-d H:i:s');

// Tüm area'lar için verileri çekmek ve ortalama hesaplamak üzere bir döngü
$temperature_data = [];
$humidity_data = [];
$time_data = [];
$average_temperature_data = [];
$average_humidity_data = [];

for ($i = 1; $i <= 6; $i++) {
    // Sunucu saatine kadar olan verileri çek
    $sql = "SELECT * FROM sensor_readings WHERE sensor_id = $i AND reading_time <= '$server_time' ORDER BY reading_time DESC LIMIT 24";
    $result = $conn->query($sql);

    $temperature_data[$i] = [];
    $humidity_data[$i] = [];
    $time_data[$i] = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $temperature_data[$i][] = $row['temperature'];
            $humidity_data[$i][] = $row['humidity'];
            $time_data[$i][] = $row['reading_time'];
        }
    }
}

// Ortalama hesaplama
for ($j = 0; $j < 24; $j++) {
    $temp_sum = 0;
    $hum_sum = 0;
    $valid_temp_count = 0;
    $valid_hum_count = 0;

    for ($i = 1; $i <= 6; $i++) {
        if (isset($temperature_data[$i][$j])) {
            $temp_sum += $temperature_data[$i][$j];
            $valid_temp_count++;
        }
        if (isset($humidity_data[$i][$j])) {
            $hum_sum += $humidity_data[$i][$j];
            $valid_hum_count++;
        }
    }

    // Sadece veri varsa ortalamayı hesapla
    $average_temperature_data[] = $valid_temp_count > 0 ? $temp_sum / $valid_temp_count : 0;
    $average_humidity_data[] = $valid_hum_count > 0 ? $hum_sum / $valid_hum_count : 0;
}

// Zaman etiketlerini almak için herhangi bir sensörün zamanını kullanabiliriz
$average_time_data = isset($time_data[1]) ? array_reverse($time_data[1]) : [];

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
    <title>Graphics</title>
    <link rel="stylesheet" href="graphics.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

    <!-- Temperature and Humidity Charts -->
    <div class="gauges">
        <?php for ($i = 1; $i <= 6; $i++): ?>
        <div class="area">
            <h2 class="area-title">AREA <?php echo $i; ?></h2>
            <div class="gauge-row">
                <!-- Temperature Chart -->
                <div class="gauge-container">
                    <label class="gauge-label-temperature">TEMPERATURE</label>
                    <canvas id="temperatureChart<?php echo $i; ?>" width="200" height="200"></canvas>
                </div>

                <!-- Humidity Chart -->
                <div class="gauge-container">
                    <label class="gauge-label-humidity">HUMIDITY</label>
                    <canvas id="humidityChart<?php echo $i; ?>" width="200" height="200"></canvas>
                </div>
            </div>
        </div>
        <?php endfor; ?>

        <!-- 7. Area - Ortalama Sıcaklık ve Nem -->
        <div class="area">
            <h2 class="area-title">AREA 7 - AVERAGE VALUES</h2>
            <div class="gauge-row">
                <!-- Average Temperature Chart -->
                <div class="gauge-container">
                    <label class="gauge-label-temperature">AVERAGE TEMPERATURE</label>
                    <canvas id="averageTemperatureChart" width="400" height="300"></canvas>
                </div>

                <!-- Average Humidity Chart -->
                <div class="gauge-container">
                    <label class="gauge-label-humidity">AVERAGE HUMIDITY</label>
                    <canvas id="averageHumidityChart" width="400" height="300"></canvas>
                </div>
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

        // Sayfanın her 5 dakikada bir yenilenmesini sağlar
        setTimeout(function() {
            location.reload();
        }, 5000); // 300000 milisaniye = 5 dakika
    });

    <?php for ($i = 1; $i <= 6; $i++): ?>
    var temperatureData<?php echo $i; ?> = <?php echo json_encode(array_reverse($temperature_data[$i])); ?>;
    var humidityData<?php echo $i; ?> = <?php echo json_encode(array_reverse($humidity_data[$i])); ?>;
    var timeData<?php echo $i; ?> = <?php echo json_encode(array_reverse($time_data[$i])); ?>;

    var ctxTemp<?php echo $i; ?> = document.getElementById('temperatureChart<?php echo $i; ?>').getContext('2d');
    var temperatureChart<?php echo $i; ?> = new Chart(ctxTemp<?php echo $i; ?>, {
        type: 'line',
        data: {
            labels: timeData<?php echo $i; ?>,
            datasets: [{
                label: 'Temperature (°C)',
                data: temperatureData<?php echo $i; ?>,
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderColor: 'rgba(255, 0, 0, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Time'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Temperature (°C)'
                    },
                    beginAtZero: false
                }
            }
        }
    });

    var ctxHum<?php echo $i; ?> = document.getElementById('humidityChart<?php echo $i; ?>').getContext('2d');
    var humidityChart<?php echo $i; ?> = new Chart(ctxHum<?php echo $i; ?>, {
        type: 'line',
        data: {
            labels: timeData<?php echo $i; ?>,
            datasets: [{
                label: 'Humidity (%)',
                data: humidityData<?php echo $i; ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(0, 0, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Time'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Humidity (%)'
                    },
                    beginAtZero: false
                }
            }
        }
    });
    <?php endfor; ?>

    // Ortalama Sıcaklık Grafiği
    var ctxAvgTemp = document.getElementById('averageTemperatureChart').getContext('2d');
    var averageTemperatureChart = new Chart(ctxAvgTemp, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($average_time_data); ?>,
            datasets: [{
                label: 'Average Temperature (°C)',
                data: <?php echo json_encode($average_temperature_data); ?>,
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderColor: 'rgba(255, 0, 0, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Time'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Average Temperature (°C)'
                    },
                    beginAtZero: false
                }
            }
        }
    });

    // Ortalama Nem Grafiği
    var ctxAvgHum = document.getElementById('averageHumidityChart').getContext('2d');
    var averageHumidityChart = new Chart(ctxAvgHum, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($average_time_data); ?>,
            datasets: [{
                label: 'Average Humidity (%)',
                data: <?php echo json_encode($average_humidity_data); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(0, 0, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Time'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Average Humidity (%)'
                    },
                    beginAtZero: false
                }
            }
        }
    });
    </script>
</body>

</html>