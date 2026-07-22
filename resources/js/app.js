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

Alpine.start();
