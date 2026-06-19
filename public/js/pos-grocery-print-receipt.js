window.posGroceryPrintReceipt = function () {
    const area = document.getElementById('pos-receipt-print-area');
    const styleEl = document.getElementById('pos-receipt-iframe-styles');

    if (!area || !styleEl) {
        return;
    }

    const iframe = document.createElement('iframe');
    iframe.setAttribute('aria-hidden', 'true');
    iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
    document.body.appendChild(iframe);

    const doc = iframe.contentWindow.document;
    const title = styleEl.dataset.receiptTitle || 'Receipt';
    const html = [
        '<!DOCTYPE html><html><head><title>',
        title,
        '</title><style>',
        styleEl.textContent,
        '</style></head><body>',
        area.innerHTML,
        '</body></html>',
    ].join('');

    doc.open();
    doc.write(html);
    doc.close();

    const printFrame = function () {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
        setTimeout(function () {
            iframe.remove();
        }, 1000);
    };

    if (iframe.contentWindow.document.readyState === 'complete') {
        printFrame();
    } else {
        iframe.onload = printFrame;
    }
};
