<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Tarih aralığı kontrolü
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';

if ($start_date == '' || $end_date == '') {
    echo "Please select a valid date range.";
    exit();
}

// Veritabanından belirtilen tarih aralığındaki verileri çekin
$sql = "SELECT * FROM sensor_readings WHERE reading_time BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // CSV dosyasını indirmek için başlıklar
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="sensor_readings_".$start_date."_to_".$end_date.".csv"');
    
    // Dosya çıktısı için dosya işaretçisi oluşturun
    $output = fopen('php://output', 'w');
    
    // CSV başlıklarını yazın
    fputcsv($output, array('ID', 'Sensor ID', 'Temperature (°C)', 'Humidity (%)', 'Reading Time'));
    
    // Verileri yazın
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    
    // Dosyayı kapatın
    fclose($output);
    exit;
} else {
    echo "No data found for the selected date range.";
}
?>
