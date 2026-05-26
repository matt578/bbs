document.addEventListener('DOMContentLoaded', function () {
    const labels = Array.isArray(window.CHART_LABELS) ? window.CHART_LABELS : [];
    const data = Array.isArray(window.CHART_DATA) ? window.CHART_DATA : [];

    if (typeof Chart === 'undefined') return;

    const barCanvas = document.getElementById('barChart');
    const areaCanvas = document.getElementById('areaChart');

    if (barCanvas) {
        new Chart(barCanvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Stock',
                    data: data,
                    backgroundColor: 'rgba(235,255,87,0.75)',
                    borderColor: 'rgba(235,255,87,1)',
                    borderWidth: 1,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        ticks: { color: '#9aa4b2' },
                        grid: { color: 'rgba(255,255,255,0.05)' }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#9aa4b2', precision: 0 },
                        grid: { color: 'rgba(255,255,255,0.05)' }
                    }
                }
            }
        });
    }

    if (areaCanvas) {
        new Chart(areaCanvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Trend',
                    data: data,
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2,
                    borderColor: '#56d6ff',
                    backgroundColor: 'rgba(86,214,255,0.16)',
                    pointBackgroundColor: '#56d6ff',
                    pointBorderColor: '#56d6ff',
                    pointRadius: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        ticks: { color: '#9aa4b2' },
                        grid: { color: 'rgba(255,255,255,0.05)' }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#9aa4b2', precision: 0 },
                        grid: { color: 'rgba(255,255,255,0.05)' }
                    }
                }
            }
        });
    }
});