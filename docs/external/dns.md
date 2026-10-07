# Domain and DNS

This records the **current** state of `dogcafeuk.com`.

- COUPLED:public-domain. = caddy:site addresses, php:session.cookie_domain

| Setting | Value |
|---|---|
| Registrar | TODO |
| Renewal date / auto-renew | TODO |
| DNS provider | TODO |
| Production server | TODO (host/provider, IP) |

## Records

| Name | Type | Value | Notes |
|---|---|---|---|
| `dogcafeuk.com` | A | TODO | Must point at the server running Caddy |
| `www.dogcafeuk.com` | TODO (A or CNAME) | TODO | Caddy redirects it to the apex |
| AAAA records | TODO | TODO | If present, the server must accept IPv6 or Caddy's certificate challenge fails |

Caddy obtains TLS certificates automatically. Ports 80 and 443 must reach the server, and the records above must already resolve to it.

## When changing the domain

`grep -rn 'COUPLED:public-domain\.'` finds every site. Outside the repo, also update:
- the Google OAuth redirect URIs ([google-oauth.md](google-oauth.md))
- the Discord redirects ([discord-oauth.md](discord-oauth.md))
- the Maps key referrers ([google-maps.md](google-maps.md))
