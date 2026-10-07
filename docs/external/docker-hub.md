# Docker Hub

This records the **current** state of the image namespace.

- COUPLED:image-namespace. = task:IMAGE_NAMESPACE (`ianflanagan1`)

| Setting | Value |
|---|---|
| Account | `ianflanagan1` |
| Repositories | `dog-cafe-php-prod`, `dog-cafe-php-dev`, `dog-cafe-nginx-prod`, `dog-cafe-nginx-dev`. TODO: confirm which exist, and their visibility |
| Access tokens | TODO: none yet |

At the moment the Taskfile only builds and tags images locally. Nothing pushes them, and production builds from source ([compose.production.yaml](../../compose.production.yaml)), so the coupling only matters once images are pushed or pulled. When that happens, record here who pushes (CI or a person), which token they use, and where production pulls from.
