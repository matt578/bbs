document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const supplierTable = document.getElementById('supplierTable');
    const visibleCount = document.getElementById('visibleCount');
    const flashMsg = document.getElementById('flashMsg');

    const addModal = document.getElementById('addModal');
    const editModal = document.getElementById('editModal');
    const deleteModal = document.getElementById('deleteModal');

    const btnOpenAdd = document.getElementById('btnOpenAdd');
    const btnCloseAdd = document.getElementById('btnCloseAdd');
    const btnCloseAdd2 = document.getElementById('btnCloseAdd2');

    const btnCloseEdit = document.getElementById('btnCloseEdit');
    const btnCloseEdit2 = document.getElementById('btnCloseEdit2');

    const btnDeleteCancel = document.getElementById('btnDeleteCancel');
    const btnDeleteConfirm = document.getElementById('btnDeleteConfirm');
    const deleteSupplierName = document.getElementById('deleteSupplierName');

    const editId = document.getElementById('editId');
    const editName = document.getElementById('editName');
    const editContact = document.getElementById('editContact');
    const editPhone = document.getElementById('editPhone');
    const editEmail = document.getElementById('editEmail');
    const editFacebook = document.getElementById('editFacebook');

    function openModal(modal) {
        if (modal) modal.classList.add('show');
    }

    function closeModal(modal) {
        if (modal) modal.classList.remove('show');
    }

    if (btnOpenAdd) {
        btnOpenAdd.addEventListener('click', function () {
            openModal(addModal);
        });
    }

    if (btnCloseAdd) btnCloseAdd.addEventListener('click', () => closeModal(addModal));
    if (btnCloseAdd2) btnCloseAdd2.addEventListener('click', () => closeModal(addModal));
    if (btnCloseEdit) btnCloseEdit.addEventListener('click', () => closeModal(editModal));
    if (btnCloseEdit2) btnCloseEdit2.addEventListener('click', () => closeModal(editModal));
    if (btnDeleteCancel) btnDeleteCancel.addEventListener('click', () => closeModal(deleteModal));

    [addModal, editModal, deleteModal].forEach(function (modal) {
        if (!modal) return;
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal(modal);
            }
        });
    });

    document.querySelectorAll('.btn-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            editId.value = btn.dataset.id || '';
            editName.value = btn.dataset.name || '';
            editContact.value = btn.dataset.contact || '';
            editPhone.value = btn.dataset.phone || '';
            editEmail.value = btn.dataset.email || '';
            if (editFacebook) {
                editFacebook.value = btn.dataset.facebook || '';
            }
            openModal(editModal);
        });
    });

    document.querySelectorAll('.btn-delete').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            deleteSupplierName.textContent = btn.dataset.name || 'this supplier';
            btnDeleteConfirm.href = btn.getAttribute('href');
            openModal(deleteModal);
        });
    });

    if (searchInput && supplierTable && visibleCount) {
        const rows = Array.from(supplierTable.querySelectorAll('tbody tr'));

        function updateSearch() {
            const query = searchInput.value.trim().toLowerCase();
            let count = 0;

            rows.forEach(function (row) {
                const emptyRow = row.querySelector('.td-empty');
                if (emptyRow) {
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

    if (flashMsg) {
        setTimeout(function () {
            flashMsg.style.transition = 'opacity 0.35s ease';
            flashMsg.style.opacity = '0';
            setTimeout(function () {
                if (flashMsg.parentNode) {
                    flashMsg.parentNode.removeChild(flashMsg);
                }
            }, 350);
        }, 2600);
    }
});