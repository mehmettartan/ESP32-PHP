document.addEventListener('DOMContentLoaded', function() {
    const gauges = [
        {inputId: 'temperature-input-1', valueId: 'temperature-value-1', isTemperature: true, area: '1'},
        {inputId: 'temperature-input-2', valueId: 'temperature-value-2', isTemperature: true, area: '2'},
        {inputId: 'temperature-input-3', valueId: 'temperature-value-3', isTemperature: true, area: '3'},
        {inputId: 'temperature-input-4', valueId: 'temperature-value-4', isTemperature: true, area: '4'},
        {inputId: 'temperature-input-5', valueId: 'temperature-value-5', isTemperature: true, area: '5'},
        {inputId: 'temperature-input-6', valueId: 'temperature-value-6', isTemperature: true, area: '6'},
        {inputId: 'humidity-input-1', valueId: 'humidity-value-1', isTemperature: false, area: '1'},
        {inputId: 'humidity-input-2', valueId: 'humidity-value-2', isTemperature: false, area: '2'},
        {inputId: 'humidity-input-3', valueId: 'humidity-value-3', isTemperature: false, area: '3'},
        {inputId: 'humidity-input-4', valueId: 'humidity-value-4', isTemperature: false, area: '4'},
        {inputId: 'humidity-input-5', valueId: 'humidity-value-5', isTemperature: false, area: '5'},
        {inputId: 'humidity-input-6', valueId: 'humidity-value-6', isTemperature: false, area: '6'}
    ];

    // Veritabanından veri çekmek için AJAX kullanımı
    function fetchData(sensorId, callback) {
        fetch(`fetch_data.php?sensor_id=${sensorId}`)
            .then(response => response.json())
            .then(data => {
                console.log(data);  // JSON verisini kontrol etmek için
                callback(data);
            })
            .catch(error => console.error('Error fetching data:', error));
    }

    // Gauge değerlerini güncelleme fonksiyonu
    function updateGauge(gauge, value) {
        const primaryColor = gauge.isTemperature ? "#FF2400" : "blue";
        const secondaryColor = "black";
        const primaryValue = value * 3.6;
        const secondaryValue = 360 - primaryValue;

        const gaugeFill = document
          .getElementById(gauge.inputId)
          .closest(".gauge-container")
          .querySelector(".gauge-fill");
        gaugeFill.style.background = `conic-gradient(${primaryColor} 0deg, ${primaryColor} ${primaryValue}deg, ${secondaryColor} ${primaryValue}deg, ${secondaryColor} 360deg)`;
    }

    // Her gauge için veritabanından veri çekip gösterme
    gauges.forEach(gauge => {
        fetchData(gauge.area, function(data) {
            const latestData = data[0]; // En son veriyi al
            if (latestData) {
                const valueElement = document.getElementById(gauge.valueId);
                const value = gauge.isTemperature ? latestData.temperature : latestData.humidity;
                valueElement.textContent = gauge.isTemperature ? `${value}°C` : `${value}%`;
                updateGauge(gauge, value);
            } else {
                console.error('No data available for sensor:', gauge.area);
            }
        });
    });
});
