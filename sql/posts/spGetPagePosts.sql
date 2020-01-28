DELIMITER ;;
DROP PROCEDURE IF EXISTS GetPagePosts;;
CREATE PROCEDURE GetPagePosts( IN `in_account_id` int(11), IN `in_site_guid` varchar(36), IN `in_canon_url` varchar(2048), IN `in_object` varchar(2048), IN `in_tag` varchar(256), IN `site_pass` varchar(512), IN `in_count` int(11), IN `in_page` int(11) )
BEGIN
    DECLARE `pass_valid` enum('N','Y');
    DECLARE `post_types` varchar(512);
    DECLARE `post_id`    int(11);
    DECLARE `site_id`    int(11);
    DECLARE `min_id`     int(11);

    /** ********************************************************************** **
     *  Function returns the Visible Timeline for a Given Persona GUID
     *
     *  Usage: CALL GetPagePosts(0, 'cc5346ea-9358-df5c-90ea-e27c343e4843', '/', '', '', '', 10, 0);
     ** ********************************************************************** **/

    /* If the Count Only value is not cromulent, make it so */
    IF IFNULL(`in_count`, 0) NOT BETWEEN 1 AND 25 THEN
        SET `in_count` = 10;
    END IF;

    /* Get the id and type filters for the Site */
    SELECT tmp.`site_id`, tmp.`show_types` INTO `site_id`, `post_types`
      FROM (SELECT sm.`site_id`,
                   CONCAT(CASE WHEN `in_object` = 'note' THEN 'post.note'
                               WHEN `in_object` = '' AND MAX(CASE WHEN sm.`key` = 'show_note' THEN sm.`value` ELSE '-' END) = 'Y'
                                    THEN MAX(CASE WHEN sm.`key` = 'show_note' THEN 'post.note' ELSE '' END) ELSE '' END, ',',
                          CASE WHEN `in_object` = 'article' THEN 'post.article'
                               WHEN `in_object` = '' AND MAX(CASE WHEN sm.`key` = 'show_article' THEN sm.`value` ELSE '-' END) = 'Y'
                                    THEN MAX(CASE WHEN sm.`key` = 'show_article' THEN 'post.article' ELSE '' END) ELSE '' END, ',',
                          CASE WHEN `in_object` = 'bookmark' THEN 'post.bookmark'
                               WHEN `in_object` = '' AND MAX(CASE WHEN sm.`key` = 'show_bookmark' THEN sm.`value` ELSE '-' END) = 'Y'
                                    THEN MAX(CASE WHEN sm.`key` = 'show_bookmark' THEN 'post.bookmark' ELSE '' END) ELSE '' END, ',',
                          CASE WHEN `in_object` = 'quotation' THEN 'post.quotation'
                               WHEN `in_object` = '' AND MAX(CASE WHEN sm.`key` = 'show_quotation' THEN sm.`value` ELSE '-' END) = 'Y'
                                    THEN MAX(CASE WHEN sm.`key` = 'show_quotation' THEN 'post.quotation' ELSE '' END) ELSE '' END, ',',
                          CASE WHEN `in_object` = 'location' THEN 'post.location'
                               WHEN `in_object` = '' AND MAX(CASE WHEN sm.`key` = 'show_location' THEN sm.`value` ELSE '-' END) = 'Y'
                                    THEN MAX(CASE WHEN sm.`key` = 'show_location' THEN 'post.location' ELSE '' END) ELSE '' END) as `show_types`
              FROM `SiteMeta` sm INNER JOIN `Site` z ON sm.`site_id` = z.`id`
             WHERE sm.`is_deleted` = 'N' and z.`is_deleted` = 'N' and z.`guid` = `in_site_guid`
             GROUP BY sm.`site_id`
             UNION ALL
            SELECT 4294967295 as `site_id`, 'post.article, post.bookmark, post.quotation' as `show_types`
              FROM `Site` z
             WHERE z.`is_deleted` = 'N' and z.`guid` = `in_site_guid`
             ORDER BY `site_id`
             LIMIT 1) tmp
     ORDER BY tmp.`site_id`;

    /* If the Type filter is Empty, Add Blog Posts (post.article) */
    IF LENGTH(IFNULL(`post_types`, '')) < 9 THEN
        SET `post_types` = 'post.article';
    END IF;

    /* If we have a specific URL, ensure every post type is acceptable */
    IF IFNULL(`in_canon_url`, '') NOT IN ('/article', '/bookmark', '/note', '/quotation', '/location', '/', '') AND IFNULL(`in_tag`, '') = '' THEN
        SET `post_types` = 'post.article, post.bookmark, post.note, post.quotation, post.location, post.page';

        SELECT tmp.`id` INTO `post_id`
          FROM (SELECT po.`id`, po.`publish_at`
                  FROM `Post` po INNER JOIN `Channel` ch ON po.`channel_id` = ch.`id`
                                 INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                 WHERE si.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and po.`is_deleted` = 'N'
                   and Now() BETWEEN po.`publish_at` AND IFNULL(po.`expires_at`, DATE_ADD(Now(), INTERVAL 1 MINUTE))
                   and si.`guid` = `in_site_guid` and po.`canonical_url` = `in_canon_url`
                 UNION ALL
                 SELECT -1 as `id`, FROM_UNIXTIME(0) as `publish_at`
                 ORDER BY `publish_at` DESC
                 LIMIT 1) tmp;
    END IF;

    /* Separate and Validate the Post Type Filters */
    DROP TEMPORARY TABLE IF EXISTS tmpTypes;
    CREATE TEMPORARY TABLE tmpTypes AS
    SELECT DISTINCT t.`code`
      FROM `Type` t INNER JOIN (SELECT DISTINCT TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(`post_types`, ',', num.`id`), ',', -1)) as `type_code`
                                  FROM (SELECT (v+1) as `id`
                                          FROM (SELECT 0 v UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) v) num) tmp ON t.`code` = tmp.`type_code`
     WHERE t.`is_deleted` = 'N' and t.`code` LIKE 'post.%'
     ORDER BY t.`code`;

    /* Collect the Timeline Details into a Temporary Table */
    DROP TEMPORARY TABLE IF EXISTS tmpPosts;
    IF IFNULL(`post_id`, 0) = 0 AND IFNULL(`in_tag`, '') = '' THEN
        SELECT MAX(`id`) - 5000 INTO `min_id` FROM `Post` as `start`;

        CREATE TEMPORARY TABLE tmpPosts AS
        SELECT CASE WHEN IFNULL(po.`expires_at`, DATE_ADD(Now(), INTERVAL 1 MINUTE)) < Now() THEN 'N'
                    WHEN ch.`privacy_type` <> 'visibility.public' AND si.`account_id` = `in_account_id` THEN 'Y'
                    WHEN ch.`privacy_type` <> 'visibility.public' THEN 'N'
                    WHEN po.`privacy_type` = 'visibility.none' AND pa.`account_id` <> `in_account_id` THEN 'N'
                    WHEN po.`privacy_type` <> 'visibility.public' THEN IFNULL(tmp.`can_read`, 'N')
                    WHEN po.`publish_at` > Now() AND pa.`account_id` <> `in_account_id` THEN 'N'
                    ELSE 'Y' END as `is_visible`,
               po.`id` as `post_id`, po.`publish_at`
          FROM `Post` po INNER JOIN `Channel` ch ON po.`channel_id` = ch.`id`
                         INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                         INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
                         INNER JOIN `tmpTypes` tmp ON po.`type` = tmp.`code`
                    LEFT OUTER JOIN (SELECT ca.`persona_id`, ca.`channel_id`, ca.`can_read`, ca.`can_write`
                                       FROM `Account` a INNER JOIN `Persona` pa ON a.`id` = pa.`account_id`
                                                        INNER JOIN `ChannelAuthor` ca ON pa.`id` = ca.`persona_id`
                                      WHERE ca.`is_deleted` = 'N' and pa.`is_deleted` = 'N'
                                        and a.`id` = `in_account_id`) tmp ON po.`persona_id` = tmp.`persona_id` AND ch.`id` = tmp.`channel_id`
        WHERE po.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and si.`is_deleted` = 'N' and si.`guid` = `in_site_guid`
          and po.`id` >= `min_id`;

        /* Ensure there are enough posts to meet the criteria, or go back for more */
        IF (SELECT COUNT(`post_id`) FROM `tmpPosts` WHERE `is_visible` = 'Y') < `in_count` THEN
            INSERT INTO `tmpPosts` (`is_visible`, `post_id`, `publish_at`)
            SELECT CASE WHEN IFNULL(po.`expires_at`, DATE_ADD(Now(), INTERVAL 1 MINUTE)) < Now() THEN 'N'
                        WHEN ch.`privacy_type` <> 'visibility.public' AND si.`account_id` = `in_account_id` THEN 'Y'
                        WHEN ch.`privacy_type` <> 'visibility.public' THEN 'N'
                        WHEN po.`privacy_type` = 'visibility.none' AND pa.`account_id` <> `in_account_id` THEN 'N'
                        WHEN po.`privacy_type` <> 'visibility.public' THEN IFNULL(tmp.`can_read`, 'N')
                        WHEN po.`publish_at` > Now() AND pa.`account_id` <> `in_account_id` THEN 'N'
                        ELSE 'Y' END as `is_visible`,
                   po.`id` as `post_id`, po.`publish_at`
              FROM `Post` po INNER JOIN `Channel` ch ON po.`channel_id` = ch.`id`
                             INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                             INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
                             INNER JOIN `tmpTypes` tmp ON po.`type` = tmp.`code`
                        LEFT OUTER JOIN (SELECT ca.`persona_id`, ca.`channel_id`, ca.`can_read`, ca.`can_write`
                                           FROM `Account` a INNER JOIN `Persona` pa ON a.`id` = pa.`account_id`
                                                            INNER JOIN `ChannelAuthor` ca ON pa.`id` = ca.`persona_id`
                                          WHERE ca.`is_deleted` = 'N' and pa.`is_deleted` = 'N'
                                            and a.`id` = `in_account_id`) tmp ON po.`persona_id` = tmp.`persona_id` AND ch.`id` = tmp.`channel_id`
            WHERE po.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and si.`is_deleted` = 'N' and si.`guid` = `in_site_guid`
              and po.`id` < `min_id`;
        END IF;
    END IF;

    /* Are We Doing a Tag Lookup? */
    IF IFNULL(`post_id`, 0) = 0 AND IFNULL(`in_tag`, '') <> '' THEN
        CREATE TEMPORARY TABLE tmpPosts AS
        SELECT CASE WHEN IFNULL(po.`expires_at`, DATE_ADD(Now(), INTERVAL 1 MINUTE)) < Now() THEN 'N'
                    WHEN ch.`privacy_type` <> 'visibility.public' AND si.`account_id` = `in_account_id` THEN 'Y'
                    WHEN ch.`privacy_type` <> 'visibility.public' THEN 'N'
                    WHEN po.`privacy_type` = 'visibility.none' AND pa.`account_id` <> `in_account_id` THEN 'N'
                    WHEN po.`privacy_type` <> 'visibility.public' THEN IFNULL(tmp.`can_read`, 'N')
                    WHEN po.`publish_at` > Now() AND pa.`account_id` <> `in_account_id` THEN 'N'
                    ELSE 'Y' END as `is_visible`,
               po.`id` as `post_id`, po.`publish_at`
          FROM `PostTags` pt INNER JOIN `Post` po ON pt.`post_id` = po.`id`
                             INNER JOIN `Channel` ch ON po.`channel_id` = ch.`id`
                             INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                             INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
                             INNER JOIN `tmpTypes` tmp ON po.`type` = tmp.`code`
                        LEFT OUTER JOIN (SELECT ca.`persona_id`, ca.`channel_id`, ca.`can_read`, ca.`can_write`
                                            FROM `Account` a INNER JOIN `Persona` pa ON a.`id` = pa.`account_id`
                                                        INNER JOIN `ChannelAuthor` ca ON pa.`id` = ca.`persona_id`
                                      WHERE ca.`is_deleted` = 'N' and pa.`is_deleted` = 'N'
                                        and a.`id` = `in_account_id`) tmp ON po.`persona_id` = tmp.`persona_id` AND ch.`id` = tmp.`channel_id`
        WHERE po.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and si.`is_deleted` = 'N' and si.`guid` = `in_site_guid`
          and pt.`key` = IFNULL(`in_tag`, '');
    END IF;

    IF IFNULL(`post_id`, 0) <> 0 THEN
        CREATE TEMPORARY TABLE tmpPosts AS
        SELECT CASE WHEN IFNULL(po.`expires_at`, DATE_ADD(Now(), INTERVAL 1 MINUTE)) < Now() THEN 'N'
                    WHEN ch.`privacy_type` <> 'visibility.public' AND si.`account_id` = `in_account_id` THEN 'Y'
                    WHEN ch.`privacy_type` <> 'visibility.public' THEN 'N'
                    WHEN po.`privacy_type` = 'visibility.none' AND pa.`account_id` <> `in_account_id` THEN 'N'
                    WHEN po.`privacy_type` <> 'visibility.public' THEN IFNULL(tmp.`can_read`, 'N')
                    WHEN po.`publish_at` > Now() AND pa.`account_id` <> `in_account_id` THEN 'N'
                    ELSE 'Y' END as `is_visible`,
               CASE WHEN po.`canonical_url` = `in_canon_url` THEN 'Y'
                    ELSE 'N' END as `aux_visible`,
               po.`id` as `post_id`, po.`publish_at`
          FROM `Post` po INNER JOIN `Channel` ch ON po.`channel_id` = ch.`id`
                         INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                         INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
                    LEFT OUTER JOIN (SELECT ca.`persona_id`, ca.`channel_id`, ca.`can_read`, ca.`can_write`
                                       FROM `Account` a INNER JOIN `Persona` pa ON a.`id` = pa.`account_id`
                                                        INNER JOIN `ChannelAuthor` ca ON pa.`id` = ca.`persona_id`
                                      WHERE ca.`is_deleted` = 'N' and pa.`is_deleted` = 'N'
                                        and a.`id` = `in_account_id`) tmp ON po.`persona_id` = tmp.`persona_id` AND ch.`id` = tmp.`channel_id`
        WHERE po.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and si.`is_deleted` = 'N'
          and po.`publish_at` <= Now() and po.`id` = `post_id`;
    END IF;

    /* Output the Completed Post Objects */
    SELECT po.`id` as `post_id`, po.`parent_id`, po.`guid` as `post_guid`, po.`type` as `post_type`, po.`privacy_type`,
           (SELECT z.`guid` FROM `Post` z WHERE z.`is_deleted` = 'N' and z.`id` = IFNULL(po.`thread_id`, po.`id`)) as `thread_guid`,
           (SELECT COUNT(z.`id`) + 1  FROM `Post` z WHERE z.`is_deleted` = 'N' and z.`thread_id` = IFNULL(po.`thread_id`, po.`id`)) as `thread_posts`,
           po.`persona_id`, pa.`name` as `persona_name`, pa.`last_name`, pa.`first_name`, pa.`display_name`, pa.`avatar_img`, pa.`email`,
           pa.`guid` as `persona_guid`, pa.`is_active` as `persona_active`, pa.`created_at` as `persona_created_at`, pa.`updated_at` as `persona_updated_at`,
           po.`title`, po.`value`,
           (SELECT CASE WHEN COUNT(z.`key`) > 0 THEN 'Y' ELSE 'N' END FROM `PostMeta` z WHERE z.`is_deleted` = 'N' and z.`post_id` = po.`id` LIMIT 1) as `has_meta`,
           (SELECT GROUP_CONCAT(z.`value`) as `value` FROM `PostTags` z WHERE z.`is_deleted` = 'N' and z.`post_id` = po.`id`) as `post_tags`,
           (SELECT CASE WHEN COUNT(z.`persona_id`) > 0 THEN 'Y' ELSE 'N' END FROM `PostMention` z WHERE z.`is_deleted` = 'N' and z.`post_id` = po.`id`) as `has_mentions`,

           IFNULL((SELECT pp.`pin_type` FROM `PostAction` pp INNER JOIN `Persona` pz ON pp.`persona_id` = pz.`id`
                    WHERE pp.`is_deleted` = 'N' and pz.`is_deleted` = 'N' and pp.`post_id` = po.`id` and pz.`account_id` = `in_account_id`), 'pin.none') as `pin_type`,
           IFNULL((SELECT pp.`is_starred` FROM `PostAction` pp INNER JOIN `Persona` pz ON pp.`persona_id` = pz.`id`
                    WHERE pp.`is_deleted` = 'N' and pz.`is_deleted` = 'N' and pp.`post_id` = po.`id` and pz.`account_id` = `in_account_id`), 'N') as `is_starred`,
           IFNULL((SELECT pp.`is_muted` FROM `PostAction` pp INNER JOIN `Persona` pz ON pp.`persona_id` = pz.`id`
                    WHERE pp.`is_deleted` = 'N' and pz.`is_deleted` = 'N' and pp.`post_id` = po.`id` and pz.`account_id` = `in_account_id`), 'N') as `is_muted`,
           IFNULL((SELECT pp.`points` FROM `PostAction` pp INNER JOIN `Persona` pz ON pp.`persona_id` = pz.`id`
                    WHERE pp.`is_deleted` = 'N' and pz.`is_deleted` = 'N' and pp.`post_id` = po.`id` and pz.`account_id` = `in_account_id`), 0) as `points`,

           po.`canonical_url`, po.`slug`, po.`reply_to`,
           po.`channel_id`, ch.`name` as `channel_name`, ch.`type` as `channel_type`, ch.`privacy_type` as `channel_privacy_type`, ch.`guid` as `channel_guid`,
           ch.`created_at` as `channel_created_at`, ch.`updated_at` as `channel_updated_at`,
           ch.`site_id`, (SELECT z.`url` FROM `SiteUrl` z WHERE z.`is_deleted` = 'N' and z.`is_active` = 'Y' and z.`site_id` = ch.`site_id` ORDER BY z.`id` DESC LIMIT 1) as `site_url`, si.`https`,
           si.`name` as `site_name`, si.`description` as `site_description`, si.`keywords` as `site_keywords`, si.`theme` as `site_theme`,
           si.`guid` as `site_guid`, si.`created_at` as `site_created_at`, si.`updated_at` as `site_updated_at`,
           po.`client_id`, cl.`name` as `client_name`, cl.`logo_img` as `client_logo_img`, cl.`guid` as `client_guid`,
           po.`publish_at`, po.`expires_at`,
           po.`created_at`, po.`created_by`, po.`updated_at`
      FROM `Site` si INNER JOIN `Channel` ch ON si.`id` = ch.`site_id`
                     INNER JOIN `Post` po ON ch.`id` = po.`channel_id`
                     INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
                     INNER JOIN `Client` cl ON po.`client_id` = cl.`id`
                     INNER JOIN (SELECT z.`post_id` FROM `tmpPosts` z WHERE z.`is_visible` = 'Y'
                                  ORDER BY z.`publish_at` DESC LIMIT `in_page`, `in_count`) tmp ON po.`id` = tmp.`post_id`
     WHERE ch.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and po.`is_deleted` = 'N' and si.`guid` = `in_site_guid`
       and 'Y' = CASE WHEN po.`privacy_type` = 'visibility.public' THEN 'Y'
                      WHEN pa.`account_id` = `in_account_id` THEN 'Y'
                      WHEN ch.`privacy_type` = 'visibility.password' AND IFNULL(`pass_valid`, 'N') = 'Y' THEN 'Y'
                      ELSE 'N' END
     ORDER BY po.`publish_at` DESC, po.`id` DESC
     LIMIT `in_count`;

END ;;
DELIMITER ;