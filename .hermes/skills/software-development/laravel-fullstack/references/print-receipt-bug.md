# Print Receipt Bug in Vue 3 + Laravel SPA

## Problem
When printing the receipt from the CheckoutModal component, the printed page is blank or only shows the receipt without proper styling.

## Root Cause
The print CSS in `resources/js/components/CheckoutModal.vue` includes a rule that hides all direct children of `<body>` that do not have the `.fixed` class:

```css
@media print {
    body > *:not(.fixed) {
        display: none !important;
    }
    /* ... */
}
```

Since the Vue app is mounted inside `<div id="app">` (which does **not** have the `.fixed` class), the entire `#app` element is hidden during print, causing the receipt to be invisible.

## Fix
Change the selector to exclude the `#app` element:

```css
@media print {
    body > *:not(.fixed):not(#app) {
        display: none !important;
    }
    /* ... keep other rules ... */
}
```

Alternatively, add the `.fixed` class to the `#app` element via JavaScript when the modal opens, but the CSS fix is simpler and does not require state changes.

## Verification
1. Open the POS interface and complete an order to reach the receipt step.
2. Press the "Cetak Struk" button (which calls `window.print()`).
3. In the print preview, you should see the receipt formatted correctly, with the modal backdrop hidden and the receipt taking the full printable area.

## Additional Notes
- The `@page { size: 80mm auto; }` rule targets thermal printers; adjust if printing to standard paper.
- Ensure that the `.print-receipt` class has `display: block !important` and appropriate sizing.
- Test printing in both browser print dialog and direct to thermal printer if applicable.