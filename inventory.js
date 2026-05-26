document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const productTable = document.getElementById('productTable');
    const visibleCount = document.getElementById('visibleCount');

    const earnTodayVal = document.getElementById('earnTodayVal');
    const earnWeekVal = document.getElementById('earnWeekVal');
    const earnMonthVal = document.getElementById('earnMonthVal');
    const earnYearVal = document.getElementById('earnYearVal');

    function formatPeso(value) {
        const n = Number(value || 0);
        return '₱' + n.toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    if (searchInput && productTable && visibleCount) {
        const rows = Array.from(productTable.querySelectorAll('tbody tr'));

        function updateSearch() {
            const query = searchInput.value.trim().toLowerCase();
            let count = 0;

            rows.forEach(function (row) {
                const emptyCell = row.querySelector('.td-empty');
                if (emptyCell) {
                    row.style.display = 'none';
                    return;
                }

                const match = row.textContent.toLowerCase().includes(query);
                row.style.display = match ? '' : 'none';
                if (match) count++;
            });

            visibleCount.textContent = count;

            const emptyFallback = rows.find(r => r.querySelector('.td-empty'));
            if (emptyFallback) {
                emptyFallback.style.display = count === 0 ? '' : 'none';
            }
        }

        searchInput.addEventListener('input', updateSearch);
    }

    function loadRealtimeStats() {
        fetch('inventory_stats.php', { cache: 'no-store' })
            .then(response => response.json())
            .then(data => {
                if (!data || !data.success) return;

                if (earnTodayVal) earnTodayVal.textContent = formatPeso(data.today);
                if (earnWeekVal) earnWeekVal.textContent = formatPeso(data.week);
                if (earnMonthVal) earnMonthVal.textContent = formatPeso(data.month);
                if (earnYearVal) earnYearVal.textContent = formatPeso(data.year);
            })
            .catch(() => {
            });
    }

    loadRealtimeStats();
    setInterval(loadRealtimeStats, 5000);
});