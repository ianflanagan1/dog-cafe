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

```
git clone git@github.com:ianflanagan1/dog-cafe.git
cd dog-cafe
cp app/.env.example app/.env
sudo make up-detach
sudo make composer-install
```
Access in a browser at `http://localhost:8090`.

If your user is added to the `docker` group, `sudo` is not required for `make` commands.

If another application is using port 8090, in `./docker/compose.yml` modify `services.nginx.ports` from `8090:80` to `X:80`, where `X` is a free port number, and access `http://localhost:X` instead.

The login function requires Google and/or Discord developer ID and Secret configured in `.env`, so will not work out-of-the-box in the local environment.

Execute `sudo make down-delete` to stop and remove all containers, named volumes and networks.

## Static Analysis

```
make phpstan
```

## Unit Tests

```
make test
```