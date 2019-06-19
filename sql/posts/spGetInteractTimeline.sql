DELIMITER ;;
DROP PROCEDURE IF EXISTS GetInteractTimeline;;
CREATE PROCEDURE GetInteractTimeline( IN `in_account_id` int(11), IN `in_persona_guid` varchar(36), IN `in_type_list` varchar(1024), IN `in_since_unix` int(11), IN `in_until_unix` int(11), IN `in_count` int(11) )
BEGIN
    DECLARE `min_id` int(11);

    /** ********************************************************************** **
     *  Function returns the Visible Posts for the Interaction Timeline
     *
     *  Usage: CALL GetInteractTimeline(1, '', 'post.article, post.bookmark, post.note, post.quotation', 0, 0, 75);
     ** ********************************************************************** **/

    /* If the Type filter is Empty, Add Social Posts (post.note) */
    IF LENGTH(IFNULL(`in_type_list`, '')) < 9 THEN
        SET `in_type_list` = 'post.note';
    END IF;

    /* If the Count Only value is not cromulent, make it so */
    IF IFNULL(`in_count`, 0) NOT BETWEEN 1 AND 250 THEN
        SET `in_count` = 75;
    END IF;

    /* Get the Initial Post.id Minimum */
    SELECT MAX(`id`) - 5000 INTO `min_id` FROM `Post` as `start`;

    /* Separate and Validate the Post Type Filters */
    DROP TEMPORARY TABLE IF EXISTS tmpTypes;
    CREATE TEMPORARY TABLE tmpTypes AS
    SELECT DISTINCT t.`code`
      FROM `Type` t INNER JOIN (SELECT DISTINCT TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(txt.`types`, ',', num.`id`), ',', -1)) as `type_code`
                                  FROM (SELECT (v+1) as `id`
                                          FROM (SELECT 0 v UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) v) num
                                         CROSS JOIN (SELECT `in_type_list` as `types`) AS txt) tmp ON t.`code` = tmp.`type_code`
     WHERE t.`is_deleted` = 'N' and t.`code` LIKE 'post.%'
     ORDER BY t.`code`;

    /* Collect the Timeline Details into a Temporary Table */
    DROP TEMPORARY TABLE IF EXISTS tmpPosts;
    CREATE TEMPORARY TABLE tmpPosts AS
    SELECT DISTINCT po.`id` as `post_id`, GREATEST(po.`publish_at`, po.`updated_at`) as `posted_at`,
           LEAST(CASE WHEN ch.`privacy_type` = 'visibility.public' THEN 'Y'
                      WHEN pa.`account_id` = `in_account_id` THEN 'Y'
                      ELSE 'N' END,
                CASE WHEN po.`expires_at` IS NULL THEN 'Y'
                     WHEN po.`expires_at` IS NOT NULL AND po.`expires_at` < Now() THEN 'N'
                     WHEN pa.`account_id` = `in_account_id` THEN 'Y'
                     ELSE 'Y' END) as `is_visible`
      FROM `SiteUrl` su INNER JOIN `Site` si ON su.`site_id` = si.`id`
                        INNER JOIN `Channel` ch ON ch.`site_id` = si.`id`
                        INNER JOIN `Post` po ON ch.`id` = po.`channel_id`
                        INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
                        INNER JOIN `PostAction` act ON po.`id` = act.`post_id`
                        INNER JOIN `Persona` pap ON act.`persona_id` = pap.`id`
                        INNER JOIN `tmpTypes` tmp ON po.`type` = tmp.`code`
     WHERE po.`is_deleted` = 'N' and si.`is_deleted` = 'N' and su.`is_deleted` = 'N' and su.`is_active` = 'Y'
       and ch.`is_deleted` = 'N' and ch.`type` = 'channel.site'
       and act.`is_deleted` = 'N' and pap.`is_deleted` = 'N' and pap.`account_id` = `in_account_id`
       and pa.`is_deleted` = 'N' and po.`publish_at` <= Now() and po.`id` >= IFNULL(`min_id`, 0);

    /* If there aren't enough posts, reach back farther to look for some */
    IF (SELECT COUNT(`post_id`) FROM tmpPosts WHERE `is_visible` = 'Y') < `in_count` THEN
        INSERT INTO tmpPosts (`post_id`, `posted_at`, `is_visible`)
        SELECT DISTINCT po.`id` as `post_id`, GREATEST(po.`publish_at`, po.`updated_at`) as `posted_at`,
               LEAST(CASE WHEN ch.`privacy_type` = 'visibility.public' THEN 'Y'
                          WHEN pa.`account_id` = `in_account_id` THEN 'Y'
                          ELSE 'N' END,
                    CASE WHEN po.`expires_at` IS NULL THEN 'Y'
                         WHEN po.`expires_at` IS NOT NULL AND po.`expires_at` < Now() THEN 'N'
                         WHEN pa.`account_id` = `in_account_id` THEN 'Y'
                         ELSE 'Y' END,
                    CASE WHEN act.`pin_type` <> 'pin.none' THEN 'Y'
                         WHEN act.`is_starred` <> 'N' THEN 'Y'
                         WHEN act.`points` > 0 THEN 'Y'
                         ELSE 'N' END) as `is_visible`
          FROM `SiteUrl` su INNER JOIN `Site` si ON su.`site_id` = si.`id`
                            INNER JOIN `Channel` ch ON ch.`site_id` = si.`id`
                            INNER JOIN `Post` po ON ch.`id` = po.`channel_id`
                            INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
                            INNER JOIN `PostAction` act ON po.`id` = act.`post_id`
                            INNER JOIN `Persona` pap ON act.`persona_id` = pap.`id`
                            INNER JOIN `tmpTypes` tmp ON po.`type` = tmp.`code`
         WHERE po.`is_deleted` = 'N' and si.`is_deleted` = 'N' and su.`is_deleted` = 'N' and su.`is_active` = 'Y'
           and ch.`is_deleted` = 'N' and ch.`type` = 'channel.site'
           and act.`is_deleted` = 'N' and pap.`is_deleted` = 'N' and pap.`account_id` = `in_account_id`
           and pa.`is_deleted` = 'N' and po.`publish_at` <= Now() and po.`id` < IFNULL(`min_id`, 4294967295);
    END IF;

    /* Build the Completed Timeline */
    SELECT pa.`name` as `persona_name`, pa.`display_name`, pa.`guid` as `persona_guid`,
           CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`, '/avatars/', pa.`avatar_img`) as `avatar_url`,
           CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`, '/', pa.`guid`, '/profile') as `profile_url`,
           po.`id` as `post_id`, po.`thread_id`, po.`parent_id`, po.`title`, po.`value`,
           (SELECT CASE WHEN COUNT(z.`key`) > 0 THEN 'Y' ELSE 'N' END FROM `PostMeta` z WHERE z.`is_deleted` = 'N' and z.`post_id` = po.`id` LIMIT 1) as `has_meta`,
           (SELECT GROUP_CONCAT('{"guid": "', zpa.`guid`, '", "as": "@', zpa.`name`, '", "is_you": "', CASE WHEN zpa.`account_id` = `in_account_id` THEN 'Y' ELSE 'N' END, '"}') as `mentions`
              FROM `Persona` zpa INNER JOIN `PostMention` zpm ON zpa.`id` = zpm.`persona_id`
             WHERE zpa.`is_deleted` = 'N' and zpm.`is_deleted` = 'N' and zpm.`post_id` = po.`id`) as `mentions`,
           (SELECT GROUP_CONCAT(z.`value`) as `value` FROM `PostTags` z WHERE z.`is_deleted` = 'N' and z.`post_id` = po.`id`) as `post_tags`,
           CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`, po.`canonical_url`) as `canonical_url`,
           CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`) as `site_url`,

           IFNULL((SELECT pp.`pin_type` FROM `PostAction` pp INNER JOIN `Persona` pz ON pp.`persona_id` = pz.`id`
                    WHERE pp.`is_deleted` = 'N' and pz.`is_deleted` = 'N' and pp.`post_id` = po.`id` and pz.`account_id` = `in_account_id`
                    ORDER BY pp.`updated_at` DESC LIMIT 1), 'pin.none') as `pin_type`,
           IFNULL((SELECT pp.`is_starred` FROM `PostAction` pp INNER JOIN `Persona` pz ON pp.`persona_id` = pz.`id`
                    WHERE pp.`is_deleted` = 'N' and pz.`is_deleted` = 'N' and pp.`post_id` = po.`id` and pz.`account_id` = `in_account_id`
                    ORDER BY pp.`updated_at` DESC LIMIT 1), 'N') as `is_starred`,
           IFNULL((SELECT pp.`is_muted` FROM `PostAction` pp INNER JOIN `Persona` pz ON pp.`persona_id` = pz.`id`
                    WHERE pp.`is_deleted` = 'N' and pz.`is_deleted` = 'N' and pp.`post_id` = po.`id` and pz.`account_id` = `in_account_id`
                    ORDER BY pp.`updated_at` DESC LIMIT 1), 'N') as `is_muted`,
           IFNULL((SELECT pp.`points` FROM `PostAction` pp INNER JOIN `Persona` pz ON pp.`persona_id` = pz.`id`
                    WHERE pp.`is_deleted` = 'N' and pz.`is_deleted` = 'N' and pp.`post_id` = po.`id` and pz.`account_id` = `in_account_id`
                    ORDER BY pp.`updated_at` DESC LIMIT 1), 0) as `points`,

           po.`reply_to`, po.`type`,
           po.`guid` as `post_guid`, po.`privacy_type`,
           po.`publish_at`, po.`expires_at`, po.`updated_at`,
           CASE WHEN pa.`account_id` = `in_account_id` THEN 'Y' ELSE 'N' END as `is_you`,
           CASE WHEN po.`expires_at` IS NULL THEN 'Y'
                WHEN po.`expires_at` < Now() THEN 'N'
                ELSE 'Y' END as `is_visible`
      FROM `SiteUrl` su INNER JOIN `Site` si ON su.`site_id` = si.`id`
                        INNER JOIN `Channel` ch ON si.`id` = ch.`site_id`
                        INNER JOIN `Post` po ON ch.`id` = po.`channel_id`
                        INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
                        INNER JOIN (SELECT `post_id`, `posted_at`, `is_visible` FROM tmpPosts
                                     WHERE `is_visible` = 'Y'
                                       and `posted_at` BETWEEN CASE WHEN `in_since_unix` = 0 THEN DATE_SUB(Now(), INTERVAL 6 MONTH) ELSE FROM_UNIXTIME(`in_since_unix`) END AND
                                                               CASE WHEN `in_until_unix` = 0 THEN Now() ELSE FROM_UNIXTIME(`in_until_unix`) END
                                     ORDER BY CASE WHEN `in_since_unix` = 0 THEN 1 ELSE tmpPosts.`posted_at` END, tmpPosts.`posted_at` DESC
                                     LIMIT `in_count`) tmp ON po.`id` = tmp.`post_id`
     WHERE su.`is_deleted` = 'N' and si.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and po.`is_deleted` = 'N' and pa.`is_deleted` = 'N'
       and ch.`type` = 'channel.site' and ch.`privacy_type` = 'visibility.public' and su.`is_active` = 'Y'
       and po.`privacy_type` IN ('visibility.public', 'visibility.private', 'visibility.none')
       and 'Y' = CASE WHEN po.`privacy_type` = 'visibility.public' THEN 'Y'
                      WHEN pa.`account_id` = `in_account_id` THEN 'Y'
                      ELSE 'N' END
     ORDER BY CASE WHEN `in_since_unix` = 0 THEN 0 ELSE 1 END, tmp.`posted_at` DESC;

END ;;
DELIMITER ;