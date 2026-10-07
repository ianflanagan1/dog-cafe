# Google OAuth

"Log in with Google". This records the **current** configuration in Google Cloud Console → APIs & Services.

| Setting | Value |
|---|---|
| Google Cloud project | TODO |
| OAuth client (Credentials → OAuth 2.0 Client IDs) | TODO name. Its ID and secret go in `app/.env` as `GOOGLE_OAUTH_CLIENT_ID` / `GOOGLE_OAUTH_CLIENT_SECRET` |
| Consent screen: publishing status | TODO (Testing / In production) |
| Scopes requested by the app | `email profile` ([LoginService.php](../../app/src/Services/LoginService.php)) |

## Authorised redirect URIs

The app builds each redirect URI from `APP_URL` (app/.env) plus the route path, so every origin that serves the app needs its own registered URI matching its `APP_URL`.

- COUPLED:oauth-redirect-paths. path = app:LoginController::REDIRECT_ROUTE_GOOGLE (`/redirect-google-login`)
- COUPLED:public-domain. production host = caddy:site address (`dogcafeuk.com`)
- COUPLED:dev-origin. dev host = `localhost:<HTTP_PORT>`, one URI per worktree port

| Environment | URI | Registered? |
|---|---|---|
| Production | `https://dogcafeuk.com/redirect-google-login` | TODO |
| Dev (default port) | `http://localhost:8090/redirect-google-login` | TODO |
| Dev (other worktree ports) | `http://localhost:<HTTP_PORT>/redirect-google-login` | TODO: list them |

## When changing

- **Domain, dev port or route path:** add the new URI in the console *before* deploying, and remove the old one afterwards.
- **Rotated client secret:** update `app/.env` on every environment.
