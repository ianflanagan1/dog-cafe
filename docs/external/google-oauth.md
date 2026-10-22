# Google OAuth

"Log in with Google". How to set it up in Google Cloud Console → [Google Auth Platform](https://console.cloud.google.com/auth/overview), for any environment.

## Couplings

- COUPLED:oauth-redirect-paths. redirect URI path = app:LoginController::REDIRECT_ROUTE_GOOGLE
- COUPLED:public-domain. production redirect URI origin = caddy:site address
- COUPLED:dev-origin. dev redirect URI origins = each checkout's `APP_URL` in app/.env (app/.env.example, .workspace.toml [worktree-env])

## Set up

1. **Project.** In the project picker at the top of the console, choose the project, or create one. Use a separate project, or at least a separate client, for dev, so production's client lists only production's redirect URI.
2. **Consent screen.** [Branding](https://console.cloud.google.com/auth/branding): set the app name, support email and developer contact. If the console has no consent screen yet, it asks for these and for the audience (External) first.
3. **Scopes.** [Data Access](https://console.cloud.google.com/auth/scopes) → Add or remove scopes: tick `.../auth/userinfo.email` and `.../auth/userinfo.profile`, the scopes the app requests (`email profile` in [LoginService.php](../../app/src/Services/LoginService.php)). → Update → Save.
4. **Client.** [Clients](https://console.cloud.google.com/auth/clients) → Create client:
   - Application type: **Web application**
   - Name: any, such as `Dog Cafe (production)` or `Dog Cafe (dev)`
   - Authorised JavaScript origins: leave empty
   - Authorised redirect URIs → Add URI, once for each origin that serves the app: `<APP_URL>/redirect-google-login`, where `<APP_URL>` is that checkout's value in `app/.env`, with no trailing slash. Google accepts only `https` origins on a public domain, or `http://localhost`. Wildcards aren't accepted, so for worktree slots add one URI for each of `main` and `wt1` to `wt<slots>`, `slots` being in `.workspace.toml` [worktree-env].
   - Create
5. **Credentials.** The dialog that follows shows the client ID and secret. Copy the secret now, as the console won't show it again. Put them in that environment's `app/.env` as `GOOGLE_OAUTH_CLIENT_ID` and `GOOGLE_OAUTH_CLIENT_SECRET`.
6. **Audience.** [Audience](https://console.cloud.google.com/auth/audience): while the publishing status is **Testing**, only the test users listed there can log in, so add yours. For production, Publish app.

To add a redirect URI later, open the client in [Clients](https://console.cloud.google.com/auth/clients), Add URI, and Save. It can take a few minutes to take effect.

## When changing

- **Production domain, `APP_URL` or route path:** add the new redirect URI *before* deploying, and remove the old one afterwards.
- **Number of worktree slots:** add a redirect URI for each new slot.
- **Rotated client secret:** Clients → the client → Add secret, update `app/.env` on every environment that uses the client, then disable and delete the old secret.
