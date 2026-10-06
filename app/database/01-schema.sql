BEGIN;

SET client_encoding = 'UTF8';

CREATE TABLE app_user (
    id          INTEGER PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    email       VARCHAR(100)    UNIQUE,
    name        VARCHAR(100),
    picture     VARCHAR(45),
    active      BOOL            NOT NULL DEFAULT true,
    created_at  TIMESTAMPTZ     NOT NULL
);

CREATE TABLE favourite (
    id              INTEGER PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    app_user_id     INTEGER     NOT NULL,
    venue_id        INTEGER     NOT NULL,
    venue_ext_id    CHAR(22)    NOT NULL,
    active          BOOL        NOT NULL DEFAULT true,
    created_at      TIMESTAMPTZ NOT NULL
);

CREATE TABLE venue (
    id                  INTEGER PRIMARY KEY GENERATED ALWAYS AS IDENTITY
    ,ext_id             CHAR(22)        NOT NULL UNIQUE
    ,name               VARCHAR(500)    NOT NULL
    ,human_url          VARCHAR(500)    NOT NULL UNIQUE
    ,town_id            SMALLINT        NOT NULL
    ,town               VARCHAR(500)    NOT NULL
    ,lat                REAL            NOT NULL
    ,lng                REAL            NOT NULL
    ,image              VARCHAR(45)
    ,images             VARCHAR(10000)
    ,is_cafe            BOOL            NOT NULL DEFAULT false
    ,is_restaurant      BOOL            NOT NULL DEFAULT false
    ,is_bar             BOOL            NOT NULL DEFAULT false
    ,open_0             TIME
    ,close_0            TIME
    ,open_1             TIME
    ,close_1            TIME
    ,open_2             TIME
    ,close_2            TIME
    ,open_3             TIME
    ,close_3            TIME
    ,open_4             TIME
    ,close_4            TIME
    ,open_5             TIME
    ,close_5            TIME
    ,open_6             TIME
    ,close_6            TIME
    ,street             VARCHAR(500)
    ,area               VARCHAR(500)
    ,locality           VARCHAR(500)
    ,region             VARCHAR(500)
    ,postcode           VARCHAR(10)
    ,area_id            SMALLINT
    ,locality_id        SMALLINT
    ,region_id          SMALLINT
    ,public_phone       VARCHAR(500)
    ,website            VARCHAR(1000)
    ,description        VARCHAR(10000)
    ,smart_sort         SMALLINT
    ,active             BOOLEAN         NOT NULL    DEFAULT true
);

CREATE TABLE venue_image (
    id          INTEGER PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    venue_id    INTEGER     NOT NULL,
    name        VARCHAR(43) NOT NULL
);

CREATE TABLE suggest (
    id          INTEGER PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    app_user_id INTEGER,
    text        VARCHAR(1000) NOT NULL
);

CREATE TABLE county (
    id          SMALLINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    name        VARCHAR(100) NOT NULL
);

CREATE TABLE town (
    id              SMALLINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    name            VARCHAR(50) NOT NULL,
    name_url        VARCHAR(50) NOT NULL,
    name_url_lower  VARCHAR(50) NOT NULL,
    name_search     VARCHAR(50) NOT NULL,
    county_id       SMALLINT,
    county          VARCHAR(50),
    venue_count     SMALLINT    NOT NULL,
    lat             REAL,
    lng             REAL
);

CREATE TABLE region (
    id          SMALLINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    name        VARCHAR(100) NOT NULL,
    venue_count SMALLINT,
    lat         REAL,
    lng         REAL
);

CREATE TABLE locality (
    id          SMALLINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    name        VARCHAR(100) NOT NULL,
    region_id   SMALLINT,
    venue_count SMALLINT,
    lat         REAL,
    lng         REAL
);

CREATE TABLE area (
    id          SMALLINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    name        VARCHAR(100) NOT NULL,
    region_id   SMALLINT,
    locality_id SMALLINT,
    venue_count SMALLINT,
    lat         REAL,
    lng         REAL
);

COMMIT;
