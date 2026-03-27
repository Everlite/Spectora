// Service Worker Version: 1.2 (Fix Icon & Payload)
self.addEventListener('install', (event) => {
    console.log('Service Worker: Installed');
    event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', (event) => {
    console.log('Service Worker: Activated');
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
    // Basic pass-through
    event.respondWith(fetch(event.request));
});

// Handle incoming push notifications
// Handle incoming push notifications
self.addEventListener('push', (event) => {
    console.log('[SW] Push Received', event);

    if (!(self.Notification && self.Notification.permission === 'granted')) {
        console.log('[SW] Notifications not granted.');
        return;
    }

    let data = {};
    if (event.data) {
        try {
            data = event.data.json();
        } catch (e) {
            console.log('[SW] Push data is not JSON', event.data.text());
            data = { body: event.data.text() };
        }
    }

    console.log('[SW] Push Data:', data);

    const title = data.title || 'Spectora Notification';

    // Handle nested data (Laravel WebPush often nests 'data' inside 'data')
    const url = data.url || (data.data ? data.data.url : '/') || '/';

    const options = {
        body: data.body || 'New alert from Spectora',
        icon: '/images/icon-192.png', // Fixed icon path
        badge: '/images/icon-192.png', // Fixed badge path
        data: {
            url: url
        },
        vibrate: [100, 50, 100]
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// Handle notification clicks
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(
        clients.openWindow(event.notification.data.url)
    );
});
