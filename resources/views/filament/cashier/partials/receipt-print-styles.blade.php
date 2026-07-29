* { box-sizing: border-box; }

body {
    margin: 0;
    padding: 0;
    font-family: 'Courier New', Courier, monospace;
    font-size: 12px;
    line-height: 1.4;
    color: #000;
    background: #fff;
}

.pos-receipt-paper {
    width: 72mm;
    max-width: 100%;
    margin: 0 auto;
    padding: 0.75rem;
    background: #fff;
}

.pos-receipt-paper__center { text-align: center; }
.pos-receipt-paper__brand { font-size: 14px; font-weight: 700; letter-spacing: 0.04em; }
.pos-receipt-paper__muted { color: #4b5563; }
.pos-receipt-paper__subtitle,
.pos-receipt-paper__footer {
    color: #000;
    font-weight: 700;
    font-size: 12px;
}
.pos-receipt-paper__unit-price {
    color: #000;
    font-weight: 700;
    font-size: 12px;
    margin-top: 0.1rem;
}
.pos-receipt-paper__divider { border-top: 1px dashed #9ca3af; margin: 0.625rem 0; }
.pos-receipt-paper__table { width: 100%; border-collapse: collapse; }
.pos-receipt-paper__table th,
.pos-receipt-paper__table td { padding: 0.125rem 0; vertical-align: top; }
.pos-receipt-paper__table th { font-weight: 700; text-align: left; border-bottom: 1px solid #000; }
.pos-receipt-paper__qty { width: 2rem; text-align: center; }
.pos-receipt-paper__amount { text-align: right; white-space: nowrap; }
.pos-receipt-paper__totals td { padding-top: 0.25rem; }
.pos-receipt-paper__label { text-align: right; padding-right: 0.5rem; }
.pos-receipt-paper__grand { font-weight: 700; font-size: 13px; }

@media print {
    @page { margin: 4mm; size: 80mm auto; }
    body { padding: 0; background: #fff; }
    .pos-receipt-paper { width: 100%; padding: 0; }
}
