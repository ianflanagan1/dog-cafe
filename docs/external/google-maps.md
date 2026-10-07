# Google Maps Platform

The map and venue pages use the Maps JavaScript API with Advanced Markers. This records the **current** configuration in Google Cloud Console.

## API key

The key is public by design, because it ships in the browser JavaScript. The console restrictions are the only thing that stops other sites from using it on your bill.

- COUPLED:google-maps-api-key. = js:map.js key, js:venue.js key

| Setting | Value |
|---|---|
| Google Cloud project | TODO |
| Key | `AIzaSyCSpBqids50TGeihGvZEqdUpNQZ68HUo9c` |
| Application restriction | TODO: should be "Websites" (HTTP referrers) |
| API restrictions | TODO: should be Maps JavaScript API only |
| Quota / budget alert | TODO |

### Allowed referrers

- COUPLED:public-domain. production referrer = caddy:site address
- COUPLED:dev-origin. dev referrer = `localhost:<HTTP_PORT>`

| Environment | Referrer | Allowed? |
|---|---|---|
| Production | `https://dogcafeuk.com/*` | TODO |
| Production (www) | `https://www.dogcafeuk.com/*` | Not needed: Caddy redirects www before any page loads |
| Dev | `http://localhost:8090/*` (or `localhost:*` for every worktree) | TODO |

## Map ID

Advanced Markers require a Map ID (Map management in the console).

- COUPLED:google-maps-map-id. = js:map.js mapId, js:venue.js mapId

| Setting | Value |
|---|---|
| Map ID | `dog-cafe-map` |
| Map type | TODO (JavaScript, vector/raster) |
| Map style | TODO |

## Related, in the repo

The nginx `Content-Security-Policy` ([nginx.conf](../../deploy/nginx/nginx.conf)) must allow `maps.googleapis.com` and `maps.gstatic.com`.
