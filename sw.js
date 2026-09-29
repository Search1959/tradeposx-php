// Site-wide service worker — registered from every tenant page (see
// views/layouts/header.php) so the app is installable as a home-screen app on
// Android/Chrome from any screen, not just the offline Quick Sale POS screen
// (which has its own separate, more specific-scoped worker — see
// MobilePosController::swAction() — and is untouched by this one; the more
// specific scope always wins for its own URLs).
//
// Deliberately does NOT cache or intercept anything here: a POS app showing
// stale stock/price/Udhaar data because of an overzealous cache would be a
// real business problem. Its only job is to exist, so the browser recognizes
// this as an installable app and Android shows a proper "Install app" prompt
// with a real icon instead of just a browser bookmark.
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));
self.addEventListener('fetch', () => {}); // pass-through; no caching
