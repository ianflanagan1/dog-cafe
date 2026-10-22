# Docker Hub

Where the Taskfile's image tags point. How to set it up on [Docker Hub](https://hub.docker.com/).

## Couplings

- COUPLED:image-namespace. Docker Hub account or organisation = task:IMAGE_NAMESPACE (`ianflanagan1`)

## Set up

At the moment the Taskfile only builds and tags images locally. Nothing pushes them, and production builds from source ([compose.production.yaml](../../compose.production.yaml)), so this matters only once images are pushed or pulled.

1. **Repositories.** [Repositories](https://hub.docker.com/repositories) → Create a repository, under the namespace above, for each image the Taskfile tags: `dog-cafe-php-prod`, `dog-cafe-php-dev`, `dog-cafe-nginx-prod` and `dog-cafe-nginx-dev`. Choose Public or Private.
2. **Token.** [Personal access tokens](https://app.docker.com/settings/personal-access-tokens) → Generate new token, with Read & Write access for whoever pushes, or Read-only for a server that only pulls. It's shown once. Log in with `docker login -u <namespace>`, giving the token as the password, or store it as a CI secret.
