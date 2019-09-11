DELIMITER ;;
DROP PROCEDURE IF EXISTS GetExportForDayOne;;
CREATE PROCEDURE GetExportForDayOne( IN `in_account_id` int(11), IN `in_unlock_key` varchar(36), IN `in_page` int(11) )
BEGIN
    DECLARE `x_limit`       int(11);
    DECLARE `x_start`       int(11);
    DECLARE `x_account_id`  int(11);

   /** ********************************************************************** **
     *  Function collects the requisite data for
     *
     *  Usage: CALL GetExportForDayOne(1, '', 0);
     ** ********************************************************************** **/

    SET `x_limit` = 10000;
    SET `x_start` = `x_limit` * IFNULL(`in_page`, 0);

    IF ( IFNULL(`x_start`, 0) < 0 ) THEN
        SET `x_start` = 0;
    END IF;

    /* Determine if the Unlock Key is valid or not */
    SELECT acct.`id` INTO `x_account_id`
      FROM `Account` acct INNER JOIN `AccountMeta` am ON acct.`id` = am.`account_id`
     WHERE acct.`is_deleted` = 'N' and am.`is_deleted` = 'N'
       and am.`key` = 'export.access_key' and am.`updated_at` >= DATE_SUB(Now(), INTERVAL 1 DAY)
       and am.`value` = `in_unlock_key` and am.`account_id` = `in_account_id`
     LIMIT 1;

    /* Create the Temporary Tables that Will Be Used */
      DROP TEMPORARY TABLE IF EXISTS tmp;
    CREATE TEMPORARY TABLE IF NOT EXISTS tmp (
        `post_id`       int(11)        UNSIGNED NOT NULL    ,
        `title`         varchar(512)                NULL    ,
        `post_text`     text                    NOT NULL    ,
        `uuid`          char(36)                NOT NULL    ,
        `guid`          char(36)                NOT NULL    ,

        `is_starred`    enum('N','Y')           NOT NULL    DEFAULT 'N',
        `latitude`      decimal(16,8)               NULL    ,
        `longitude`     decimal(16,8)               NULL    ,
        `altitude`      decimal(16,8)               NULL    ,
        `timezone`      varchar(40)             NOT NULL    DEFAULT 'UTC',
        `tags`          varchar(2048)               NULL    ,

        `created_at`    timestamp               NOT NULL    DEFAULT CURRENT_TIMESTAMP,
        `updated_at`    timestamp               NOT NULL    DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`post_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

      DROP TEMPORARY TABLE IF EXISTS tmpTags;
    CREATE TEMPORARY TABLE IF NOT EXISTS tmpTags (
        `post_id`       int(11)        UNSIGNED NOT NULL    ,
        `tags`          varchar(2048)               NULL    ,
        PRIMARY KEY (`post_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    /* Collect the Posts */
    INSERT INTO tmp (`post_id`, `title`, `post_text`, `uuid`, `guid`, `timezone`, `created_at`, `updated_at`)
    SELECT DISTINCT po.`id` as `post_id`, po.`title`, po.`value` as `post_text`,
           UPPER(MD5(po.`guid`)) as `uuid`, po.`guid`, IFNULL(acct.`timezone`, 'UTC') as `timezone`,
           po.`publish_at` as `created_at`, CASE WHEN po.`updated_at` < po.`publish_at` THEN po.`publish_at` ELSE po.`updated_at` END as `updated_at`
      FROM `Account` acct INNER JOIN `Persona` pa ON acct.`id` = pa.`account_id`
                          INNER JOIN `Channel` ch ON pa.`account_id` = ch.`account_id`
                          INNER JOIN `Post` po ON ch.`id` = po.`channel_id`
                          INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                          INNER JOIN `SiteUrl` su ON si.`id` = su.`site_id` AND su.`is_active` = 'Y'
     WHERE acct.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and po.`is_deleted` = 'N' and si.`is_deleted` = 'N'
       and IFNULL(po.`expires_at`, DATE_ADD(Now(), INTERVAL 1 DAY)) >= Now()
       and acct.`id` = `x_account_id`
     ORDER BY po.`publish_at`;

    /* Collect and Assign Tags */
    INSERT INTO tmpTags (`post_id`, `tags`)
    SELECT pt.`post_id`, GROUP_CONCAT(TRIM(pt.`value`)) as `tags`
      FROM `PostTags` pt INNER JOIN tmp ON pt.`post_id` = tmp.`post_id`
     WHERE pt.`is_deleted` = 'N' and LENGTH(pt.`value`) > 1
     GROUP BY pt.`post_id`;

    UPDATE `tmp` tt INNER JOIN `tmpTags` tg ON tt.`post_id` = tg.`post_id`
       SET tt.`tags` = tg.`tags`
     WHERE LENGTH(tg.`tags`) > 1;
      DROP TEMPORARY TABLE IF EXISTS tmpTags;

    /* Set whether the Post is starred or not */
    UPDATE `Persona` pa INNER JOIN `PostAction` act ON pa.`id` = act.`persona_id`
                        INNER JOIN tmp ON act.`post_id` = tmp.`post_id`
       SET tmp.`is_starred` = act.`is_starred`
     WHERE act.`is_starred` = 'Y';

    /* Set the Geo-Location Information */
    UPDATE tmp INNER JOIN (SELECT pm.`post_id`,
                                  MAX(CASE WHEN pm.`key` = 'geo_altitude' THEN pm.`value` ELSE NULL END) as `altitude`,
                                  MAX(CASE WHEN pm.`key` = 'geo_latitude' THEN pm.`value` ELSE NULL END) as `latitude`,
                                  MAX(CASE WHEN pm.`key` = 'geo_longitude' THEN pm.`value` ELSE NULL END) as `longitude`
                             FROM `PostMeta` pm
                            WHERE pm.`is_deleted` = 'N'
                            GROUP BY pm.`post_id`) geo ON tmp.`post_id` = geo.`post_id`
       SET tmp.`altitude` = geo.`altitude`,
           tmp.`latitude` = geo.`latitude`,
           tmp.`longitude` = geo.`longitude`
     WHERE geo.`latitude` IS NOT NULL and geo.`longitude` IS NOT NULL;

    /* Output the Content */
    SELECT t.`title`, t.`post_text`, t.`uuid`, t.`guid`, t.`is_starred`, t.`latitude`, t.`longitude`, t.`altitude`, t.`timezone`, t.`tags`,
           t.`created_at`, t.`updated_at`
      FROM `tmp` t
     ORDER BY `created_at`
     LIMIT `x_start`, `x_limit`;
END;;
DELIMITER ;