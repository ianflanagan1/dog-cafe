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

## Local Installation

```bash
git clone git@github.com:ianflanagan1/dog-cafe.git
cd dog-cafe
cp .env.example .env
make composer-install
make up
```
Access in a browser at `http://localhost:8080`.

If another application is using port 8080, in `./docker/compose.yml` modify `services.nginx.ports` from `8080:80` to `X:80`, where `X` is another port number, and access `http://localhost:X` instead.

The login function requires Google and/or Discord developer ID and Secret configured in `.env`, so will not work out-of-the-box in the local environment.

Tear down with `make down`.

## Static Analysis

```bash
make phpstan
```

## Unit Tests

```bash
make test
```