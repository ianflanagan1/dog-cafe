# Discord OAuth

"Log in with Discord". This records the **current** configuration in the Discord Developer Portal → Applications.

| Setting | Value |
|---|---|
| Application | TODO name. Its client ID and secret go in `app/.env` as `DISCORD_CLIENT_ID` / `DISCORD_CLIENT_SECRET` |
| Scopes requested by the app | `identify email` ([LoginService.php](../../app/src/Services/LoginService.php)) |

`session.cookie_samesite=none` in [php.ini](../../deploy/php/php.ini) exists for this login flow.

## Redirects (OAuth2 → Redirects)

The app builds each redirect URI from the request's scheme and host plus the route path, so every origin that serves the app needs its own registered URI.

- COUPLED:oauth-redirect-paths. path = app:LoginController::REDIRECT_ROUTE_DISCORD (`/redirect-discord-login`)
- COUPLED:public-domain. production host = caddy:site address (`dogcafeuk.com`)
- COUPLED:dev-origin. dev host = `localhost:<HTTP_PORT>`, one URI per worktree port

| Environment | URI | Registered? |
|---|---|---|
| Production | `https://dogcafeuk.com/redirect-discord-login` | TODO |
| Dev (default port) | `http://localhost:8090/redirect-discord-login` | TODO |
| Dev (other worktree ports) | `http://localhost:<HTTP_PORT>/redirect-discord-login` | TODO: list them |

## When changing

- **Domain, dev port or route path:** add the new redirect in the portal *before* deploying, and remove the old one afterwards.
- **Rotated client secret:** update `app/.env` on every environment.
