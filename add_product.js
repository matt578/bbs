document.addEventListener('DOMContentLoaded', function () {
    const barcodeInput = document.getElementById('barcodeInput');
    const scannerProtectedFields = document.querySelectorAll('.scanner-protected');
    const imageInput = document.getElementById('imageInput');
    const imagePreview = document.getElementById('imagePreview');
    const imagePreviewWrap = document.getElementById('imagePreviewWrap');

    if (barcodeInput) {
        barcodeInput.focus();
    }

    let scanBuffer = '';
    let scanStartTime = 0;
    let lastKeyTime = 0;

    function isPrintableKey(event) {
        return event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey;
    }

    function resetScannerBuffer() {
        scanBuffer = '';
        scanStartTime = 0;
        lastKeyTime = 0;
    }

    scannerProtectedFields.forEach(function (field) {
        field.addEventListener('keydown', function (event) {
            const now = Date.now();

            if (!isPrintableKey(event) && event.key !== 'Enter') {
                return;
            }

            if (scanStartTime === 0) {
                scanStartTime = now;
                lastKeyTime = now;
            }

            const diff = now - lastKeyTime;
            lastKeyTime = now;

            if (event.key !== 'Enter' && isPrintableKey(event)) {
                scanBuffer += event.key;
            }

            const duration = now - scanStartTime;
            const looksLikeScanner =
                scanBuffer.length >= 6 &&
                duration <= 250 &&
                diff <= 35;

            if (looksLikeScanner || event.key === 'Enter') {
                event.preventDefault();

                if (barcodeInput) {
                    barcodeInput.focus();

                    if (scanBuffer.length > 0) {
                        barcodeInput.value = scanBuffer;
                    }
                }

                resetScannerBuffer();
            }

            setTimeout(function () {
                if (Date.now() - lastKeyTime > 120) {
                    resetScannerBuffer();
                }
            }, 140);
        });

        field.addEventListener('paste', function (event) {
            const pasted = (event.clipboardData || window.clipboardData).getData('text');
            const looksLikeBarcode = /^[A-Za-z0-9\-_]{6,}$/.test((pasted || '').trim());

            if (looksLikeBarcode) {
                event.preventDefault();
                if (barcodeInput) {
                    barcodeInput.focus();
                    barcodeInput.value = pasted.trim();
                }
            }
        });
    });

    if (imageInput && imagePreview && imagePreviewWrap) {
        imageInput.addEventListener('change', function () {
            const file = imageInput.files && imageInput.files[0];

            if (!file) {
                imagePreview.src = '';
                imagePreviewWrap.style.display = 'none';
                return;
            }

            const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (!allowed.includes(file.type)) {
                imagePreview.src = '';
                imagePreviewWrap.style.display = 'none';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                imagePreview.src = e.target.result;
                imagePreviewWrap.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
    }
});