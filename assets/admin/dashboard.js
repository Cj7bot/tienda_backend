// assets/admin/dashboard.js

document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.dashboard-container');
    if (!container) {
        console.error('Dashboard container not found');
        return;
    }

    const mercureUrl = container.dataset.mercureUrl;
    const mercureJwt = container.dataset.mercureJwt;

    if (!mercureUrl || !mercureJwt) {
        console.error('Mercure URL or JWT not found in data attributes.');
        return;
    }

    console.log('Connecting to Mercure Hub:', mercureUrl);

    const eventSource = new EventSource(mercureUrl, {
        headers: {
            'Authorization': `Bearer ${mercureJwt}`
        }
    });

    eventSource.onmessage = event => {
        console.log('New Mercure event received:', event.data);
        const data = JSON.parse(event.data);

        // 1. Actualizar la tarjeta "Real-Time"
        const realtimeCardValue = document.querySelector('.realtime-card .metric-value');
        if (realtimeCardValue) {
            // Asumimos que el valor actual es un número. Lo leemos, sumamos 1 y lo volvemos a poner.
            let currentValue = parseInt(realtimeCardValue.textContent) || 0;
            // Esto es un ejemplo, podrías querer sumar el total de la venta, etc.
            realtimeCardValue.textContent = currentValue + 1;
        }

        // 2. Actualizar la gráfica en tiempo real
        // Buscamos la instancia de la gráfica que ya fue creada en el HTML
        const realtimeChartInstance = Chart.getChart('realtimeChart');
        if (realtimeChartInstance) {
            const now = new Date();
            const label = `${now.getHours()}:${now.getMinutes().toString().padStart(2, '0')}`;
            const newDataPoint = parseFloat(data.total) || 0;

            // Añadir el nuevo dato al dataset de 'Compras'
            realtimeChartInstance.data.labels.push(label);
            realtimeChartInstance.data.datasets[0].data.push(newDataPoint);

            // Para mantener la gráfica limpia, podemos limitar el número de puntos a mostrar
            const maxDataPoints = 20;
            if (realtimeChartInstance.data.labels.length > maxDataPoints) {
                realtimeChartInstance.data.labels.shift();
                realtimeChartInstance.data.datasets[0].data.shift();
            }

            realtimeChartInstance.update();
        }
    };

    eventSource.onerror = (error) => {
        console.error('Mercure EventSource failed:', error);
        // Puedes intentar reconectar aquí si es necesario
        eventSource.close();
    };
});
