# Domain and DNS

How to point the production domain at the server. Caddy then obtains its TLS certificates automatically.

## Couplings

- COUPLED:public-domain. DNS records = caddy:site addresses, app:config/session.php cookie_domain

## Set up

At the DNS provider for the domain, the one the registrar's nameservers point to:

1. **Apex.** Add an `A` record for the domain itself (`@`), with the production server's public IPv4 address.
2. **`www`.** Add a `CNAME` for `www` to the apex, or an `A` record to the same address. Caddy redirects it to the apex.
3. **IPv6.** Add `AAAA` records only if the server accepts IPv6 on ports 80 and 443. If not, remove any, or Caddy's certificate challenge fails.
4. If the provider proxies traffic (Cloudflare's orange cloud), turn it off for these records, so that Caddy's certificate challenge reaches the server.
5. Open ports 80 and 443 to the server. Start Caddy only once the records resolve to it (`dig +short <domain>`), or its certificate requests fail and are rate-limited.
6. At the registrar, turn on auto-renew for the domain.

## When changing the domain

`grep -rn 'COUPLED:public-domain\.'` finds every site in the repo. Outside it, also update:
- the Google OAuth redirect URIs ([google-oauth.md](google-oauth.md))
- the Discord redirects ([discord-oauth.md](discord-oauth.md))
- the Maps key referrers ([google-maps.md](google-maps.md))
