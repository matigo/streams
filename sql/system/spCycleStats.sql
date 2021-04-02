DELIMITER ;;
DROP PROCEDURE IF EXISTS CycleStats;;
CREATE PROCEDURE CycleStats()
BEGIN

   /** ********************************************************************** **
     *  Function creates a Site and all the requisite data for it to function.
     *
     *  Usage: CALL CycleStats();
     ** ********************************************************************** **/

    ALTER TABLE `UsageStats` DROP FOREIGN KEY `usagestats_ibfk_1`;
    ALTER TABLE `UsageStats` DROP FOREIGN KEY `usagestats_ibfk_2`;

    SELECT CONCAT('RENAME TABLE `UsageStats` TO `UsageStats', DATE_FORMAT(DATE_SUB(Now(), INTERVAL 1 HOUR), '%Y%m'), "`;") INTO @sqlStr;
    PREPARE stmt FROM @sqlStr;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

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

END ;;
DELIMITER ;