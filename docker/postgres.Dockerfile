FROM postgres:17.5-alpine AS prod
COPY ./production/postgres/initial.sql /docker-entrypoint-initdb.d/