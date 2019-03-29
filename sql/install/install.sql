/* *************************************************************************
 * @author Jason F. Irwin
 *
 *  This is the main SQL DataTable Definition for Streams
 * ************************************************************************* */
CREATE DATABASE `streams` DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE USER 'sapi'@'localhost' IDENTIFIED BY 'JlM94sK0';
GRANT ALL ON `streams`.* TO 'sapi'@'localhost';

/** ************************************************************************* *
 *  Create Sequence (Preliminaries)
 ** ************************************************************************* */
DROP TABLE IF EXISTS `Type`;
CREATE TABLE IF NOT EXISTS `Type` (
    `code`          varchar(64)             CHARACTER SET utf8  NOT NULL,
    `description`   varchar(80)                                 NOT NULL    DEFAULT '',

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_type_main` ON `Type` (`is_deleted`, `code`);

INSERT INTO `Type` (`code`, `description`)
VALUES ('system.invalid', 'Invalid Type'), ('system.unknown', 'Unknown Type'),
       ('pin.red', 'Pin (Red Colour)'), ('pin.blue', 'Pin (Blue Colour)'), ('pin.yellow', 'Pin (Yellow)'), ('pin.black', 'Pin (Black)'),
       ('pin.green', 'Pin (Green)'), ('pin.orange', 'Pin (Orange)'), ('pin.none', 'No Pin Assignment'),
       ('account.admin', 'Administrator Account'), ('account.normal', 'Standard Account'), ('account.anonymous', 'Anonymous Account'), ('account.expired', 'Expired Account'),
       ('channel.site', 'A General Channel'), ('channel.note', 'A Notes-Only Channel'), ('channel.todo', 'A ToDo-Only Channel'),
       ('post.social', 'A Social Post'), ('post.blog', 'A Blog / Podcast Post'), ('post.locker', 'A Secured Text Item'), ('post.photo', 'A Photo Post'), ('post.invalid', 'A Broken Post'),
       ('post.bookmark', 'A Bookmark Object'), ('post.todo', 'A Standard ToDo Item'), ('post.note', 'A Standard Note Item'), ('post.quotation', 'A Quotation Post from a Bookmark'),
       ('visibility.none', 'An Invisible Post'), ('visibility.password', 'A Password-Protected Post'), ('visibility.private', 'A Private Post'), ('visibility.public', 'A Public Post')
    ON DUPLICATE KEY UPDATE `is_deleted` = 'N'
;

UPDATE `Type`
   SET `created_at` = DATE_FORMAT(Now(), '%Y-%m-%d 00:00:00'),
       `updated_at` = DATE_FORMAT(Now(), '%Y-%m-%d 00:00:00');

DROP TABLE IF EXISTS `Category`;
CREATE TABLE IF NOT EXISTS `Category` (
    `id`            int(11)        UNSIGNED                     NOT NULL    AUTO_INCREMENT,
    `description`   varchar(80)                                 NOT NULL    DEFAULT '',
    `parent_id`     int(11)                                         NULL    ,
    `type`          varchar(64)             CHARACTER SET utf8  NOT NULL    DEFAULT 'system.invalid',
    `label`         varchar(80)             CHARACTER SET utf8  NOT NULL    ,

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_cats_main` ON `Category` (`is_deleted`, `parent_id`, `id`);
CREATE INDEX `idx_cats_type` ON `Category` (`is_deleted`, `type`);

INSERT INTO `Category` (`description`, `parent_id`, `type`, `label`)
VALUES ('Arts', NULL, 'category.podcast', 'catArts'), ('Business', NULL, 'category.podcast', 'catBusiness'),
       ('Comedy', NULL, 'category.podcast', 'catComedy'), ('Education', NULL, 'category.podcast', 'catEducation'),
       ('Games & Hobbies', NULL, 'category.podcast', 'catGameHobby'), ('Government & Organizations', NULL, 'category.podcast', 'catGovOrg'),
       ('Health', NULL, 'category.podcast', 'catHealth'), ('Kids & Family', NULL, 'category.podcast', 'catKids'),
       ('Music', NULL, 'category.podcast', 'catMusic'), ('News & Politics', NULL, 'category.podcast', 'catNewsPol'),
       ('Religion & Spirituality', NULL, 'category.podcast', 'catSpirit'), ('Science & Medicine', NULL, 'category.podcast', 'catSciMed'),
       ('Society & Culture', NULL, 'category.podcast', 'catSociety'), ('Sports & Recreation', NULL, 'category.podcast', 'catSports'),
       ('Technology', NULL, 'category.podcast', 'catTech'), ('TV & Film', NULL, 'category.podcast', 'catTVFilm');

INSERT INTO `Category` (`description`, `parent_id`, `type`, `label`)
VALUES ('Design', 1, 'category.podcast', 'catArtsDesign'), ('Fashion & Beauty', 1, 'category.podcast', 'catArtsFashion'),
       ('Food', 1, 'category.podcast', 'catArtsFood'), ('Literature', 1, 'category.podcast', 'catArtsLit'),
       ('Performing Arts', 1, 'category.podcast', 'catArtsPerf'), ('Visual Arts', 1, 'category.podcast', 'catArtsVisual');

INSERT INTO `Category` (`description`, `parent_id`, `type`, `label`)
VALUES ('Business News', 2, 'category.podcast', 'catBusinessNews'), ('Careers', 2, 'category.podcast', 'catBusinessCareer'),
       ('Investing', 2, 'category.podcast', 'catBusinessInvest'), ('Management & Marketing', 2, 'category.podcast', 'catBusinessManage'),
       ('Shopping', 2, 'category.podcast', 'catBusinessShopping');

INSERT INTO `Category` (`description`, `parent_id`, `type`, `label`)
VALUES ('Educational Technology', 4, 'category.podcast', 'catEducationTech'), ('Higher Education', 4, 'category.podcast', 'catEducationHigher'),
       ('K-12', 4, 'category.podcast', 'catEducationK12'), ('Language Courses', 4, 'category.podcast', 'catEducationLang'),
       ('Training', 4, 'category.podcast', 'catEducationTrain');

INSERT INTO `Category` (`description`, `parent_id`, `type`, `label`)
VALUES ('Automotive', 5, 'category.podcast', 'catGameHobbyAuto'), ('Aviation', 5, 'category.podcast', 'catGameHobbyAviation'),
       ('Hobbies', 5, 'category.podcast', 'catGameHobbyHobbies'), ('Other Games', 5, 'category.podcast', 'catGameHobbyOther'),
       ('Video Games', 5, 'category.podcast', 'catGameHobbyVide');

INSERT INTO `Category` (`description`, `parent_id`, `type`, `label`)
VALUES ('Local', 6, 'category.podcast', 'catGovOrgLocal'), ('National', 6, 'category.podcast', 'catGovOrgNation'),
       ('Non-Profit', 6, 'category.podcast', 'catGovOrgNPO'), ('Regional', 6, 'category.podcast', 'catGovOrgRegion');

INSERT INTO `Category` (`description`, `parent_id`, `type`, `label`)
VALUES ('Alternative Health', 7, 'category.podcast', 'catHealthAlt'), ('Fitness & Nutrition', 7, 'category.podcast', 'catHealthFit'),
       ('Self-Help', 7, 'category.podcast', 'catHealthSelf'), ('Sexuality', 7, 'category.podcast', 'catHealthSex');

INSERT INTO `Category` (`description`, `parent_id`, `type`, `label`)
VALUES ('Buddhism', 11, 'category.podcast', 'catSpiritBuddhism'), ('Christianity', 11, 'category.podcast', 'catSpiritChristian'),
       ('Hinduism', 11, 'category.podcast', 'catSpiritHindu'), ('Islam', 11, 'category.podcast', 'catSpiritIslam'),
       ('Judaism', 11, 'category.podcast', 'catSpiritJewish'), ('Other', 11, 'category.podcast', 'catSpiritOther'),
       ('Spirituality', 11, 'category.podcast', 'catSpiritSpirit');

INSERT INTO `Category` (`description`, `parent_id`, `type`, `label`)
VALUES ('Medicine', 12, 'category.podcast', 'catSciMedMed'), ('Natural Sciences', 12, 'category.podcast', 'catSciMedNatural'),
       ('Social Sciences', 12, 'category.podcast', 'catSciMedSocial');

INSERT INTO `Category` (`description`, `parent_id`, `type`, `label`)
VALUES ('History', 13, 'category.podcast', 'catSocietyHistory'), ('Personal Journals', 13, 'category.podcast', 'catSocietyPersonal'),
       ('Philosophy', 13, 'category.podcast', 'catSocietyPhilosophy'), ('Places & Travel', 13, 'category.podcast', 'catSocietyTravel');

INSERT INTO `Category` (`description`, `parent_id`, `type`, `label`)
VALUES ('Amateur', 14, 'category.podcast', 'catSportsAmateur'), ('College & High School', 14, 'category.podcast', 'catSportsSchool'),
       ('Outdoor', 14, 'category.podcast', 'catSportsOutdoor'), ('Professional', 14, 'category.podcast', 'catSportsPro');

INSERT INTO `Category` (`description`, `parent_id`, `type`, `label`)
VALUES ('Gadgets', 15, 'category.podcast', 'catTechGadget'), ('Tech News', 15, 'category.podcast', 'catTechNews'),
       ('Podcasting', 15, 'category.podcast', 'catTechPodcast'), ('Software How-To', 15, 'category.podcast', 'catTechHowTo');

UPDATE `Category`
   SET `created_at` = DATE_FORMAT(Now(), '%Y-%m-%d 00:00:00'),
       `updated_at` = DATE_FORMAT(Now(), '%Y-%m-%d 00:00:00');

DROP TABLE IF EXISTS `Timezone`;
CREATE TABLE IF NOT EXISTS `Timezone` (
    `id`            int(11)        UNSIGNED                     NOT NULL    AUTO_INCREMENT,
    `name`          varchar(80)                                 NOT NULL    ,
    `description`   varchar(80)                                 NOT NULL    ,
    `group`         varchar(80)                                 NOT NULL    DEFAULT '',

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_zone_group` ON `Timezone` (`is_deleted`, `group`);
CREATE INDEX `idx_zone_main` ON `Timezone` (`is_deleted`);

DROP TABLE IF EXISTS `ServerIP`;
CREATE TABLE IF NOT EXISTS `ServerIP` (
    `id`            int(11)        UNSIGNED                     NOT NULL    AUTO_INCREMENT,
    `ipv4`          varchar(16)             CHARACTER SET utf8  NOT NULL    DEFAULT '',
    `ipv6`          varchar(40)             CHARACTER SET utf8  NOT NULL    DEFAULT '',

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_server_main` ON `ServerIP` (`is_deleted`);

/** ************************************************************************* *
 *  Create Sequence (Authentication)
 ** ************************************************************************* */
DROP TABLE IF EXISTS `Account`;
CREATE TABLE IF NOT EXISTS `Account` (
    `id`            int(11)        UNSIGNED                     NOT NULL    AUTO_INCREMENT,
    `email`         varchar(140)                                NOT NULL    DEFAULT '',
    `password`      varchar(128)            CHARACTER SET utf8  NOT NULL    DEFAULT '',

    `last_name`     varchar(80)                                 NOT NULL    DEFAULT '',
    `first_name`    varchar(80)                                 NOT NULL    DEFAULT '',
    `display_name`  varchar(80)                                 NOT NULL    DEFAULT '',
    `language_code` varchar(10)             CHARACTER SET utf8  NOT NULL    DEFAULT '',
    `timezone`      varchar(40)             CHARACTER SET utf8  NOT NULL    DEFAULT 'UTC',

    `type`          varchar(64)             CHARACTER SET utf8  NOT NULL    DEFAULT 'account.expired',
    `guid`          char(36)                CHARACTER SET utf8  NOT NULL    ,

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`type`) REFERENCES `Type` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_account_main` ON `Account` (`is_deleted`, `email`);
CREATE INDEX `idx_account_guid` ON `Account` (`is_deleted`, `guid`);

DROP TABLE IF EXISTS `AccountMeta`;
CREATE TABLE IF NOT EXISTS `AccountMeta` (
    `account_id`    int(11)        UNSIGNED                     NOT NULL    ,
    `key`           varchar(64)             CHARACTER SET utf8  NOT NULL    ,
    `value`         varchar(2048)                               NOT NULL    DEFAULT '',

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    datetime                                    NOT NULL    DEFAULT Now(),
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`account_id`, `key`),
    FOREIGN KEY (`account_id`) REFERENCES `Account` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_ameta_main` ON `AccountMeta` (`is_deleted`, `account_id`, `key`);

DROP TABLE IF EXISTS `AccountPass`;
CREATE TABLE IF NOT EXISTS `AccountPass` (
    `id`            int(11)        UNSIGNED                     NOT NULL    AUTO_INCREMENT,
    `account_id`    int(11)        UNSIGNED                     NOT NULL    ,
    `password`      varchar(128)            CHARACTER SET utf8  NOT NULL    DEFAULT '',

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`account_id`) REFERENCES `Account` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_acctpass_main` ON `AccountPass` (`is_deleted`, `account_id`);

DELIMITER ;;
DROP TRIGGER IF EXISTS `before_account`;;
CREATE TRIGGER `before_account`
BEFORE INSERT ON `Account`
   FOR EACH ROW
 BEGIN
    IF new.`guid` IS NULL THEN SET new.`guid` = uuid(); END IF;
   END
;;
DROP TRIGGER IF EXISTS `after_insert_account`;;
CREATE TRIGGER `after_insert_account`
 AFTER INSERT ON `Account`
   FOR EACH ROW
 BEGIN
    INSERT INTO `AccountPass` (`account_id`, `password`)
    SELECT new.`id` as `account_id`, new.`password`
     WHERE new.`is_deleted` = 'N' and new.`password` <> IFNULL((SELECT z.`password` FROM `AccountPass` z
                                                          WHERE z.`is_deleted` = 'N' and z.`account_id` = new.`id`
                                                          ORDER BY z.`id` DESC LIMIT 1), '');
   END
;;
DROP TRIGGER IF EXISTS `after_update_account`;;
CREATE TRIGGER `after_update_account`
 AFTER UPDATE ON `Account`
   FOR EACH ROW
 BEGIN
    INSERT INTO `AccountPass` (`account_id`, `password`)
    SELECT new.`id` as `account_id`, new.`password`
     WHERE new.`is_deleted` = 'N' and new.`password` <> IFNULL((SELECT z.`password` FROM `AccountPass` z
                                                          WHERE z.`is_deleted` = 'N' and z.`account_id` = new.`id`
                                                          ORDER BY z.`id` DESC LIMIT 1), '');
   END
;;

DROP TABLE IF EXISTS `Tokens`;
CREATE TABLE IF NOT EXISTS `Tokens` (
    `id`            int(11)        UNSIGNED                     NOT NULL    AUTO_INCREMENT,
    `guid`          varchar(50)             CHARACTER SET utf8  NOT NULL    ,
    `account_id`    int(11)        UNSIGNED                     NOT NULL    ,
    `client_id`     int(11)        UNSIGNED                     NOT NULL    ,

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`account_id`) REFERENCES `Account` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_token_main` ON `Tokens` (`is_deleted`, `guid`);
CREATE INDEX `idx_token_apps` ON `Tokens` (`is_deleted`, `client_id`);
CREATE INDEX `idx_token_acct` ON `Tokens` (`is_deleted`, `account_id`);

/** ************************************************************************* *
 *  Application (Client) Tables
 ** ************************************************************************* */
DROP TABLE IF EXISTS `Client`;
CREATE TABLE IF NOT EXISTS `Client` (
    `id`            int(11)        UNSIGNED                     NOT NULL    AUTO_INCREMENT,
    `account_id`    int(11)        UNSIGNED                     NOT NULL    ,
    `name`          varchar(40)                                 NOT NULL    ,

    `logo_img`      varchar(256)            CHARACTER SET utf8  NOT NULL    DEFAULT 'default.png',
    `guid`          char(36)                CHARACTER SET utf8  NOT NULL    ,
    `secret`        varchar(128)            CHARACTER SET utf8  NOT NULL    ,
    `is_active`     enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'Y',

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`account_id`) REFERENCES `Account` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_client_main` ON `Client` (`is_deleted`, `guid`);
CREATE INDEX `idx_client_acct` ON `Client` (`is_deleted`, `account_id`);

DROP TABLE IF EXISTS `ClientMeta`;
CREATE TABLE IF NOT EXISTS `ClientMeta` (
    `client_id`     int(11)        UNSIGNED                     NOT NULL    ,
    `key`           varchar(64)             CHARACTER SET utf8  NOT NULL    ,
    `value`         varchar(2048)                               NOT NULL    DEFAULT '',

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`client_id`, `key`),
    FOREIGN KEY (`client_id`) REFERENCES `Client` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_climeta_main` ON `ClientMeta` (`is_deleted`, `client_id`);

DELIMITER ;;
DROP TRIGGER IF EXISTS `before_client`;;
CREATE TRIGGER `before_client`
BEFORE INSERT ON `Client`
   FOR EACH ROW
 BEGIN
    IF new.`secret` IS NULL THEN SET new.`secret` = sha2(CONCAT(Now(), ROUND(RAND() * 100), uuid()), 512); END IF;
    IF new.`guid` IS NULL THEN SET new.`guid` = uuid(); END IF;
   END
;;

/** ************************************************************************* *
 *  Create Sequence (Accounts & Whatnot)
 ** ************************************************************************* */
DROP TABLE IF EXISTS `Persona`;
CREATE TABLE IF NOT EXISTS `Persona` (
    `id`            int(11)        UNSIGNED                     NOT NULL    AUTO_INCREMENT,
    `account_id`    int(11)        UNSIGNED                     NOT NULL    ,
    `name`          varchar(40)             CHARACTER SET utf8  NOT NULL    ,
    `last_name`     varchar(80)                                 NOT NULL    DEFAULT '',
    `first_name`    varchar(80)                                 NOT NULL    DEFAULT '',
    `display_name`  varchar(80)                                 NOT NULL    DEFAULT '',

    `avatar_img`    varchar(120)            CHARACTER SET utf8  NOT NULL    DEFAULT 'default.png',
    `email`         varchar(256)                                NOT NULL    DEFAULT '',
    `guid`          char(36)                CHARACTER SET utf8  NOT NULL    ,
    `is_active`     enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'Y',

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`account_id`) REFERENCES `Account` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_prsn_main` ON `Persona` (`is_deleted`, `id`);
CREATE INDEX `idx_prsn_name` ON `Persona` (`is_deleted`, `name`);
CREATE INDEX `idx_prsn_guid` ON `Persona` (`is_deleted`, `guid`);
CREATE INDEX `idx_prsn_acct` ON `Persona` (`is_deleted`, `account_id`);

DROP TABLE IF EXISTS `PersonaMeta`;
CREATE TABLE IF NOT EXISTS `PersonaMeta` (
    `persona_id`    int(11)        UNSIGNED                     NOT NULL    ,
    `key`           varchar(64)             CHARACTER SET utf8  NOT NULL    ,
    `value`         varchar(2048)                               NOT NULL    DEFAULT '',

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`persona_id`, `key`),
    FOREIGN KEY (`persona_id`) REFERENCES `Persona` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_psnameta_main` ON `PersonaMeta` (`is_deleted`, `persona_id`);

DELIMITER ;;
DROP TRIGGER IF EXISTS `before_persona`;;
CREATE TRIGGER `before_persona`
BEFORE INSERT ON `Persona`
   FOR EACH ROW
 BEGIN
    IF new.`guid` IS NULL THEN SET new.`guid` = uuid(); END IF;
   END
;;

/** ************************************************************************* *
 *  Create Sequence (Sites)
 ** ************************************************************************* */
DROP TABLE IF EXISTS `Site`;
CREATE TABLE IF NOT EXISTS `Site` (
    `id`            int(11)        UNSIGNED                     NOT NULL    AUTO_INCREMENT,
    `account_id`    int(11)        UNSIGNED                     NOT NULL    ,
    `name`          varchar(80)                                 NOT NULL    ,
    `description`   varchar(255)                                NOT NULL    DEFAULT '',
    `keywords`      varchar(255)                                NOT NULL    DEFAULT '',

    `https`         enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `theme`         varchar(20)                                 NOT NULL    DEFAULT 'demo',
    `guid`          char(36)                CHARACTER SET utf8  NOT NULL    ,
    `version`       varchar(64)             CHARACTER SET utf8  NOT NULL    DEFAULT '',

    `is_default`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`account_id`) REFERENCES `Account` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_site_main` ON `Site` (`is_deleted`, `id`);
CREATE INDEX `idx_site_defs` ON `Site` (`is_deleted`, `is_default`);
CREATE INDEX `idx_site_acct` ON `Site` (`is_deleted`, `account_id`);

DROP TABLE IF EXISTS `SiteUrl`;
CREATE TABLE IF NOT EXISTS `SiteUrl` (
    `id`            int(11)        UNSIGNED                     NOT NULL    AUTO_INCREMENT,
    `site_id`       int(11)        UNSIGNED                     NOT NULL    ,
    `url`           varchar(140)            CHARACTER SET utf8  NOT NULL    ,
    `is_active`     enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'Y',

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`site_id`) REFERENCES `Site` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_surl_main` ON `SiteUrl` (`is_deleted`, `url`);
CREATE INDEX `idx_surl_site` ON `SiteUrl` (`is_deleted`, `is_active`, `site_id`);

DROP TABLE IF EXISTS `SiteMeta`;
CREATE TABLE IF NOT EXISTS `SiteMeta` (
    `site_id`       int(11)        UNSIGNED                     NOT NULL    ,
    `key`           varchar(64)             CHARACTER SET utf8  NOT NULL    ,
    `value`         varchar(2048)                               NOT NULL    DEFAULT '',

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`site_id`, `key`),
    FOREIGN KEY (`site_id`) REFERENCES `Site` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_smeta_main` ON `SiteMeta` (`is_deleted`, `site_id`, `key`);
CREATE INDEX `idx_smeta_site` ON `SiteMeta` (`is_deleted`, `site_id`);

DELIMITER ;;
DROP TRIGGER IF EXISTS `before_site`;;
CREATE TRIGGER `before_site`
BEFORE INSERT ON `Site`
   FOR EACH ROW
 BEGIN
    IF new.`guid` IS NULL THEN SET new.`guid` = uuid(); END IF;
   END
;;

/** ************************************************************************* *
 *  File Resources
 ** ************************************************************************* */
DROP TABLE IF EXISTS `File`;
CREATE TABLE IF NOT EXISTS `File` (
    `id`            int(11)        UNSIGNED                     NOT NULL    AUTO_INCREMENT,
    `account_id`    int(11)        UNSIGNED                     NOT NULL    ,

    `name`          varchar(256)                                NOT NULL    ,
    `local_name`    varchar(80)                                 NOT NULL    ,
    `public_name`   varchar(80)                                 NOT NULL    ,
    `hash`          varchar(64)             CHARACTER SET utf8  NOT NULL    ,
    `bytes`         int(11)        UNSIGNED                     NOT NULL    ,
    `location`      varchar(1024)                               NOT NULL    ,
    `type`          varchar(64)             CHARACTER SET utf8  NOT NULL    ,
    `guid`          char(36)                CHARACTER SET utf8  NOT NULL    ,

    `expires_at`    datetime                                        NULL    ,

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`account_id`) REFERENCES `Account` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_file_main` ON `File` (`is_deleted`, `public_name`);
CREATE INDEX `idx_file_acct` ON `File` (`is_deleted`, `account_id`);
CREATE INDEX `idx_file_guid` ON `File` (`is_deleted`, `guid`);

DROP TABLE IF EXISTS `FileMeta`;
CREATE TABLE IF NOT EXISTS `FileMeta` (
    `file_id`       int(11)        UNSIGNED                     NOT NULL    ,
    `key`           varchar(64)             CHARACTER SET utf8  NOT NULL    ,
    `value`         varchar(2048)                               NOT NULL    DEFAULT '',

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`file_id`, `key`),
    FOREIGN KEY (`file_id`) REFERENCES `File` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_fmeta_main` ON `FileMeta` (`is_deleted`, `file_id`);

DELIMITER ;;
DROP TRIGGER IF EXISTS `before_file`;;
CREATE TRIGGER `before_file`
BEFORE INSERT ON `File`
   FOR EACH ROW
 BEGIN
    IF new.`guid` IS NULL THEN SET new.`guid` = uuid(); END IF;
   END
;;

/** ************************************************************************* *
 *  Channels & Content
 ** ************************************************************************* */
DROP TABLE IF EXISTS `Channel`;
CREATE TABLE IF NOT EXISTS `Channel` (
    `id`            int(11)        UNSIGNED                     NOT NULL    AUTO_INCREMENT,
    `account_id`    int(11)        UNSIGNED                     NOT NULL    ,
    `name`          varchar(128)                                NOT NULL    ,

    `type`          varchar(64)             CHARACTER SET utf8  NOT NULL    DEFAULT 'channel.site',
    `privacy_type`  varchar(64)             CHARACTER SET utf8  NOT NULL    DEFAULT 'visibility.public',
    `site_id`       int(11)        UNSIGNED                         NULL    ,
    `guid`          char(36)                CHARACTER SET utf8  NOT NULL    ,

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`type`) REFERENCES `Type` (`code`),
    FOREIGN KEY (`account_id`) REFERENCES `Account` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_chan_main` ON `Channel` (`is_deleted`, `type`, `site_id`, `privacy_type`, `id`);
CREATE INDEX `idx_chan_acct` ON `Channel` (`is_deleted`, `account_id`);
CREATE INDEX `idx_chan_guid` ON `Channel` (`is_deleted`, `guid`);
CREATE INDEX `idx_chan_type` ON `Channel` (`is_deleted`, `type`);

DROP TABLE IF EXISTS `ChannelAuthor`;
CREATE TABLE IF NOT EXISTS `ChannelAuthor` (
    `channel_id`    int(11)        UNSIGNED                     NOT NULL    ,
    `persona_id`    int(11)        UNSIGNED                     NOT NULL    ,

    `can_read`      enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `can_write`     enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`channel_id`, `persona_id`),
    FOREIGN KEY (`channel_id`) REFERENCES `Channel` (`id`),
    FOREIGN KEY (`persona_id`) REFERENCES `Persona` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_chanauth_main` ON `ChannelAuthor` (`is_deleted`, `channel_id`);
CREATE INDEX `idx_chanauth_acct` ON `ChannelAuthor` (`is_deleted`, `channel_id`, `persona_id`);

DELIMITER ;;
DROP TRIGGER IF EXISTS `before_insert_channel`;;
CREATE TRIGGER `before_insert_channel`
BEFORE INSERT ON `Channel`
   FOR EACH ROW
 BEGIN
    IF new.`guid` IS NULL THEN SET new.`guid` = uuid(); END IF;
   END
;;

DROP TABLE IF EXISTS `Post`;
CREATE TABLE IF NOT EXISTS `Post` (
    `id`            int(11)        UNSIGNED                     NOT NULL    AUTO_INCREMENT,
    `persona_id`    int(11)        UNSIGNED                     NOT NULL    ,
    `client_id`     int(11)        UNSIGNED                         NULL    ,
    `thread_id`     int(11)        UNSIGNED                         NULL    ,
    `parent_id`     int(11)        UNSIGNED                         NULL    ,

    `title`         varchar(512)                                    NULL    ,
    `value`         text                                        NOT NULL    ,

    `canonical_url` varchar(512)            CHARACTER SET utf8  NOT NULL    ,
    `reply_to`      varchar(512)                                    NULL    ,
    `channel_id`    int(11)        UNSIGNED                     NOT NULL    ,

    `slug`          varchar(255)            CHARACTER SET utf8  NOT NULL    ,
    `type`          varchar(64)             CHARACTER SET utf8  NOT NULL    DEFAULT 'post.invalid',
    `guid`          char(36)                CHARACTER SET utf8  NOT NULL    ,
    `privacy_type`  varchar(64)             CHARACTER SET utf8  NOT NULL    DEFAULT 'visibility.public',

    `publish_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `expires_at`    timestamp                                       NULL    ,
    `hash`          char(40)                CHARACTER SET utf8  NOT NULL    ,

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `created_by`    int(11)        UNSIGNED                     NOT NULL    ,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_by`    int(11)        UNSIGNED                     NOT NULL    ,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`type`) REFERENCES `Type` (`code`),
    FOREIGN KEY (`client_id`) REFERENCES `Client` (`id`),
    FOREIGN KEY (`persona_id`) REFERENCES `Persona` (`id`),
    FOREIGN KEY (`channel_id`) REFERENCES `Channel` (`id`),
    FOREIGN KEY (`privacy_type`) REFERENCES `Type` (`code`),
    FOREIGN KEY (`created_by`) REFERENCES `Account` (`id`),
    FOREIGN KEY (`updated_by`) REFERENCES `Account` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_post_main` ON `Post` (`is_deleted`, `channel_id`, `type`, `privacy_type`, `publish_at`, `persona_id`);
CREATE INDEX `idx_post_acct` ON `Post` (`is_deleted`, `persona_id`, `type`);
CREATE INDEX `idx_post_thrd` ON `Post` (`is_deleted`, `thread_id` DESC, `id` DESC);
CREATE INDEX `idx_post_guid` ON `Post` (`is_deleted`, `guid`);
CREATE INDEX `idx_post_idx` ON `Post` (`is_deleted`, `id` DESC);

DROP TABLE IF EXISTS `PostHistory`;
CREATE TABLE IF NOT EXISTS `PostHistory` (
    `id`            int(11)        UNSIGNED                     NOT NULL    AUTO_INCREMENT,
    `post_id`       int(11)        UNSIGNED                     NOT NULL    ,

    `title`         varchar(512)                                    NULL    ,
    `value`         text                                        NOT NULL    ,

    `canonical_url` varchar(255)            CHARACTER SET utf8  NOT NULL    ,
    `channel_id`    int(11)        UNSIGNED                     NOT NULL    ,

    `slug`          varchar(255)            CHARACTER SET utf8  NOT NULL    ,
    `type`          varchar(64)             CHARACTER SET utf8  NOT NULL    DEFAULT 'post.invalid',
    `privacy_type`  varchar(64)             CHARACTER SET utf8  NOT NULL    DEFAULT 'visibility.public',

    `publish_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `expires_at`    timestamp                                       NULL    ,
    `hash`          char(40)                CHARACTER SET utf8  NOT NULL    ,

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_by`    int(11)        UNSIGNED                     NOT NULL    ,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`type`) REFERENCES `Type` (`code`),
    FOREIGN KEY (`post_id`) REFERENCES `Post` (`id`),
    FOREIGN KEY (`channel_id`) REFERENCES `Channel` (`id`),
    FOREIGN KEY (`privacy_type`) REFERENCES `Type` (`code`),
    FOREIGN KEY (`updated_by`) REFERENCES `Account` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_phist_main` ON `PostHistory` (`is_deleted`, `post_id` DESC);
CREATE INDEX `idx_phist_hash` ON `PostHistory` (`is_deleted`, `post_id` DESC, `hash`);

DROP TABLE IF EXISTS `PostMeta`;
CREATE TABLE IF NOT EXISTS `PostMeta` (
    `post_id`       int(11)        UNSIGNED                     NOT NULL    ,
    `key`           varchar(64)             CHARACTER SET utf8  NOT NULL    ,
    `value`         varchar(2048)                               NOT NULL    DEFAULT '',
    `is_private`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`post_id`, `key`),
    FOREIGN KEY (`post_id`) REFERENCES `Post` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_pmeta_main` ON `PostMeta` (`is_deleted`, `post_id` DESC, `key`);
CREATE INDEX `idx_pmeta_post` ON `PostMeta` (`is_deleted`, `post_id` DESC);

DROP TABLE IF EXISTS `PostSearch`;
CREATE TABLE IF NOT EXISTS `PostSearch` (
    `post_id`       int(11)        UNSIGNED                     NOT NULL    ,
    `word`          varchar(255)                                NOT NULL    DEFAULT '',
    `length`        smallint       UNSIGNED                     NOT NULL    DEFAULT 0,
    `hash`          char(40)                                    NOT NULL    ,

    `is_deleted`    enum('N','Y')                               NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`post_id`, `word`),
    FOREIGN KEY (`post_id`) REFERENCES `Post` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_psrch_main` ON `PostSearch` (`is_deleted`, `post_id` DESC, `word`);
CREATE INDEX `idx_psrch_hash` ON `PostSearch` (`is_deleted`, `hash`);

DROP TABLE IF EXISTS `PostTags`;
CREATE TABLE IF NOT EXISTS `PostTags` (
    `post_id`       int(11)        UNSIGNED                     NOT NULL    ,
    `key`           varchar(128)                                NOT NULL    DEFAULT '',
    `value`         varchar(128)                                NOT NULL    DEFAULT '',

    `is_deleted`    enum('N','Y')                               NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`post_id`, `key`),
    FOREIGN KEY (`post_id`) REFERENCES `Post` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_ptags_main` ON `PostTags` (`is_deleted`, `post_id` DESC, `key`);
CREATE INDEX `idx_ptags_post` ON `PostTags` (`is_deleted`, `post_id` DESC);

DROP TABLE IF EXISTS `PostMention`;
CREATE TABLE IF NOT EXISTS `PostMention` (
    `post_id`       int(11)        UNSIGNED                     NOT NULL    ,
    `persona_id`    int(11)        UNSIGNED                     NOT NULL    ,

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`post_id`, `persona_id`),
    FOREIGN KEY (`post_id`) REFERENCES `Post` (`id`),
    FOREIGN KEY (`persona_id`) REFERENCES `Persona` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_pmens_acct` ON `PostMention` (`is_deleted`, `persona_id`);
CREATE INDEX `idx_pmens_post` ON `PostMention` (`is_deleted`, `post_id` DESC);

DROP TABLE IF EXISTS `PostFile`;
CREATE TABLE IF NOT EXISTS `PostFile` (
    `post_id`       int(11)        UNSIGNED                     NOT NULL    ,
    `file_id`       int(11)        UNSIGNED                     NOT NULL    ,

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`post_id`, `file_id`),
    FOREIGN KEY (`post_id`) REFERENCES `Post` (`id`),
    FOREIGN KEY (`file_id`) REFERENCES `File` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_pfile_main` ON `PostFile` (`is_deleted`, `post_id` DESC);
CREATE INDEX `idx_pfile_aux` ON `PostFile` (`is_deleted`, `file_id` DESC);

DROP TABLE IF EXISTS `PostAction`;
CREATE TABLE IF NOT EXISTS `PostAction` (
    `post_id`       int(11)        UNSIGNED                     NOT NULL    ,
    `persona_id`    int(11)        UNSIGNED                     NOT NULL    ,

    `pin_type`      varchar(64)             CHARACTER SET utf8  NOT NULL    DEFAULT 'pin.none',
    `is_starred`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `is_muted`      enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `points`        int(11)        UNSIGNED                     NOT NULL    DEFAULT 0,

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`post_id`, `persona_id`),
    FOREIGN KEY (`post_id`) REFERENCES `Post` (`id`),
    FOREIGN KEY (`persona_id`) REFERENCES `Persona` (`id`),
    FOREIGN KEY (`pin_type`) REFERENCES `Type` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_pact_main` ON `PostAction` (`is_deleted`, `post_id` DESC, `persona_id`);
CREATE INDEX `idx_pact_aux` ON `PostAction` (`is_deleted`, `post_id` DESC);

DELIMITER ;;
DROP TRIGGER IF EXISTS `before_insert_post`;;
CREATE TRIGGER `before_insert_post`
BEFORE INSERT ON `Post`
   FOR EACH ROW
 BEGIN
    IF new.`guid` IS NULL THEN SET new.`guid` = uuid(); END IF;
    IF new.`canonical_url` IS NULL THEN SET new.`canonical_url` = CONCAT(REPLACE(new.`type`, 'post.', '/'), '/', new.`guid`); END IF;
    IF new.`slug` IS NULL THEN SET new.`slug` = new.`guid`; END IF;
    SET new.`hash` = SHA1(CONCAT(new.`id`, IFNULL(new.`title`, ''), IFNULL(new.`value`, ''), IFNULL(new.`canonical_url`, ''), new.`channel_id`,
                                 IFNULL(new.`slug`, ''), IFNULL(new.`type`, ''), IFNULL(new.`privacy_type`, ''),
                                 DATE_FORMAT(new.`publish_at`, '%Y-%m-%d %H:%i:%s'), DATE_FORMAT(IFNULL(new.`expires_at`, new.`publish_at`), '%Y-%m-%d %H:%i:%s'), new.`is_deleted`));
   END
;;
DROP TRIGGER IF EXISTS `after_insert_post`;;
CREATE TRIGGER `after_insert_post`
 AFTER INSERT ON `Post`
   FOR EACH ROW
 BEGIN
    INSERT INTO `PostHistory` (`post_id`, `title`, `value`, `canonical_url`, `channel_id`, `slug`, `type`, `privacy_type`, `publish_at`, `expires_at`, `hash`, `updated_by`)
    SELECT new.`id`, new.`title`, new.`value`, new.`canonical_url`, new.`channel_id`, new.`slug`, new.`type`, new.`privacy_type`,
           new.`publish_at`, new.`expires_at`, new.`hash`, new.`updated_by`
     WHERE new.`is_deleted` = 'N' and new.`hash` NOT IN (SELECT z.`hash` FROM `PostHistory` z WHERE z.`is_deleted` = 'N' and z.`post_id` = new.`id`);
   END
;;
DROP TRIGGER IF EXISTS `before_update_post`;;
CREATE TRIGGER `before_update_post`
BEFORE UPDATE ON `Post`
   FOR EACH ROW
 BEGIN
    SET new.`hash` = SHA1(CONCAT(new.`id`, IFNULL(new.`title`, ''), IFNULL(new.`value`, ''), IFNULL(new.`canonical_url`, ''), new.`channel_id`,
                                 IFNULL(new.`slug`, ''), IFNULL(new.`type`, ''), IFNULL(new.`privacy_type`, ''),
                                 DATE_FORMAT(new.`publish_at`, '%Y-%m-%d %H:%i:%s'), DATE_FORMAT(IFNULL(new.`expires_at`, new.`publish_at`), '%Y-%m-%d %H:%i:%s'), new.`is_deleted`));
   END
;;
DROP TRIGGER IF EXISTS `after_update_post`;;
CREATE TRIGGER `after_update_post`
 AFTER UPDATE ON `Post`
   FOR EACH ROW
 BEGIN
    INSERT INTO `PostHistory` (`post_id`, `title`, `value`, `canonical_url`, `channel_id`, `slug`, `type`, `privacy_type`, `publish_at`, `expires_at`, `hash`, `updated_by`)
    SELECT new.`id`, new.`title`, new.`value`, new.`canonical_url`, new.`channel_id`, new.`slug`, new.`type`, new.`privacy_type`,
           new.`publish_at`, new.`expires_at`, new.`hash`, new.`updated_by`
     WHERE new.`is_deleted` = 'N' and new.`hash` NOT IN (SELECT z.`hash` FROM `PostHistory` z WHERE z.`is_deleted` = 'N' and z.`post_id` = new.`id`);
   END
;;

DROP TRIGGER IF EXISTS `before_insert_postsrch`;;
CREATE TRIGGER `before_insert_postsrch`
BEFORE INSERT ON `PostSearch`
   FOR EACH ROW
 BEGIN
    SET new.`length` = LENGTH(new.`word`);
    SET new.`hash` = MD5(new.`word`);
   END
;;
DROP TRIGGER IF EXISTS `before_update_postsrch`;;
CREATE TRIGGER `before_update_postsrch`
 BEFORE UPDATE ON `PostSearch`
   FOR EACH ROW
 BEGIN
    SET new.`is_deleted` = CASE WHEN IFNULL(new.`word`, '') <> '' THEN 'N' ELSE 'Y' END;
    SET new.`updated_at` = Now();
   END
;;

/** ************************************************************************* *
 *  Syndicated
 ** ************************************************************************* */
DROP TABLE IF EXISTS `SyndFeed`;
CREATE TABLE IF NOT EXISTS `SyndFeed` (
    `id`            int(11)       UNSIGNED                      NOT NULL    AUTO_INCREMENT,
    `title`         varchar(255)                                NOT NULL    ,
    `description`   varchar(2048)                                   NULL    ,
    `url`           varchar(512)                                NOT NULL    ,

    `guid`          char(36)                                    NOT NULL    ,
    `hash`          char(40)                                    NOT NULL    ,
    `channel_id`    int(11)        UNSIGNED                         NULL    ,
    `polled_at`     timestamp                                   NOT NULL    ,

    `is_deleted`    enum('N','Y')                               NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`channel_id`) REFERENCES `Channel` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_syndfeed_main` ON `SyndFeed` (`is_deleted`, `guid`);

DELIMITER ;;
DROP TRIGGER IF EXISTS `before_update_syndfeed`;;
CREATE TRIGGER `before_update_syndfeed`
BEFORE UPDATE ON `SyndFeed`
   FOR EACH ROW
 BEGIN
    SET new.`updated_at` = Now();
   END
;;

DROP TABLE IF EXISTS `SyndFeedMeta`;
CREATE TABLE IF NOT EXISTS `SyndFeedMeta` (
    `feed_id`       int(11)        UNSIGNED                     NOT NULL    ,
    `key`           varchar(64)                                 NOT NULL    ,
    `value`         varchar(2048)                                   NULL    ,

    `is_deleted`    enum('N','Y')                               NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`feed_id`, `key`),
    FOREIGN KEY (`feed_id`) REFERENCES `SyndFeed` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_syndfmeta_main` ON `SyndFeedMeta` (`is_deleted`, `feed_id`);

DELIMITER ;;
DROP TRIGGER IF EXISTS `before_update_syndfeedmeta`;;
CREATE TRIGGER `before_update_syndfeedmeta`
BEFORE UPDATE ON `SyndFeedMeta`
   FOR EACH ROW
 BEGIN
    SET new.`is_deleted` = CASE WHEN IFNULL(new.`value`, '') = '' THEN 'Y' ELSE 'N' END;
    SET new.`updated_at` = Now();
   END
;;

DROP TABLE IF EXISTS `SyndFeedItem`;
CREATE TABLE IF NOT EXISTS `SyndFeedItem` (
    `id`            int(11)       UNSIGNED                      NOT NULL    AUTO_INCREMENT,
    `feed_id`       int(11)       UNSIGNED                      NOT NULL    ,
    `title`         varchar(255)                                    NULL    ,
    `url`           varchar(2048)                               NOT NULL    ,

    `publish_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `guid`          char(36)                                    NOT NULL    ,
    `hash`          char(40)                                    NOT NULL    ,

    `is_deleted`    enum('N','Y')                               NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`feed_id`) REFERENCES `SyndFeed` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_syndfitem_main` ON `SyndFeedItem` (`is_deleted`, `feed_id`, `guid`);
CREATE INDEX `idx_syndfitem_pub` ON `SyndFeedItem` (`is_deleted`, `feed_id`, `publish_at`);

DELIMITER ;;
DROP TRIGGER IF EXISTS `before_insert_sfitem`;;
CREATE TRIGGER `before_insert_sfitem`
BEFORE INSERT ON `SyndFeedItem`
   FOR EACH ROW
 BEGIN
    IF new.`guid` IS NULL THEN SET new.`guid` = uuid(); END IF;
   END
;;

DROP TABLE IF EXISTS `SyndFeedItemMeta`;
CREATE TABLE IF NOT EXISTS `SyndFeedItemMeta` (
    `item_id`       int(11)        UNSIGNED                     NOT NULL    ,
    `key`           varchar(64)                                 NOT NULL    ,
    `value`         varchar(2048)                                   NULL    ,

    `is_deleted`    enum('N','Y')                               NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`item_id`, `key`),
    FOREIGN KEY (`item_id`) REFERENCES `SyndFeedItem` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_syndimeta_main` ON `SyndFeedItemMeta` (`is_deleted`, `item_id`);

DELIMITER ;;
DROP TRIGGER IF EXISTS `before_update_syndfitemmeta`;;
CREATE TRIGGER `before_update_syndfitemmeta`
BEFORE UPDATE ON `SyndFeedItemMeta`
   FOR EACH ROW
 BEGIN
    SET new.`is_deleted` = CASE WHEN IFNULL(new.`value`, '') = '' THEN 'Y' ELSE 'N' END;
    SET new.`updated_at` = Now();
   END
;;

DROP TABLE IF EXISTS `SyndFeedItemSearch`;
CREATE TABLE IF NOT EXISTS `SyndFeedItemSearch` (
    `item_id`       int(11)        UNSIGNED                     NOT NULL    ,
    `word`          varchar(255)                                NOT NULL    DEFAULT '',
    `length`        smallint       UNSIGNED                     NOT NULL    DEFAULT 0,
    `hash`          char(40)                                    NOT NULL    ,

    `is_deleted`    enum('N','Y')                               NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`item_id`, `word`),
    FOREIGN KEY (`item_id`) REFERENCES `SyndFeedItem` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_sfisrch_main` ON `SyndFeedItemSearch` (`is_deleted`, `item_id`, `word`);
CREATE INDEX `idx_sfisrch_hash` ON `SyndFeedItemSearch` (`is_deleted`, `hash`);

DELIMITER ;;
DROP TRIGGER IF EXISTS `before_insert_sfisrch`;;
CREATE TRIGGER `before_insert_sfisrch`
BEFORE INSERT ON `SyndFeedItemSearch`
   FOR EACH ROW
 BEGIN
    SET new.`length` = LENGTH(new.`word`);
    SET new.`hash` = MD5(new.`word`);
   END
;;

DELIMITER ;;
DROP TRIGGER IF EXISTS `before_update_sfisrch`;;
CREATE TRIGGER `before_update_sfisrch`
 BEFORE UPDATE ON `SyndFeedItemSearch`
   FOR EACH ROW
 BEGIN
    SET new.`is_deleted` = CASE WHEN IFNULL(new.`word`, '') <> '' THEN 'N' ELSE 'Y' END;
    SET new.`updated_at` = Now();
   END
;;

DROP TABLE IF EXISTS `SyndFollow`;
CREATE TABLE IF NOT EXISTS `SyndFollow` (
    `account_id`    int(11)        UNSIGNED                     NOT NULL    ,
    `feed_id`       int(11)        UNSIGNED                     NOT NULL    ,

    `is_deleted`    enum('N','Y')                               NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`account_id`, `feed_id`),
    FOREIGN KEY (`account_id`) REFERENCES `Account` (`id`),
    FOREIGN KEY (`feed_id`) REFERENCES `SyndFeed` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_syndfolw_main` ON `SyndFollow` (`is_deleted`, `account_id`);
CREATE INDEX `idx_syndfolw_feed` ON `SyndFollow` (`is_deleted`, `feed_id`);

DELIMITER ;;
DROP TRIGGER IF EXISTS `before_update_syndfollow`;;
CREATE TRIGGER `before_update_syndfollow`
BEFORE UPDATE ON `SyndFollow`
   FOR EACH ROW
 BEGIN
    SET new.`updated_at` = Now();
   END
;;

/** ************************************************************************* *
 *  Messages
 ** ************************************************************************* */
DROP TABLE IF EXISTS `SiteContact`;
CREATE TABLE IF NOT EXISTS `SiteContact` (
    `id`            int(11)        UNSIGNED                     NOT NULL    AUTO_INCREMENT,
    `site_id`       int(11)        UNSIGNED                     NOT NULL    ,
    `name`          varchar(80)             CHARACTER SET utf8  NOT NULL    ,
    `mail`          varchar(160)            CHARACTER SET utf8  NOT NULL    ,
    `subject`       varchar(160)            CHARACTER SET utf8      NULL    ,
    `message`       text                                        NOT NULL    ,

    `is_read`       enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `is_mailed`     enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',

    `guid`          char(36)                CHARACTER SET utf8  NOT NULL    ,
    `hash`          char(40)                CHARACTER SET utf8  NOT NULL    ,

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`site_id`) REFERENCES `Site` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_sitecont_main` ON `SiteContact` (`is_deleted`, `site_id`);

DELIMITER ;;
DROP TRIGGER IF EXISTS `before_insert_sitecontact`;;
CREATE TRIGGER `before_insert_sitecontact`
BEFORE INSERT ON `SiteContact`
   FOR EACH ROW
 BEGIN
    IF new.`guid` IS NULL THEN SET new.`guid` = uuid(); END IF;
    SET new.`hash` = SHA1(new.`message`);
   END
;;
DROP TRIGGER IF EXISTS `before_update_sitecontact`;;
CREATE TRIGGER `before_update_sitecontact`
BEFORE UPDATE ON `SiteContact`
   FOR EACH ROW
 BEGIN
    SET new.`hash` = SHA1(new.`message`);
    SET new.`updated_at` = Now();
   END
;;

/** ************************************************************************* *
 *  Create Sequence (Statistics)
 ** ************************************************************************* */
DROP TABLE IF EXISTS `UsageStats`;
CREATE TABLE IF NOT EXISTS `UsageStats` (
    `id`            int(11)        UNSIGNED                     NOT NULL    AUTO_INCREMENT,
    `site_id`       int(11)        UNSIGNED                         NULL    ,
    `token_id`      int(11)        UNSIGNED                         NULL    ,

    `http_code`     smallint       UNSIGNED                     NOT NULL    DEFAULT 200,
    `request_type`  varchar(8)              CHARACTER SET utf8  NOT NULL    DEFAULT 'GET',
    `request_uri`   varchar(512)                                NOT NULL    ,
    `referrer`      varchar(1024)                                   NULL    ,

    `event_at`      timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `event_on`      varchar(10)             CHARACTER SET utf8  NOT NULL    ,
    `from_ip`       varchar(64)             CHARACTER SET utf8  NOT NULL    ,
    `device_id`     varchar(64)             CHARACTER SET utf8      NULL    ,

    `agent`         varchar(2048)                                   NULL    ,
    `platform`      varchar(64)                                     NULL    ,
    `browser`       varchar(64)                                 NOT NULL    DEFAULT 'unknown',
    `version`       varchar(64)                                     NULL    ,

    `seconds`       decimal(16,8)                               NOT NULL    DEFAULT 0,
    `sqlops`        smallint       UNSIGNED                     NOT NULL    DEFAULT 0,
    `message`       varchar(512)                                    NULL    ,

    `is_deleted`    enum('N','Y')           CHARACTER SET utf8  NOT NULL    DEFAULT 'N',
    `created_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    timestamp                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    FOREIGN KEY (`site_id`) REFERENCES `Site` (`id`),
    FOREIGN KEY (`token_id`) REFERENCES `Tokens` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `idx_stats_main` ON `UsageStats` (`event_on`, `site_id`);
CREATE INDEX `idx_stats_aux` ON `UsageStats` (`event_on`, `token_id`);
