# Discord OAuth

"Log in with Discord". How to set it up in the Discord Developer Portal → [Applications](https://discord.com/developers/applications), for any environment.

`session.cookie_samesite=none` in [php.ini](../../deploy/php/php.ini) exists for this login flow.

## Couplings

- COUPLED:oauth-redirect-paths. redirect path = app:LoginController::REDIRECT_ROUTE_DISCORD
- COUPLED:public-domain. production redirect origin = caddy:site address
- COUPLED:dev-origin. dev redirect origins = each checkout's `APP_URL` in app/.env (app/.env.example, .workspace.toml [worktree-env])

## Set up

1. **Application.** [Applications](https://discord.com/developers/applications) → New Application → a name, such as `Dog Cafe` or `Dog Cafe (dev)` → accept the terms → Create. Use a separate application for dev, so production's lists only production's redirect.
2. **Credentials.** The application → OAuth2. Copy the Client ID, then Reset Secret and copy the secret, which is shown only once. Put them in that environment's `app/.env` as `DISCORD_CLIENT_ID` and `DISCORD_CLIENT_SECRET`.
3. **Redirects.** Still on OAuth2 → Redirects → Add Redirect, once for each origin that serves the app: `<APP_URL>/redirect-discord-login`, where `<APP_URL>` is that checkout's value in `app/.env`, with no trailing slash. Wildcards aren't accepted, so for worktree slots add one for each of `main` and `wt1` to `wt<slots>`, `slots` being in `.workspace.toml` [worktree-env]. → Save Changes.

The app requests the scopes `identify email` ([LoginService.php](../../app/src/Services/LoginService.php)) in each login URL, so there are none to set in the portal.

## When changing

- **Production domain, `APP_URL` or route path:** add the new redirect *before* deploying, and remove the old one afterwards.
- **Number of worktree slots:** add a redirect for each new slot.
- **Rotated client secret:** OAuth2 → Reset Secret, then update `app/.env` on every environment that uses the application. The old secret stops working at once.
