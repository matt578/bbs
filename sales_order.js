document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const visibleCount = document.getElementById('visibleCount');
    const salesTable = document.getElementById('salesTable');

    if (!searchInput || !visibleCount || !salesTable) return;

    const tbody = salesTable.querySelector('tbody');
    if (!tbody) return;

    const allRows = Array.from(tbody.querySelectorAll('tr'));

    function isEmptyRow(row) {
        return row.querySelector('.td-empty') !== null;
    }

    function updateSearch() {
        const keyword = searchInput.value.trim().toLowerCase();
        let shownCount = 0;
        let emptyRow = null;

        allRows.forEach(function (row) {
            if (isEmptyRow(row)) {
                emptyRow = row;
                row.style.display = 'none';
                return;
            }

            const rowText = row.textContent.toLowerCase();
            const matched = keyword === '' || rowText.includes(keyword);

            row.style.display = matched ? '' : 'none';

            if (matched) {
                shownCount++;
            }
        });

        visibleCount.textContent = shownCount;

        if (emptyRow) {
            emptyRow.style.display = shownCount === 0 ? '' : 'none';
        }
    }

    searchInput.addEventListener('input', updateSearch);

    updateSearch();
});