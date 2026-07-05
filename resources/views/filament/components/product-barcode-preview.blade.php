@php
    $barcode = $barcode ?? null;
    $productName = $productName ?? null;
    $html = filled($barcode) ? \App\Support\ProductBarcodeRenderer::htmlWithLabel((string) $barcode) : '';
@endphp

@if ($html !== '')
    <div
        class="fi-product-barcode-preview"
        data-fallback-product-name="{{ e($productName ?? '') }}"
        data-default-title="{{ e(__('Barcode')) }}"
    >
        <div class="fi-product-barcode-print-area">
            {!! $html !!}
        </div>

        <button
            type="button"
            class="fi-product-barcode-print-btn mt-3 inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-medium text-gray-950 shadow-sm ring-1 ring-gray-950/10 transition hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:ring-white/10 dark:hover:bg-white/10"
        >
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18M6.34 18H4.5a2.25 2.25 0 0 1-2.25-2.25V9.75A2.25 2.25 0 0 1 4.5 7.5h15a2.25 2.25 0 0 1 2.25 2.25v6.075a2.25 2.25 0 0 1-2.25 2.25H17.66M6.34 18v-2.25m0 0V9.75m0 3.75h10.94m0 0V9.75" />
            </svg>
            {{ __('Print barcode') }}
        </button>
    </div>

    @once
        @verbatim
            <script>
                if (! window.printProductBarcodeLabel) {
                    window.printProductBarcodeLabel = function (button) {
                        const root = button.closest('.fi-product-barcode-preview');
                        const area = root?.querySelector('.fi-product-barcode-print-area');

                        if (! root || ! area) {
                            return;
                        }

                        const escapeHtml = (value) => value
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/"/g, '&quot;');

                        const modal = root.closest('.fi-modal');
                        const nameInput = modal?.querySelector('[id$=".name"]');
                        const fallbackName = root.dataset.fallbackProductName ?? '';
                        const defaultTitle = root.dataset.defaultTitle ?? 'Barcode';
                        const rawName = (nameInput?.value ?? fallbackName).trim();
                        const productName = escapeHtml(rawName);
                        const title = rawName !== '' ? escapeHtml(rawName) : defaultTitle;

                        const iframe = document.createElement('iframe');
                        iframe.style.position = 'fixed';
                        iframe.style.right = '0';
                        iframe.style.bottom = '0';
                        iframe.style.width = '0';
                        iframe.style.height = '0';
                        iframe.style.border = '0';
                        document.body.appendChild(iframe);

                        const doc = iframe.contentWindow?.document;

                        if (! doc) {
                            iframe.remove();

                            return;
                        }

                        doc.open();
                        doc.write(
                            '<!DOCTYPE html><html><head><title>' + title + '</title><style>'
                            + '*{box-sizing:border-box}'
                            + 'body{margin:0;padding:1.25rem;font-family:ui-sans-serif,system-ui,sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh}'
                            + '.label{text-align:center}'
                            + '.product-name{font-size:14px;font-weight:600;margin-bottom:.75rem;color:#111}'
                            + '@media print{body{padding:.5rem}}'
                            + '</style></head><body><div class="label">'
                            + (productName !== '' ? '<div class="product-name">' + productName + '</div>' : '')
                            + area.innerHTML
                            + '</div></body></html>'
                        );
                        doc.close();

                        iframe.contentWindow?.focus();
                        iframe.contentWindow?.print();

                        setTimeout(() => iframe.remove(), 1000);
                    };

                    document.addEventListener('click', (event) => {
                        const button = event.target.closest('.fi-product-barcode-print-btn');

                        if (! button) {
                            return;
                        }

                        event.preventDefault();
                        window.printProductBarcodeLabel(button);
                    });
                }
            </script>
        @endverbatim
    @endonce
@endif
