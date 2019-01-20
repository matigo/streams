DROP TABLE IF EXISTS `tmpGeoName`;
CREATE TABLE IF NOT EXISTS `tmpGeoName` (
    `id`            int(11)        UNSIGNED                     NOT NULL    AUTO_INCREMENT,
    `gnid`          int(11)        UNSIGNED                     NOT NULL    DEFAULT 0,
    `name`          varchar(200)                                NOT NULL    DEFAULT '',
    `latitude`      decimal(16,8)                               NOT NULL    DEFAULT 0,
    `lat_int`       smallint                                    NOT NULL    DEFAULT 0,
    `longitude`     decimal(16,8)                               NOT NULL    DEFAULT 0,
    `long_int`      smallint                                    NOT NULL    DEFAULT 0,

    `feature_class` char(1)                                     NOT NULL    DEFAULT '',
    `feature_code`  varchar(10)                                 NOT NULL    DEFAULT '',
    `country_code`  char(2)                                     NOT NULL    DEFAULT '',

    `fips_code`     varchar(20)                                 NOT NULL    DEFAULT '',
    `secondary`     varchar(20)                                 NOT NULL    DEFAULT '',
    `tertiary`      varchar(20)                                 NOT NULL    DEFAULT '',
    `quaternary`    varchar(20)                                 NOT NULL    DEFAULT '',
    `population`    int(11)        UNSIGNED                     NOT NULL    DEFAULT 0,
    `elevation`     int(11)                                     NOT NULL    DEFAULT 0,
    `iana_id`       varchar(40)                                 NOT NULL    DEFAULT '',

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_geo_main` ON `tmpGeoName` (`is_deleted`, `gnid`);
CREATE INDEX `idx_geo_coords` ON `tmpGeoName` (`is_deleted`, `lat_int`, `long_int`);
CREATE INDEX `idx_geo_feats` ON `tmpGeoName` (`is_deleted`, `feature_code`);

DROP TABLE IF EXISTS `tmpCountry`;
CREATE TABLE IF NOT EXISTS `tmpCountry` (
    `id`            int(11)        UNSIGNED                     NOT NULL    AUTO_INCREMENT,
    `code`          char(2)                                     NOT NULL    ,
    `iso3`          varchar(10)                                 NOT NULL    DEFAULT '',
    `iso_numeric`   varchar(10)                                 NOT NULL    DEFAULT '',
    `name`          varchar(200)                                NOT NULL    DEFAULT '',
    `capital`       varchar(200)                                NOT NULL    DEFAULT '',
    `area`          int(11)        UNSIGNED                     NOT NULL    DEFAULT 0,
    `population`    int(11)        UNSIGNED                     NOT NULL    DEFAULT 0,
    `continent`     varchar(20)                                 NOT NULL    DEFAULT '',
    `tld`           varchar(10)                                 NOT NULL    DEFAULT '',
    `currency_code` varchar(10)                                 NOT NULL    DEFAULT '',
    `currency_name` varchar(40)                                 NOT NULL    DEFAULT '',
    `gnid`          int(11)        UNSIGNED                     NOT NULL    DEFAULT 0,

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_geocnt_main` ON `tmpCountry` (`is_deleted`, `code`);
CREATE INDEX `idx_geocnt_gnid` ON `tmpCountry` (`is_deleted`, `gnid`);

DROP TABLE IF EXISTS `tmpLanguage`;
CREATE TABLE IF NOT EXISTS `tmpLanguage` (
    `id`            int(11)        UNSIGNED                     NOT NULL    AUTO_INCREMENT,
    `name`          varchar(200)                                NOT NULL    DEFAULT '',
    `iso_639_1`     varchar(10)                                 NOT NULL    DEFAULT '',
    `iso_639_2`     varchar(10)                                 NOT NULL    DEFAULT '',
    `iso_639_3`     varchar(10)                                 NOT NULL    DEFAULT '',

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_geolang_main` ON `tmpLanguage` (`is_deleted`, `iso_639_3`);

DROP TABLE IF EXISTS `tmpTimezone`;
CREATE TABLE IF NOT EXISTS `tmpTimezone` (
    `id`            int(11)        UNSIGNED                     NOT NULL    AUTO_INCREMENT,
    `country_code`  char(2)                                     NOT NULL    DEFAULT '',
    `name`          varchar(80)                                 NOT NULL    DEFAULT '',
    `gmt_offset`    decimal(8,3)                                NOT NULL    DEFAULT 0,
    `dst_offset`    decimal(8,3)                                NOT NULL    DEFAULT 0,
    `raw_offset`    decimal(8,3)                                NOT NULL    DEFAULT 0,

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_geotz_main` ON `tmpTimezone` (`is_deleted`, `country_code`);

