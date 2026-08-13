import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Helper to refresh elements dynamically via AJAX
window.refreshComponent = async function(selectors) {
    try {
        const response = await fetch(window.location.href);
        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        selectors.forEach(selector => {
            const el = document.querySelector(selector);
            const newEl = doc.querySelector(selector);
            if (el && newEl) {
                el.innerHTML = newEl.innerHTML;
                if (window.Alpine) {
                    window.Alpine.initTree(el);
                }
            }
        });
        return true;
    } catch (error) {
        console.error('Error refreshing component:', error);
        return false;
    }
};

// Let the mouse wheel scroll wide tables horizontally instead of forcing users
// to first scroll the page down to reach the scrollbar at the bottom of the table.
// Only intercepts the wheel when the hovered container actually overflows horizontally,
// so normal page scrolling is untouched everywhere else.
document.addEventListener('wheel', function (event) {
    const container = event.target.closest('.overflow-x-auto, .table-scroll');
    if (!container) return;
    if (container.scrollWidth <= container.clientWidth) return;
    if (Math.abs(event.deltaY) <= Math.abs(event.deltaX)) return; // already a horizontal gesture (trackpad), let it through natively

    container.scrollLeft += event.deltaY;
    event.preventDefault();
}, { passive: false });

Alpine.start();
