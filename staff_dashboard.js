document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;

    const labels = Array.isArray(window.CHART_LABELS) ? window.CHART_LABELS : [];
    const data = Array.isArray(window.CHART_DATA) ? window.CHART_DATA : [];
    const canvas = document.getElementById('salesChart');

    if (!canvas) return;

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Revenue',
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
                    ticks: { color: '#9aa4b2' },
                    grid: { color: 'rgba(255,255,255,0.05)' }
                }
            }
        }
    });
});