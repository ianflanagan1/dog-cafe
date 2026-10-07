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
cp app/.env.example app/.env
task up:detach
task composer:install
```
Access in a browser at `http://localhost:8090`.

If your user is added to the `docker` group, `sudo` is not required for `task` commands.

If another application is using port 8090, set `HTTP_PORT` in `app/.env` to a free port number `X`, and access `http://localhost:X` instead. To run several copies side by side (e.g. one per git worktree), give each its own `COMPOSE_PROJECT_NAME` and `HTTP_PORT`.

The login function requires Google and/or Discord developer ID and Secret configured in `.env`, so will not work out-of-the-box in the local environment.

Execute `task down:delete` to stop and remove all containers, named volumes and networks.

## Static Analysis

```
task phpstan
```

## Unit Tests

```
task test
```
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
