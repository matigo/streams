DELIMITER ;;
DROP PROCEDURE IF EXISTS GetSyndicationContent;;
CREATE PROCEDURE GetSyndicationContent( IN `in_site_url` varchar(256), IN `in_show_article` char(1), IN `in_show_bookmark` char(1), IN `in_show_quotation` char(1), IN `in_show_note` char(1),
                                        IN `in_hide_mentions` char(1), IN `in_count` int(11), IN `in_beforeunix` int(11) )
BEGIN
    DECLARE `min_id` int(11);

    /** ********************************************************************** **
     *  Function returns the Visible items for the Syndication feeds
     *
     *  Usage: CALL GetSyndicationContent('matigo.ca', 'N', 'N', 'N', 'Y', 'Y', 75, 0);
     ** ********************************************************************** **/

    /* If the Count Only value is not cromulent, make it so */
    IF IFNULL(`in_count`, 0) NOT BETWEEN 1 AND 250 THEN
        SET `in_count` = 75;
    END IF;

    /* If the Before Unix value is not set, set it to current */
    IF IFNULL(`in_count`, 0) NOT BETWEEN 1 AND 250 THEN
        SET `in_count` = 75;
    END IF;

    /* Ensure the Requested Items are Properly Shown */
    IF IFNULL(`in_show_article`, '-') NOT IN ('N', 'Y') THEN
        SET `in_show_article` = 'N';
    END IF;
    IF IFNULL(`in_show_bookmark`, '-') NOT IN ('N', 'Y') THEN
        SET `in_show_bookmark` = 'N';
    END IF;
    IF IFNULL(`in_show_quotation`, '-') NOT IN ('N', 'Y') THEN
        SET `in_show_quotation` = 'N';
    END IF;
    IF IFNULL(`in_show_note`, '-') NOT IN ('N', 'Y') THEN
        SET `in_show_note` = 'N';
    END IF;
    IF IFNULL(`in_hide_mentions`, '-') NOT IN ('N', 'Y') THEN
        SET `in_hide_mentions` = 'Y';
    END IF;

    IF CONCAT(`in_show_article`, `in_show_bookmark`, `in_show_quotation`, `in_show_note`) = 'NNNN' THEN
        SET `in_show_article` = 'Y';
    END IF;

    IF CONCAT(`in_show_article`, `in_show_bookmark`, `in_show_quotation`, `in_show_note`) = 'NNNY' THEN
        SET `in_hide_mentions` = 'Y';
    END IF;

    /* Construct the Pre-defined Temporary Tables */
      DROP TEMPORARY TABLE IF EXISTS tmpTypes;
    CREATE TEMPORARY TABLE tmpTypes (
        `site_id`       int(11)        UNSIGNED NOT NULL    ,
        `https`         enum('N','Y')           NOT NULL    DEFAULT 'N',
        `site_url`      varchar(256)            NOT NULL    ,
        `type`          varchar(64)             NOT NULL    ,
        `is_default`    enum('N','Y')           NOT NULL    DEFAULT 'N',
        PRIMARY KEY (`site_id`, `type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

      DROP TEMPORARY TABLE IF EXISTS tmpPosts;
    CREATE TEMPORARY TABLE tmpPosts (
        `post_id`       int(11)        UNSIGNED NOT NULL    ,
        `has_mentions`  enum('N','Y')           NOT NULL    DEFAULT 'N',
        `publish_at`    timestamp               NOT NULL    ,
        PRIMARY KEY (`post_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    /* Collect the Types that are requested */
    INSERT INTO tmpTypes (`site_id`, `https`, `site_url`, `type`, `is_default`)
    SELECT su.`site_id`, si.`https`,
           (SELECT z.`url` FROM `SiteUrl` z
             WHERE z.`is_deleted` = 'N' and z.`site_id` = su.`site_id`
             ORDER BY z.`is_active` DESC, z.`id` DESC LIMIT 1) as `site_url`,
           base.`type`, IFNULL(IFNULL(pref.`value`, sm.`value`), base.`is_default`) as `is_default`
      FROM `SiteUrl` su INNER JOIN `Site` si ON su.`site_id` = si.`id`
                        INNER JOIN (SELECT 'post.article' as `type`, 'N' as `is_default` UNION ALL
                                    SELECT 'post.bookmark' as `type`, 'N' as `is_default` UNION ALL
                                    SELECT 'post.quotation' as `type`, 'N' as `is_default` UNION ALL
                                    SELECT 'post.note' as `type`, 'N' as `is_default`) base ON base.`is_default` = 'N'
                   LEFT OUTER JOIN `SiteMeta` sm ON sm.`is_deleted` = 'N' and base.`type` = REPLACE(sm.`key`, 'show_', 'post.') and sm.`site_id` = si.`id`
                   LEFT OUTER JOIN (SELECT 'post.article' as `type`, CASE WHEN `in_show_article` IN ('Y', 'N') THEN `in_show_article` ELSE NULL END as `value` UNION ALL
                                    SELECT 'post.bookmark' as `type`, CASE WHEN `in_show_bookmark` IN ('Y', 'N') THEN `in_show_bookmark` ELSE NULL END as `value` UNION ALL
                                    SELECT 'post.quotation' as `type`, CASE WHEN `in_show_quotation` IN ('Y', 'N') THEN `in_show_quotation` ELSE NULL END as `value` UNION ALL
                                    SELECT 'post.note' as `type`, CASE WHEN `in_show_note` IN ('Y', 'N') THEN `in_show_note` ELSE NULL END as `value`) pref ON base.`type` = pref.`type`
     WHERE su.`is_deleted` = 'N' and si.`is_deleted` = 'N' and su.`url` = `in_site_url`;

    /* Collect the applicable Posts from the last six months only */
    INSERT INTO tmpPosts (`post_id`, `has_mentions`, `publish_at`)
    SELECT po.`id` as `post_id`, IFNULL((SELECT 'Y' FROM `PostMention` z WHERE z.`is_deleted` = 'N' and z.`post_id` = po.`id` LIMIT 1), 'N') as `has_mentions`, po.`publish_at`
      FROM tmpTypes tp INNER JOIN `Channel` ch ON tp.`site_id` = ch.`site_id`
                       INNER JOIN `Post` po ON ch.`id` = po.`channel_id` AND tp.`type` = po.`type`
                       INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
     WHERE ch.`is_deleted` = 'N' and po.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and tp.`is_default` = 'Y'
       and ch.`privacy_type` = 'visibility.public' and ch.`type` = 'channel.site' and po.`privacy_type` = 'visibility.public'
       and Now() BETWEEN po.`publish_at` AND IFNULL(po.`expires_at`, DATE_ADD(Now(), INTERVAL 1 SECOND))
     ORDER BY po.`publish_at` DESC;

    /* Collect the Applicable Posts */
    SELECT po.`persona_id`, pa.`last_name`, pa.`first_name`, pa.`display_name`, pa.`name` as `handle`,
           CONCAT(CASE WHEN tp.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', tp.`site_url`, '/avatars/', pa.`avatar_img`) as `avatar_url`,
           po.`id` as `post_id`,
           IFNULL(po.`title`, (SELECT z.`value` FROM `PostMeta` z WHERE z.`is_deleted` = 'N' and z.`is_private` = 'N' and z.`key` = 'source_title' and z.`post_id` = po.`id` LIMIT 1)) as `post_title`,
           CONCAT(CASE WHEN tp.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', tp.`site_url`, po.`canonical_url`) as `post_url`,
           (SELECT z.`value` FROM `PostMeta` z WHERE z.`is_deleted` = 'N' and z.`is_private` = 'N' and z.`key` = 'source_url' and z.`post_id` = po.`id` LIMIT 1) as `source_url`,
           po.`type` as `post_type`, po.`guid` as `post_guid`, po.`hash`, po.`value` as `post_text`,
           IFNULL((SELECT MAX(CASE WHEN fi.`type` LIKE 'audio%' THEN 'Y' ELSE 'N' END) as `has_audio`
                     FROM `PostFile` pf INNER JOIN `File` fi ON pf.`file_id` = fi.`id`
                    WHERE fi.`is_deleted` = 'N' and IFNULL(fi.`expires_at`, DATE_ADD(Now(), INTERVAL 1 MINUTE)) > Now()
                      and pf.`is_deleted` = 'N' and pf.`post_id` = po.`id`), 'N') as `has_audio`,
           DATE_FORMAT(po.`publish_at`, '%Y-%m-%dT%H:%i:%sZ') as `publish_at`,
           DATE_FORMAT(po.`updated_at`, '%Y-%m-%dT%H:%i:%sZ') as `updated_at`
      FROM `tmpTypes` tp INNER JOIN `Channel` ch ON tp.`site_id` = ch.`site_id`
                         INNER JOIN `Post` po ON ch.`id` = po.`channel_id` AND tp.`type` = po.`type`
                         INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
                         INNER JOIN `tmpPosts` pp ON po.`id` = pp.`post_id`
     WHERE ch.`is_deleted` = 'N' and po.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and tp.`is_default` = 'Y'
       and ch.`privacy_type` = 'visibility.public' and ch.`type` = 'channel.site' and po.`privacy_type` = 'visibility.public'
       and Now() BETWEEN po.`publish_at` AND IFNULL(po.`expires_at`, DATE_ADD(Now(), INTERVAL 1 SECOND))
     ORDER BY po.`publish_at` DESC
     LIMIT `in_count`;

   DROP TEMPORARY TABLE tmpTypes;
   DROP TEMPORARY TABLE tmpPosts;
END;;
DELIMITER ;