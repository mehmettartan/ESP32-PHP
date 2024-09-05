document.addEventListener("DOMContentLoaded", function () {
  // Sunucu saatini JS tarafında da almak
  const serverTime = new Date("<?= $server_time ?>");

  // Veritabanından veri çekmek için AJAX kullanımı
  function fetchData(sensorId, callback) {
    fetch(`fetch_data.php?sensor_id=${sensorId}`)
      .then(response => response.json())
      .then(data => {
        // Sunucu saatine kadar olan verileri filtreleyin
        const filteredData = data.filter(entry => new Date(entry.reading_time) <= serverTime);
        callback(filteredData);
      })
      .catch(error => console.error('Error fetching data:', error));
  }

  // Grafik Oluşturma Fonksiyonu
  function createChart(
    canvasId,
    label,
    data,
    backgroundColor,
    borderColor,
    yAxisLabel
  ) {
    var ctx = document.getElementById(canvasId).getContext("2d");
    return new Chart(ctx, {
      type: "line",
      data: {
        labels: data.map(entry => entry.reading_time), // Zaman etiketlerini kullan
        datasets: [
          {
            label: label,
            data: data.map(entry => entry[label.toLowerCase()]), // Verileri çek
            backgroundColor: backgroundColor,
            borderColor: borderColor,
            borderWidth: 1,
            fill: true,
          },
        ],
      },
      options: {
        scales: {
          x: {
            title: {
              display: true,
              text: "Time",
            },
            ticks: {
              maxTicksLimit: 24, // 24 etiket (00:00, 01:00, 02:00, ... şeklinde)
            },
          },
          y: {
            title: {
              display: true,
              text: yAxisLabel,
            },
            min: 0,
            max: 100,
            ticks: {
              stepSize: 10,
            },
          },
        },
      },
    });
  }

  // Grafiklerin oluşturulması
  fetchData(1, function(data) {
    createChart(
      "temperatureChart1",
      "Temperature",
      data,
      "rgba(255, 77, 0, 0.2)",
      "rgba(255, 77, 0, 1)",
      "Temperature (°C)"
    );
    createChart(
      "humidityChart1",
      "Humidity",
      data,
      "rgba(35, 157, 232, 0.2)",
      "rgba(35, 157, 232, 1)",
      "Humidity (%)"
    );
  });

  // Diğer sensörler için benzer fetchData ve createChart çağrılarını yapabilirsiniz
  fetchData(2, function(data) {
    createChart(
      "temperatureChart2",
      "Temperature",
      data,
      "rgba(255, 77, 0, 0.2)",
      "rgba(255, 77, 0, 1)",
      "Temperature (°C)"
    );
    createChart(
      "humidityChart2",
      "Humidity",
      data,
      "rgba(35, 157, 232, 0.2)",
      "rgba(35, 157, 232, 1)",
      "Humidity (%)"
    );
  });

  // Diğer sensörler için de benzer şekilde grafikler oluşturabilirsiniz...
});
