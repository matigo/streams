DELIMITER ;;
DROP PROCEDURE IF EXISTS GetPublicProfile;;
CREATE PROCEDURE GetPublicProfile( IN `in_account_id` int(11), IN `in_persona_guid` varchar(36) )
BEGIN

    /** ********************************************************************** **
     *  Function returns the public profile of a Persona based on the GUID
     *      provided.
     *
     *  Usage: CALL GetPublicProfile(1, 'f6c797cc-8a79-5259-bbd4-e88de728b90e');
     ** ********************************************************************** **/

    /* If the Persona GUID Length is Wrong, Exit */
    IF LENGTH(IFNULL(`in_persona_guid`, '')) <> 36 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Persona GUID Supplied';
    END IF;

      DROP TEMPORARY TABLE IF EXISTS tmpPersona;
    CREATE TEMPORARY TABLE tmpPersona (
        `name`              varchar(40)             NOT NULL    ,
        `last_name`         varchar(80)             NOT NULL    ,
        `first_name`        varchar(80)             NOT NULL    ,
        `display_name`      varchar(80)             NOT NULL    ,
        `avatar_img`        varchar(80)             NOT NULL    ,
        `persona_guid`      char(36)                NOT NULL    ,
        `site_url`          varchar(256)                NULL    ,
        `persona_bio`       varchar(2048)               NULL    ,

        `follows`           enum('N','Y')           NOT NULL    DEFAULT 'N',
        `is_muted`          enum('N','Y')           NOT NULL    DEFAULT 'N',
        `is_blocked`        enum('N','Y')           NOT NULL    DEFAULT 'N',
        `is_starred`        enum('N','Y')           NOT NULL    DEFAULT 'N',

        `pin_type`          varchar(64)             NOT NULL    DEFAULT 'pin.none',
        `timezone`          varchar(64)                 NULL    ,
        `created_at`        timestamp               NOT NULL    ,


        `count_article`     int(11)        UNSIGNED NOT NULL    DEFAULT 0,
        `count_bookmark`    int(11)        UNSIGNED NOT NULL    DEFAULT 0,
        `count_location`    int(11)        UNSIGNED NOT NULL    DEFAULT 0,
        `count_quotation`   int(11)        UNSIGNED NOT NULL    DEFAULT 0,
        `count_note`        int(11)        UNSIGNED NOT NULL    DEFAULT 0,
        `count_photo`       int(11)        UNSIGNED NOT NULL    DEFAULT 0,

        `recent_at`         timestamp                   NULL    ,
        `days`              int(11)        UNSIGNED NOT NULL    DEFAULT 0,
        `is_you`            enum('N','Y')           NOT NULL    DEFAULT 'N'
    ) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    /* Collect the Public Profile */
    INSERT INTO `tmpPersona` (`name`, `last_name`, `first_name`, `display_name`, `avatar_img`, `persona_guid`, `site_url`, `persona_bio`,
                              `follows`, `is_muted`, `is_blocked`, `is_starred`, `pin_type`, `timezone`, `created_at`, `days`, `is_you`)
    SELECT DISTINCT pa.`name`, pa.`last_name`, pa.`first_name`, pa.`display_name`, pa.`avatar_img`, pa.`guid` as `persona_guid`,
           (SELECT CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`) as `site_url`
              FROM `SiteUrl` su INNER JOIN `Site` si ON su.`site_id` = si.`id`
                                INNER JOIN `PersonaMeta` z ON si.`id` = CAST(z.`value` AS UNSIGNED)
             WHERE su.`is_deleted` = 'N' and si.`is_deleted` = 'N' and z.`is_deleted` = 'N' and su.`is_active` = 'Y'
               and z.`key` = 'site.default' and z.`value` NOT IN ('', '0') and z.`persona_id` = pa.`id`) as `site_url`,
           (SELECT z.`value` FROM `PersonaMeta` z WHERE z.`is_deleted` = 'N' and z.`key` = 'persona.bio' and z.`persona_id` = pa.`id`) as `persona_bio`,

           IFNULL(pr.`follows`, 'N') as `follows`,
           IFNULL(pr.`is_muted`, 'N') as `is_muted`,
           IFNULL(pr.`is_blocked`, 'N') as `is_blocked`,
           IFNULL(pr.`is_starred`, 'N') as `is_starred`,
           CASE WHEN IFNULL(pr.`pin_type`, '') = '' THEN 'pin.none' ELSE pr.`pin_type` END as `pin_type`,

           acct.`timezone`, pa.`created_at`, DATEDIFF(DATE_FORMAT(Now(), '%Y-%m-%d 00:00:00'), DATE_FORMAT(pa.`created_at`, '%Y-%m-%d 00:00:00')) as `days`,
           CASE WHEN acct.`id` = `in_account_id` THEN 'Y' ELSE 'N' END as `is_you`
      FROM `Account` acct INNER JOIN `Persona` pa ON acct.`id` = pa.`account_id`
                          INNER JOIN `Account` zme ON zme.`is_deleted` = 'N'
                          INNER JOIN `Persona` zpa ON zme.`id` = zpa.`account_id`
                  LEFT OUTER JOIN `PersonaRelation` pr ON zpa.`id` = pr.`persona_id` AND pa.`id` = pr.`related_id` AND pr.`is_deleted` = 'N'
     WHERE pa.`is_deleted` = 'N' and acct.`is_deleted` = 'N' and pa.`guid` = `in_persona_guid`
       and zpa.`is_deleted` = 'N' and zme.`is_deleted` = 'N' and zme.`id` = `in_account_id`;

    /* Add the Post Counts */
    UPDATE `tmpPersona` tmp INNER JOIN (SELECT pa.`guid`,
                                               SUM(CASE WHEN po.`type` = 'post.article' THEN 1 ELSE 0 END) as `count_article`,
                                               SUM(CASE WHEN po.`type` = 'post.bookmark' THEN 1 ELSE 0 END) as `count_bookmark`,
                                               SUM(CASE WHEN po.`type` = 'post.quotation' THEN 1 ELSE 0 END) as `count_quotation`,
                                               SUM(CASE WHEN po.`type` = 'post.note' THEN 1 ELSE 0 END) as `count_note`,
                                               MAX(CASE WHEN po.`publish_at` <= Now() THEN po.`publish_at` ELSE NULL END) as `recent_at`
                                          FROM `Account` acct INNER JOIN `Persona` pa ON acct.`id` = pa.`account_id`
                                                              INNER JOIN `Post` po ON pa.`id` = po.`persona_id`
                                         WHERE acct.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and po.`is_deleted` = 'N'
                                           and pa.`guid` = `in_persona_guid`
                                         GROUP BY pa.`guid`) cnt ON tmp.`persona_guid` = cnt.`guid`
       SET tmp.`count_article` = cnt.`count_article`,
           tmp.`count_bookmark` = cnt.`count_bookmark`,
           tmp.`count_quotation` = cnt.`count_quotation`,
           tmp.`count_note` = cnt.`count_note`,
           tmp.`recent_at` = cnt.`recent_at`;

    /* Output the Profile */
    SELECT tmp.`name`, tmp.`last_name`, tmp.`first_name`, tmp.`display_name`, tmp.`avatar_img`, tmp.`persona_guid`, tmp.`site_url`, tmp.`persona_bio`,
           MAX(tmp.`follows`) as `follows`, MAX(tmp.`is_muted`) as `is_muted`, MAX(tmp.`is_blocked`) as `is_blocked`, MAX(tmp.`is_starred`) as `is_starred`,
           CASE WHEN MAX(CASE WHEN tmp.`pin_type` <> 'pin.none' THEN tmp.`pin_type` ELSE '' END) <> ''
                THEN MAX(CASE WHEN tmp.`pin_type` <> 'pin.none' THEN tmp.`pin_type` ELSE '' END)
                ELSE 'pin.none' END as `pin_type`,
           tmp.`timezone`, tmp.`created_at`,
           tmp.`count_article`, tmp.`count_bookmark`, tmp.`count_location`, tmp.`count_quotation`, tmp.`count_note`, tmp.`count_photo`,
           tmp.`recent_at`, tmp.`days`, tmp.`is_you`
      FROM `tmpPersona` tmp
     GROUP BY tmp.`name`, tmp.`last_name`, tmp.`first_name`, tmp.`display_name`, tmp.`avatar_img`, tmp.`persona_guid`, tmp.`site_url`, tmp.`persona_bio`,
              tmp.`timezone`, tmp.`created_at`,
              tmp.`count_article`, tmp.`count_bookmark`, tmp.`count_location`, tmp.`count_quotation`, tmp.`count_note`, tmp.`count_photo`,
              tmp.`recent_at`, tmp.`days`, tmp.`is_you`;

END ;;
DELIMITER ;