// assets/admin/dashboard.js
import { Chart } from 'chart.js/auto';

document.addEventListener('DOMContentLoaded', function() {
    // Device Chart (Mobile/Desktop)
    const deviceCtx = document.getElementById('deviceChart');
    if (deviceCtx) {
        new Chart(deviceCtx, {
            type: 'bar',
            data: {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                datasets: [{
                    data: [65, 78, 85, 92, 88, 95, 102, 110, 98, 115, 125, 138],
                    backgroundColor: '#4F46E5',
                    borderRadius: 4,
                    barPercentage: 0.6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { display: false },
                    y: { display: false }
                }
            }
        });
    }

    // Realtime Chart
    const realtimeCtx = document.getElementById('realtimeChart');
    let realtimeChart = null;
    
    if (realtimeCtx) {
        realtimeChart = new Chart(realtimeCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Compras',
                    data: [],
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#10B981'
                }, {
                    label: 'Productos Añadidos',
                    data: [],
                    borderColor: '#F59E0B',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#F59E0B'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            color: '#ffffff'
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    x: {
                        display: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            maxTicksLimit: 8,
                            color: '#9ca3af'
                        }
                    },
                    y: {
                        display: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        beginAtZero: true,
                        ticks: {
                            color: '#9ca3af'
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }

    // Source Chart (Doughnut)
    const sourceCtx = document.getElementById('sourceChart');
    if (sourceCtx) {
        new Chart(sourceCtx, {
            type: 'doughnut',
            data: {
                labels: ['Direct', 'Social', 'Email', 'Organic', 'Paid'],
                datasets: [{
                    data: [35, 25, 20, 15, 5],
                    backgroundColor: ['#4F46E5', '#06B6D4', '#10B981', '#F59E0B', '#EF4444'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        display: true,
                        position: 'bottom',
                        labels: {
                            color: '#ffffff',
                            padding: 15,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    }

    // Function to load real-time data
    window.loadRealtimeData = function() {
        fetch('/api/analytics/realtime-data')
            .then(response => response.json())
            .then(data => {
                if (data.success && realtimeChart) {
                    realtimeChart.data.labels = data.data.labels;
                    realtimeChart.data.datasets[0].data = data.data.compras;
                    realtimeChart.data.datasets[1].data = data.data.productos_creados;
                    realtimeChart.update('none');
                }
            })
            .catch(error => {
                console.error('Error loading real-time data:', error);
            });
    };

    // Function to simulate adding new events
    window.simulateNewEvent = function() {
        fetch('/api/analytics/add-event', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    setTimeout(window.loadRealtimeData, 1000);
                }
            })
            .catch(error => {
                console.error('Error adding event:', error);
            });
    };

    // Load initial data
    if (realtimeChart) {
        window.loadRealtimeData();
    }

    // Mercure integration
    const container = document.querySelector('.dashboard-container');
    if (container) {
        const mercureUrl = container.dataset.mercureUrl;
        const mercureJwt = container.dataset.mercureJwt;

        if (mercureUrl && mercureJwt) {
            const url = new URL(mercureUrl);
            url.searchParams.append('topic', 'https://tiendabackend.com/analytics');

            console.log('Connecting to Mercure Hub with topic:', url.toString());

            const eventSource = new EventSource(url, {
                withCredentials: true
            });

            eventSource.onmessage = event => {
                console.log('New Mercure event received:', event.data);
                const data = JSON.parse(event.data);

                // Actualizar la tarjeta "Real-Time"
                const realtimeCardValue = document.querySelector('.realtime-card .metric-value');
                if (realtimeCardValue) {
                    let currentValue = parseInt(realtimeCardValue.textContent) || 0;
                    realtimeCardValue.textContent = currentValue + 1;
                }

                // Actualizar la gráfica en tiempo real
                if (realtimeChart) {
                    window.loadRealtimeData();
                }
            };

            eventSource.onerror = (error) => {
                console.error('Mercure EventSource failed:', error);
                eventSource.close();
            };
        }
    }
});
