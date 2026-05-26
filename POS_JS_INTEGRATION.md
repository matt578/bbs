# POS JavaScript Integration

Yes — you can open and edit both files (`pos.php` and `pos-client.js`) directly in **VS Code**.

Use `pos-client.js` to add client-side behavior to your existing `pos.php` page.

## 1) Include the script

Add this before `</body>` in `pos.php`:

```html
<script src="pos-client.js"></script>
```

## 2) What it does

- Keeps barcode scanner input focused on page load.
- Trims scanned barcode input before submit.
- Ensures ENTER submit behaves like `scan_barcode` action.
- Shows live cash feedback (`insufficient`, `valid`, and `change`).
- Blocks checkout submit when cash is below grand total.
- Keeps status text synced with current item count badge.
- Allows `Esc` key to clear barcode input quickly.

## 3) VS Code quick steps

1. Open your project folder in VS Code.
2. Open `pos.php`.
3. Paste `<script src="pos-client.js"></script>` before `</body>`.
4. Save file.
5. Make sure `pos-client.js` is in the same folder as `pos.php`.

## 4) Notes

- This is a UI/UX helper only; server-side PHP validation remains required.
- Works directly with the HTML classes and form field names from your provided POS page.