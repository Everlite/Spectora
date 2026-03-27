(function () {
    'use strict';

    // 1. Find the script tag and get the domain ID
    var script = document.currentScript;
    if (!script) {
        // Fallback for older browsers or async loading where currentScript might be null
        var scripts = document.getElementsByTagName('script');
        for (var i = 0; i < scripts.length; i++) {
            if (scripts[i].getAttribute('data-domain')) {
                script = scripts[i];
                break;
            }
        }
    }

    if (!script) {
        console.warn('Spectora Analytics: Could not find script tag with data-domain attribute.');
        return;
    }

    var domainId = script.getAttribute('data-domain');

    // 2. Collect Data
    var data = {
        domain: domainId,
        url: window.location.href,
        referrer: document.referrer || null,
        width: window.innerWidth
    };

    // 3. Send Data
    var endpoint = '/api/event';

    try {
        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data),
            keepalive: true, // Ensure request is sent even if page is unloaded
            mode: 'cors'
        }).catch(function (error) {
            console.error('Spectora Analytics Error:', error);
        });
    } catch (e) {
        console.error('Spectora Analytics Exception:', e);
    }

})();
