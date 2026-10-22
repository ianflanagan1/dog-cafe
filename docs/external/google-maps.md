# Google Maps Platform

The map and venue pages use the Maps JavaScript API with Advanced Markers. How to set it up in Google Cloud Console → [Google Maps Platform](https://console.cloud.google.com/google/maps-apis/home).

## Couplings

- COUPLED:google-maps-api-key. API key = js:map.js key, js:venue.js key (`AIzaSyCSpBqids50TGeihGvZEqdUpNQZ68HUo9c`)
- COUPLED:google-maps-map-id. Map ID = js:map.js mapId, js:venue.js mapId (`dog-cafe-map`)
- COUPLED:public-domain. production referrer origin = caddy:site address
- COUPLED:dev-origin. dev referrer origins = each checkout's `APP_URL` in app/.env (app/.env.example, .workspace.toml [worktree-env])

## API key

The key is public by design, because it ships in the browser JavaScript. Its restrictions are the only thing that stops other sites from using it on your bill.

1. **Project and API.** Choose the project in the picker at the top of the console. [APIs](https://console.cloud.google.com/google/maps-apis/api-list) → Maps JavaScript API → Enable. Billing must be enabled on the project.
2. **Key.** [Keys & Credentials](https://console.cloud.google.com/google/maps-apis/credentials) → Create credentials → API key, or open the existing key. A new key goes in `static/js/map.js` and `static/js/venue.js`.
3. **Application restrictions:** **Websites**. Under Website restrictions → Add, once for each origin that serves the app: `<APP_URL>/*`, where `<APP_URL>` is that checkout's value in `app/.env`. Referrers accept wildcards, so one `https://*.dogcafe.<domain>/*` covers every worktree slot, `<domain>` being the one in your worktree-env config. A `www.` origin isn't needed, as Caddy redirects it before any page loads.
4. **API restrictions:** **Restrict key** → Maps JavaScript API only.
5. Save. Changes can take up to five minutes to take effect.
6. **Budget.** [Budgets & alerts](https://console.cloud.google.com/billing/budgets) → Create budget, with an alert, in case the key is abused; [Quotas](https://console.cloud.google.com/google/maps-apis/quotas) can cap daily map loads.

## Map ID

Advanced Markers require a Map ID.

1. [Map management](https://console.cloud.google.com/google/maps-apis/studio/maps) → Create Map ID → a name, Map type **JavaScript**, and Vector or Raster → Save.
2. A new ID goes in `static/js/map.js` and `static/js/venue.js` as `mapId`.
3. To style it: [Map styles](https://console.cloud.google.com/google/maps-apis/studio/styles) → create or choose a style → associate it with the Map ID.

## Related, in the repo

The nginx `Content-Security-Policy` ([nginx.conf](../../deploy/nginx/nginx.conf)) must allow `maps.googleapis.com` and `maps.gstatic.com`.
