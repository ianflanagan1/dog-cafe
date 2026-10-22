# Dog Cafe

A platform that lists 10,000+ dog-friendly cafes, restaurants and bars.

A personal challenge to build framework components from scratch:
- URI router
- Dependency injection container with autowiring
- CSRF tokens
- Input validator and rules
- View generation from templates

Access at: [https://dogcafeuk.com](https://dogcafeuk.com)

## Prerequisites

- [Docker](https://docs.docker.com/get-docker/) (latest stable version)
- [Docker Compose](https://docs.docker.com/compose/install/) (if not included with Docker)
- Git
- [Task](https://taskfile.dev/installation/), or [Devbox](https://www.jetify.com/devbox), which provides it (`devbox shell`)

## Local Installation

Ensure your user is added to the `docker` group:

```
sudo usermod -aG docker $USER
```

Install:

```
git clone git@github.com:ianflanagan1/dog-cafe.git
cd dog-cafe
task setup
```
`task setup` creates `app/.env` from `app/.env.example`, installs the git hooks, starts the dev stack and runs `composer install`. Each step is skipped if it's already done, so it's safe to re-run.

Access in a browser at `http://localhost:8080`.

If your user is added to the `docker` group, `sudo` is not required for `task` commands.

If another application is using port 8080, set `HTTP_PORT` in `app/.env` to a free port number `X`, and access `http://localhost:X` instead. To run several copies side by side (e.g. one per git worktree), give each its own `COMPOSE_PROJECT_NAME` and `HTTP_PORT`, or use HTTPS hostnames per checkout (below).

The login function requires Google and/or Discord developer ID and Secret configured in `.env`, so will not work out-of-the-box in the local environment.

Execute `task down:delete` to stop and remove all containers, named volumes and networks.

## HTTPS hostnames per checkout (optional)

Instead of `http://localhost:8080`, each checkout can have its own HTTPS hostname on port 443, with a publicly trusted certificate, so dev runs on an origin shaped like production's: secure cookies, `SESSION_DOMAIN` and OAuth redirects behave as they do there. The main checkout is `https://main.dogcafe.<domain>`; git worktrees take `wt1` to `wt9`, each with its own stack.

It needs two tools of mine, set up once per machine:
- [local-https-proxy](https://github.com/ianflanagan1/local-https-proxy): one Caddy for every project, serving `*.<domain>` on 127.0.0.1:443. Its README covers the DNS record and Cloudflare token.
- [worktree-env](https://github.com/ianflanagan1/worktree-env): sets each checkout's `app/.env` for its slot, and creates and removes worktrees. `.workspace.toml` holds this project's settings for it.

Then, in the main checkout:

```
worktree-env configure   # sets APP_URL, COMPOSE_FILE and the session cookie in app/.env
task down && task up:detach
```

| | |
|---|---|
| `task worktree:new` | Creates a worktree in the next free slot, beside this checkout as `dog-cafe-wt<n>`, and starts its stack |
| `task worktree:list` | Lists the slots in use, with their URLs |
| `task worktree:rm -- wt3` | Stops that worktree's stack, deletes its volumes and removes it |

Login works in a slot once its hostname is registered with Google and Discord: see [docs/external](docs/external/).

## Static Analysis

```
task phpstan
```

## Unit Tests

```
task test
```

## Code Style

```
task pint
```

A pre-commit hook (installed by `task setup`) checks staged PHP files with Pint. It needs the dev stack running. Skip it for one commit with `git commit --no-verify`.

## Project Structure

```
app/            PHP application (Docker build context for the PHP image)
  src/
    Framework/  Hand-built framework: container, routing, HTTP, views, validation, sessions, database
    ...         Dog Cafe domain: controllers, models, repositories, services
  config/       Application config (routes, container bindings)
  database/     01-schema.sql (tables), 02-seed.sql (dev data); run in order on first Postgres start
  resources/views/
    layouts/    Page layouts
    pages/      One folder per page: head.php, body.php
  public/       PHP entry point
  storage/      Logs and user uploads
static/         CSS, JS and images, served directly by nginx
deploy/         Dockerfiles and runtime config, one folder per tool
                (base config plus *.dev.* overrides for local development)
```

## Production

`compose.production.yaml` expects these git-ignored files:

```
app/.env.production         Set APP_URL=https://dogcafeuk.com
production/
  postgres/initial.sql      Production schema and data
  cache/                    Pre-built map cache, mounted at app/shared-backend/cache
  venue-images/small/       Venue images served by nginx
  venue-images/mid/
```

```
task prod:up
```
