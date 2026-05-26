document.addEventListener('DOMContentLoaded', function () { 
    const barcodeInput = document.getElementById('barcodeInput');
    const scanForm = document.getElementById('scanForm');
    const cashInput = document.getElementById('cashInput');
    const changeVal = document.getElementById('changeVal');
    const cashNotice = document.getElementById('cashNotice');
    const checkoutForm = document.getElementById('checkoutForm');
    const deleteItemsForm = document.getElementById('deleteItemsForm');
    const btnDeleteSelected = document.getElementById('btnDeleteSelected');
    const btnPrintReceipt = document.getElementById('btnPrintReceipt');
    const phpMsgs = document.getElementById('phpMsgs');

    const posToast = document.getElementById('posToast');
    const toastIcon = document.getElementById('toastIcon');
    const toastTitle = document.getElementById('toastTitle');
    const toastMsg = document.getElementById('toastMsg');

    const grandTotal = Number(window.POS_GRAND_TOTAL || 0);
    const cartItems = Array.isArray(window.POS_CART_ITEMS) ? window.POS_CART_ITEMS : [];

    function formatPeso(value) {
        const num = Number(value || 0);
        return '₱' + num.toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = String(text ?? '');
        return div.innerHTML;
    }

    function updateCashState() {
        if (!cashInput || !changeVal || !cashNotice) return;

        const cash = Number(cashInput.value || 0);
        const hasValue = cashInput.value !== '';
        const change = cash - grandTotal;

        cashInput.classList.remove('cash-input-error', 'cash-input-ok');

        if (!hasValue) {
            cashNotice.style.display = 'none';
            cashNotice.textContent = '';
            cashNotice.classList.remove('cash-ok', 'cash-error');
            changeVal.textContent = formatPeso(0);
            return;
        }

        if (cash < grandTotal) {
            cashNotice.style.display = 'block';
            cashNotice.textContent = 'Not enough cash.';
            cashNotice.classList.remove('cash-ok');
            cashNotice.classList.add('cash-error');
            cashNotice.style.color = '#ffffff';
            cashNotice.style.backgroundColor = '#dc2626';
            cashNotice.style.border = '1px solid #b91c1c';
            cashNotice.style.padding = '8px 12px';
            cashNotice.style.borderRadius = '6px';
            cashNotice.style.fontWeight = '600';

            cashInput.classList.add('cash-input-error');
            changeVal.textContent = formatPeso(0);
        } else {
            cashNotice.style.display = 'none';
            cashNotice.textContent = '';
            cashNotice.classList.remove('cash-error', 'cash-ok');
            cashNotice.style.backgroundColor = '';
            cashNotice.style.border = '';
            cashNotice.style.padding = '';
            cashNotice.style.borderRadius = '';
            cashNotice.style.fontWeight = '';
            cashNotice.style.color = '';

            cashInput.classList.add('cash-input-ok');
            changeVal.textContent = formatPeso(change);
        }
    }

    function showToast(type, title, message) {
        if (!posToast || !toastIcon || !toastTitle || !toastMsg) return;

        posToast.classList.remove('show', 'success', 'error');
        posToast.classList.add(type === 'error' ? 'error' : 'success');

        toastIcon.textContent = type === 'error' ? '✕' : '✓';
        toastTitle.textContent = title;
        toastMsg.textContent = message;

        requestAnimationFrame(() => {
            posToast.classList.add('show');
        });

        setTimeout(() => {
            posToast.classList.remove('show');
        }, 3000);
    }

    function buildReceiptRows(items) {
        return items.map(item => {
            const qty = Number(item.qty || 0);
            const price = Number(item.price || 0);
            const total = qty * price;

            return `
                <tr>
                    <td style="padding:2px 0; font-size:12px; vertical-align:top;">
                        ${escapeHtml(item.name)}
                        <div style="font-size:11px; color:#555;">x${qty}</div>
                    </td>
                    <td style="padding:2px 0; font-size:12px; text-align:right; vertical-align:top;">
                        ${formatPeso(total)}
                    </td>
                </tr>
            `;
        }).join('');
    }

   function getReceiptHtml() {
    const cash = Number(cashInput ? cashInput.value || 0 : 0);
    const change = Math.max(cash - grandTotal, 0);
    const invoiceNo = 'INV-' + Date.now();

    const now = new Date();
    const dateStr = now.toLocaleDateString('en-GB').replace(/\//g, '-');
    const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    function peso(val) {
        return Number(val).toFixed(2);
    }

    function buildRows(items) {
        return items.map(item => {
            const total = item.qty * item.price;
            return `
                <tr>
                    <td>${item.name}</td>
                    <td style="text-align:right;">${peso(total)}</td>
                </tr>
            `;
        }).join('');
    }

    return `
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Receipt</title>

<style>
@media print {
    @page {
        size: 80mm auto;
        margin: 0;
    }
    body {
        margin: 0;
    }
}

body {
    font-family: "Courier New", monospace;
    background: #fff;
    padding: 10px;
}

.receipt {
    width: 280px;
    margin: auto;
}

.center {
    text-align: center;
}

.title {
    font-weight: bold;
    font-size: 18px;
    letter-spacing: 2px;
}

.sub {
    font-size: 12px;
}

.line {
    border-top: 1px dashed #000;
    margin: 8px 0;
}

.table {
    width: 100%;
    font-size: 12px;
}

.table td {
    padding: 2px 0;
}

.totals td {
    padding: 2px 0;
}

.totals td:last-child {
    text-align: right;
}

.big {
    font-size: 16px;
    font-weight: bold;
}

.thank {
    text-align: center;
    margin-top: 10px;
    font-size: 18px;
    letter-spacing: 2px;
}

.barcode {
    text-align: center;
    font-size: 28px;
    letter-spacing: 3px;
    margin-top: 8px;
}
</style>
</head>

<body onload="window.print(); window.onafterprint = () => window.close();">

<div class="receipt">

    <div class="center title">CASH RECEIPT</div>
    <div class="center sub">Bohol Bicycle Inventory</div>
    <div class="center sub">Tel: 123-456-7890</div>

    <div class="line"></div>

    <div class="sub">
        Date: ${dateStr} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ${timeStr}
    </div>

    <div class="line"></div>

    <table class="table">
        ${buildRows(cartItems)}
    </table>

    <div class="line"></div>

    <table class="totals">
        <tr>
            <td>Total</td>
            <td>${peso(grandTotal)}</td>
        </tr>
        <tr>
            <td>Cash</td>
            <td>${peso(cash)}</td>
        </tr>
        <tr>
            <td>Change</td>
            <td>${peso(change)}</td>
        </tr>
    </table>

    <div class="line"></div>

    <div class="thank">THANK YOU</div>

    <div class="barcode">|||| |||| ||| |||| |||</div>

</div>

</body>
</html>
    `;
}

    function printReceipt() {

    const cartItems = window.POS_CART_ITEMS || [];
    const grandTotal = Number(window.POS_GRAND_TOTAL || 0);
    const cashInput = document.getElementById('cashInput');

    function peso(val) {
        return Number(val).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    if (!cartItems.length) {
        alert('Cart is empty.');
        return;
    }

    if (!cashInput.value) {
        alert('Enter cash first.');
        cashInput.focus();
        return;
    }

    const cash = Number(cashInput.value || 0);

    if (cash < grandTotal) {
        alert('Not enough cash.');
        cashInput.focus();
        return;
    }

    const change = cash - grandTotal;

    let rows = '';
    cartItems.forEach(item => {
        const total = item.qty * item.price;
        rows += `
            <tr>
                <td>${item.name} (x${item.qty})</td>
                <td style="text-align:right;">${peso(total)}</td>
            </tr>
        `;
    });

    const receipt = `
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Receipt</title>

<style>
@media print {
    @page {
        size: 80mm auto;
        margin: 0;
    }

    html, body {
        width: 80mm;
        margin: 0;
        padding: 0;
    }
}

body {
    font-family: "Courier New", monospace;
    background: #fff;
    margin: 0;
    padding: 4px;
}

.receipt {
    width: 72mm;
    margin: 0 auto;
}

.center {
    text-align: center;
}

.title {
    font-size: 14px;
    font-weight: bold;
}

.sub {
    font-size: 11px;
}

.line {
    border-top: 1px dashed #000;
    margin: 6px 0;
}

.table {
    width: 100%;
    font-size: 11px;
}

.table td {
    padding: 2px 0;
}

.totals td {
    font-size: 11px;
    padding: 2px 0;
}

.totals td:last-child {
    text-align: right;
}

.thank {
    text-align: center;
    font-size: 14px;
    margin-top: 6px;
}

.barcode {
    text-align: center;
    font-size: 20px;
    letter-spacing: 2px;
    margin-top: 6px;
}
</style>
</head>

<body onload="window.print(); window.onafterprint = () => window.close();">

<div class="receipt">

    <div class="center title">CASH RECEIPT</div>
    <div class="center sub">Bohol Bicycle Inventory</div>
    <div class="center sub">Tel: 123-456-7890</div>

    <div class="line"></div>

    <div class="sub">
        ${new Date().toLocaleString()}
    </div>

    <div class="line"></div>

    <table class="table">
        ${rows}
    </table>

    <div class="line"></div>

    <table class="totals">
        <tr>
            <td>Total</td>
            <td>${peso(grandTotal)}</td>
        </tr>
        <tr>
            <td>Cash</td>
            <td>${peso(cash)}</td>
        </tr>
        <tr>
            <td>Change</td>
            <td>${peso(change)}</td>
        </tr>
    </table>

    <div class="line"></div>

    <div class="thank">THANK YOU</div>

    <div class="barcode">|||| |||| ||| ||||</div>

</div>

</body>
</html>
    `;

    const w = window.open('', '', 'width=400,height=600');

    if (!w) {
        alert('Popup blocked. Please allow popups.');
        return;
    }

    w.document.open();
    w.document.write(receipt);
    w.document.close();
}
});