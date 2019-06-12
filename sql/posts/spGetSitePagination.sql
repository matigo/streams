DELIMITER ;;
DROP PROCEDURE IF EXISTS GetSitePagination;;
CREATE PROCEDURE GetSitePagination( IN `in_account_id` int(11), IN `in_site_guid` varchar(36), IN `in_site_token` varchar(512), IN `in_canon_url` varchar(512),
                                    IN `in_pgroot` varchar(256), IN `in_obj` varchar(256), IN `in_tag` varchar(256) )
BEGIN
    DECLARE `x_exact` enum('N','Y');

    /** ********************************************************************** **
     *  Function returns the basic information to build the pagingation
     *      elements in the front end.
     *
     *  Usage: CALL GetSitePagination(1, 'cc5346ea-9358-df5c-90ea-e27c343e4843', '', '/quotation', 'quotation', 'quotation', '');
     ** ********************************************************************** **/

    DECLARE EXIT HANDLER FOR SQLEXCEPTION, SQLWARNING
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    /* If the Persona GUID is bad, Exit */
    IF LENGTH(IFNULL(`in_site_guid`, '')) <> 36 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Site GUID Provided';
    END IF;

    /* Ensure We Have Access to the Site */
    DROP TEMPORARY TABLE IF EXISTS hashes;
    CREATE TEMPORARY TABLE hashes AS
    SELECT CAST(SHA2(CONCAT(si.`guid`, '.', UNIX_TIMESTAMP(su.`updated_at`), '.', DATE_FORMAT(DATE_SUB(Now(), INTERVAL cnt.`num` HOUR), '%Y-%m-%d %H:00:00')), 256) AS CHAR(512)) as `hash`
      FROM `SiteUrl` su INNER JOIN `Site` si ON su.`site_id` = si.`id`
                        INNER JOIN `Channel` ch ON si.`id` = ch.`site_id`
                        INNER JOIN (SELECT 0 as `num` UNION ALL SELECT  1 as `num` UNION ALL SELECT  2 as `num`) cnt ON `num` >= 0
     WHERE ch.`is_deleted` = 'N' and ch.`privacy_type` = 'visibility.password'
       and su.`is_deleted` = 'N' and su.`is_active` = 'Y'
       and si.`is_deleted` = 'N' and si.`guid` = `in_site_guid`
     UNION ALL
    SELECT CAST('' AS CHAR(512)) as `hash`
      FROM `SiteUrl` su INNER JOIN `Site` si ON su.`site_id` = si.`id`
                        INNER JOIN `Channel` ch ON si.`id` = ch.`site_id`
                        INNER JOIN (SELECT 0 as `num` UNION ALL SELECT  1 as `num` UNION ALL SELECT  2 as `num`) cnt ON `num` >= 0
     WHERE ch.`is_deleted` = 'N' and ch.`privacy_type` <> 'visibility.password'
       and su.`is_deleted` = 'N' and su.`is_active` = 'Y'
       and si.`is_deleted` = 'N' and si.`guid` = `in_site_guid`;

    IF CAST(IFNULL(`in_site_token`, '') AS CHAR(512)) NOT IN (SELECT `hash` FROM hashes) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Access to this Site is Denied';
    END IF;

    /* If there is a canonical URL, check to see if it's an exact match */
    IF LENGTH(IFNULL(`in_canon_url`, '')) > 1 THEN
        SELECT CASE WHEN COUNT(po.`id`) > 0 THEN 'Y' ELSE 'N' END as `is_exact` INTO `x_exact`
          FROM `Persona` pa INNER JOIN `Post` po ON pa.`id` = po.`persona_id`
                            INNER JOIN `Channel` ch ON po.`channel_id` = ch.`id`
                            INNER JOIN `Site` si ON ch.`site_id` = si.`id`
         WHERE po.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and si.`is_deleted` = 'N'
           and Now() BETWEEN po.`publish_at` AND IFNULL(po.`expires_at`, DATE_ADD(Now(), INTERVAL 1 MINUTE))
           and po.`canonical_url` = `in_canon_url` and si.`guid` = `in_site_guid`
           and 'Y' = CASE WHEN po.`privacy_type` = 'visibility.public' THEN 'Y'
                          WHEN pa.`account_id` = `in_account_id` THEN 'Y'
                          ELSE 'N' END
         LIMIT 1;
    END IF;

    IF IFNULL(`x_exact`, 'N') = 'Y' THEN
        SELECT 1 as `post_count`, 1 as `page_count`, `x_exact` as `is_exact`;
    END IF;

    /* If We're Here, Let's Build the Pagination Numbers */
    DROP TEMPORARY TABLE IF EXISTS vis;
    CREATE TEMPORARY TABLE vis AS
    SELECT sm.`site_id`, 0 as `sort_id`,
           CASE WHEN MAX(CASE WHEN sm.`key` = 'show_note' THEN sm.`value` ELSE '-' END) <> '-'
                THEN MAX(CASE WHEN sm.`key` = 'show_note' THEN sm.`value` ELSE '-' END) ELSE 'Y' END as `show_note`,
           CASE WHEN MAX(CASE WHEN sm.`key` = 'show_article' THEN sm.`value` ELSE '-' END) <> '-'
                THEN MAX(CASE WHEN sm.`key` = 'show_article' THEN sm.`value` ELSE '-' END) ELSE 'Y' END as `show_article`,
           CASE WHEN MAX(CASE WHEN sm.`key` = 'show_bookmark' THEN sm.`value` ELSE '-' END) <> '-'
                THEN MAX(CASE WHEN sm.`key` = 'show_bookmark' THEN sm.`value` ELSE '-' END) ELSE 'Y' END as `show_bookmark`,
           CASE WHEN MAX(CASE WHEN sm.`key` = 'show_location' THEN sm.`value` ELSE '-' END) <> '-'
                THEN MAX(CASE WHEN sm.`key` = 'show_location' THEN sm.`value` ELSE '-' END) ELSE 'Y' END as `show_location`,
           CASE WHEN MAX(CASE WHEN sm.`key` = 'show_quotation' THEN sm.`value` ELSE '-' END) <> '-'
                THEN MAX(CASE WHEN sm.`key` = 'show_quotation' THEN sm.`value` ELSE '-' END) ELSE 'Y' END as `show_quotation`
      FROM `SiteMeta` sm INNER JOIN `Site` si ON sm.`site_id` = si.`id`
     WHERE sm.`is_deleted` = 'N' and si.`is_deleted` = 'N' and si.`guid` = `in_site_guid`
     GROUP BY sm.`site_id`
     UNION ALL
    SELECT si.`id` as `site_id`, 1 as `sort_id`, 'N' as `show_note`, 'Y' as `show_article`, 'Y' as `show_bookmark`, 'N' as `show_location`, 'Y' as `show_quotation`
      FROM `Site` si
     WHERE si.`is_deleted` = 'N' and si.`guid` = `in_site_guid`
     ORDER BY `sort_id`
     LIMIT 1;

    /* Are We Looking for Tags? */
    IF LENGTH(IFNULL(`in_tag`, '')) > 0 THEN
        SELECT COUNT(p.`id`) as `post_count`, ROUND((COUNT(p.`id`) / 10) + 0.499, 0) as `page_count`, 'N' as `is_exact`
          FROM `Site` si INNER JOIN `Channel` ch ON si.`id` = ch.`site_id`
                         INNER JOIN `Post` p ON ch.`id` = p.`channel_id`
                         INNER JOIN `PostTags` pt ON p.`id` = pt.`post_id`
         WHERE si.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and pt.`is_deleted` = 'N' and p.`is_deleted` = 'N'
           and si.`guid` = `in_site_guid` and pt.`key` = LOWER(`in_tag`)
         LIMIT 1;
    END IF;

    /* If We're Here, Look At Everything */
    SELECT COUNT(DISTINCT pg.`post_id`) as `post_count`, ROUND((COUNT(DISTINCT pg.`post_id`) / 10) + 0.499, 0) as `page_count`, 'N' as `is_exact`
      FROM (SELECT po.`id` as `post_id`,
                   LEAST(
                   CASE WHEN ch.`privacy_type` <> 'visibility.public' AND si.`account_id` = `in_account_id` THEN 'Y'
                        WHEN ch.`privacy_type` <> 'visibility.public' THEN 'N'
                        WHEN po.`privacy_type` = 'visibility.none' AND pa.`account_id` <> `in_account_id` THEN 'N'
                        WHEN po.`privacy_type` <> 'visibility.public' THEN IFNULL(tmp.`can_read`, 'N')
                        ELSE 'Y' END,
                   CASE WHEN IFNULL(`in_obj`, '') IN ('article', 'quotation', 'location', 'bookmark', 'note') AND po.`type` = LEFT(CONCAT('post.', IFNULL(`in_obj`, '')), 64) THEN 'Y'
                        WHEN po.`type` = 'post.note' THEN vis.`show_note`
                        WHEN po.`type` = 'post.article' THEN vis.`show_article`
                        WHEN po.`type` = 'post.bookmark' THEN vis.`show_bookmark`
                        WHEN po.`type` = 'post.location' THEN vis.`show_location`
                        WHEN po.`type` = 'post.quotation' THEN vis.`show_quotation`
                        ELSE 'N' END,
                   CASE WHEN po.`privacy_type` = 'visibility.public' THEN 'Y'
                        WHEN pa.`account_id` = `in_account_id` THEN 'Y'
                        ELSE 'N' END,
                   CASE WHEN IFNULL(`in_canon_url`, '') IN ('', '/') THEN 'Y'
                        WHEN LEFT(po.`canonical_url`, LENGTH(CONCAT('/', IFNULL(`in_pgroot`, ''), '/'))) = CONCAT('/', IFNULL(`in_pgroot`, ''), '/') THEN 'Y'
                        WHEN IFNULL(`in_obj`, '') IN ('article', 'quotation', 'location', 'bookmark', 'note') AND po.`type` = LEFT(CONCAT('post.', IFNULL(`in_obj`, '')), 64) THEN 'Y'
                        ELSE 'N' END) AS `is_visible`
              FROM `Persona` pa INNER JOIN `Post` po ON pa.`id` = po.`persona_id`
                                INNER JOIN `Channel` ch ON po.`channel_id` = ch.`id`
                                INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                                INNER JOIN vis ON si.`id` = vis.`site_id`
                           LEFT OUTER JOIN (SELECT ca.`persona_id`, ca.`channel_id`, ca.`can_read`, ca.`can_write`
                                              FROM `Account` a INNER JOIN `Persona` pa ON a.`id` = pa.`account_id`
                                                               INNER JOIN `ChannelAuthor` ca ON pa.`id` = ca.`persona_id`
                                             WHERE ca.`is_deleted` = 'N' and pa.`is_deleted` = 'N'
                                               and a.`id` = `in_account_id`) tmp ON po.`persona_id` = tmp.`persona_id` AND ch.`id` = tmp.`channel_id`
             WHERE po.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and si.`is_deleted` = 'N'
               and LOWER(IFNULL(`in_pgroot`, '')) IN ('', 'note', 'article', 'bookmark', 'location', 'quotation')
               and IFNULL(po.`expires_at`, Now()) >= Now() and si.`guid` = `in_site_guid`
             ORDER BY po.`publish_at` DESC) pg
     WHERE pg.`is_visible` = 'Y';
END ;;
DELIMITER ;