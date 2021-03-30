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
    IF IFNULL(`min_id`, 0) <= 0 THEN
        SET `min_id` = (SELECT MIN(po.`id`) FROM `Post` po
                         WHERE po.`is_deleted` = 'N' and po.`publish_at` >= DATE_SUB(Now(), INTERVAL 14 DAY)
                         LIMIT 1);
    END IF;
    IF IFNULL(`min_id`, 0) <= 0 THEN
        SET `min_id` = (SELECT po.`id` - 5000 FROM `Post` po
                         ORDER BY po.`id` DESC LIMIT 1);
    END IF;
    IF IFNULL(`min_id`, 0) <= 0 THEN
        SET `min_id` = 1;
    END IF;

    /* Separate and Validate the Post Type Filters */
    DROP TEMPORARY TABLE IF EXISTS tmpTypes;
    CREATE TEMPORARY TABLE tmpTypes AS
    SELECT DISTINCT t.`code`
      FROM `Type` t INNER JOIN (SELECT DISTINCT TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(`in_type_list`, ',', num.`id`), ',', -1)) as `type_code`
                                  FROM (SELECT (v+1) as `id`
                                          FROM (SELECT 0 v UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) v) num) tmp ON t.`code` = tmp.`type_code`
     WHERE t.`is_deleted` = 'N' and t.`code` LIKE 'post.%'
     ORDER BY t.`code`;

    /* Collect the Persona Relationships */
    DROP TEMPORARY TABLE IF EXISTS tmpRelations;
    CREATE TEMPORARY TABLE tmpRelations AS
    SELECT zz.`persona_id`, MAX(zz.`follows`) as `follows`, MAX(zz.`is_muted`) as `is_muted`, MIN(zz.`is_blocked`) as `is_blocked`, MAX(zz.`is_starred`) as `is_starred`, MAX(zz.`pin_type`) as `pin_type`
      FROM (SELECT pr.`related_id` as `persona_id`, pr.`follows`, pr.`is_muted`, pr.`is_blocked`, pr.`is_starred`, pr.`pin_type`
              FROM `PersonaRelation` pr INNER JOIN `Persona` pa ON pr.`persona_id` = pa.`id`
             WHERE pr.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and pa.`account_id` = `in_account_id`
             UNION ALL
            SELECT pa.`id` as `persona_id`, 'Y' as `follows`, 'N' as `is_muted`, 'N' as `is_blocked`, 'N' as `is_starred`, '' as `pin_type`
              FROM `Persona` pa
             WHERE pa.`is_deleted` = 'N' and pa.`account_id` = `in_account_id`) zz
     GROUP BY zz.`persona_id`;

    /* Collect the Timeline Details into a Temporary Table */
    DROP TEMPORARY TABLE IF EXISTS tmpPosts;
    CREATE TEMPORARY TABLE tmpPosts AS
    SELECT DISTINCT po.`id` as `post_id`, act.`updated_at` as `posted_at`,
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
       and pa.`is_deleted` = 'N' and po.`publish_at` <= Now()
       and 'Y' = CASE WHEN act.`pin_type` NOT IN ('pin.none') THEN 'Y'
                      ELSE act.`is_starred` END;

    /* If there aren't enough posts, reach back farther to look for some */
    IF (SELECT COUNT(`post_id`) FROM tmpPosts WHERE `is_visible` = 'Y') < `in_count` THEN
        INSERT INTO tmpPosts (`post_id`, `posted_at`, `is_visible`)
        SELECT DISTINCT po.`id` as `post_id`, act.`updated_at` as `posted_at`,
               LEAST(CASE WHEN ch.`privacy_type` = 'visibility.public' THEN 'Y'
                          WHEN pa.`account_id` = `in_account_id` THEN 'Y'
                          ELSE 'N' END,
                     CASE WHEN po.`expires_at` IS NULL THEN 'Y'
                          WHEN po.`expires_at` IS NOT NULL AND po.`expires_at` < Now() THEN 'N'
                          WHEN pa.`account_id` = `in_account_id` THEN 'Y'
                          ELSE 'Y' END,
                     CASE WHEN act.`pin_type` <> 'pin.none' THEN 'Y'
                          WHEN act.`is_starred` <> 'N' THEN 'Y'
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
    SELECT DISTINCT pa.`name` as `persona_name`, pa.`display_name`, pa.`guid` as `persona_guid`,
           (SELECT CASE WHEN IFNULL(zpm.`value`, 'N') = 'Y'
                        THEN CONCAT('https://www.gravatar.com/avatar/', MD5(LOWER(CASE WHEN zpa.`email` <> '' THEN zpa.`email` ELSE zacct.`email` END)), '?s=250&r=pg')
                        ELSE (SELECT CONCAT(CASE WHEN zsi.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', zsu.`url`, '/avatars/', zpa.`avatar_img`) as `avatar_url`
                                FROM `Site` zsi INNER JOIN `SiteUrl` zsu ON zsi.`id` = zsu.`site_id`
                               WHERE zsi.`is_deleted` = 'N' and zsi.`is_default` = 'Y' and zsu.`is_active` = 'Y'
                               LIMIT 1) END as `avatar_url`
              FROM `Account` zacct INNER JOIN `Persona` zpa ON zacct.`id` = zpa.`account_id`
                             LEFT OUTER JOIN `PersonaMeta` zpm ON zpa.`id` = zpm.`persona_id` AND zpm.`is_deleted` = 'N' and zpm.`key` = 'avatar.gravatar'
             WHERE zacct.`is_deleted` = 'N' and zpa.`is_deleted` = 'N' and zpa.`id` = pa.`id`) as `avatar_url`,
           CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`, '/', pa.`guid`, '/profile') as `profile_url`,
           po.`id` as `post_id`, po.`parent_id`, po.`thread_id`,
           (SELECT COUNT(z.`id`) FROM `Post` z
             WHERE z.`is_deleted` = 'N' and z.`thread_id` = IFNULL(po.`thread_id`, po.`id`) and z.`id` >= IFNULL(po.`thread_id`, po.`id`)) as `thread_length`,
           po.`title`, po.`value`,
           (SELECT CASE WHEN COUNT(z.`key`) > 0 THEN 'Y' ELSE 'N' END FROM `PostMeta` z WHERE z.`is_deleted` = 'N' and z.`post_id` = po.`id` LIMIT 1) as `has_meta`,
           CASE WHEN po.`type` IN ('post.location')
                THEN (SELECT CASE WHEN COUNT(DISTINCT z.`seq_id`) > 0 THEN 'Y' ELSE 'N' END FROM `PostMarker` z WHERE z.`is_deleted` = 'N' and z.`post_id` = po.`id` LIMIT 1)
                ELSE 'N' END as `has_markers`,
           (SELECT GROUP_CONCAT('{"guid": "', zpa.`guid`, '", "as": "@', zpa.`name`, '", "is_you": "', CASE WHEN zpa.`account_id` = `in_account_id` THEN 'Y' ELSE 'N' END, '"}') as `mentions`
              FROM `Persona` zpa INNER JOIN `PostMention` zpm ON zpa.`id` = zpm.`persona_id`
             WHERE zpa.`is_deleted` = 'N' and zpm.`is_deleted` = 'N' and zpm.`post_id` = po.`id`) as `mentions`,
           (SELECT GROUP_CONCAT(z.`value`) as `value` FROM `PostTags` z WHERE z.`is_deleted` = 'N' and z.`post_id` = po.`id`) as `post_tags`,
           CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`, po.`canonical_url`) as `canonical_url`,
           CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`) as `site_url`,

           po.`reply_to`, po.`type`,
           po.`guid` as `post_guid`, po.`privacy_type`,
           po.`publish_at`, po.`expires_at`, po.`updated_at`,
           tmp.`posted_at`,

           IFNULL(pp.`pin_type`, 'pin.none') as `pin_type`,
           IFNULL(pp.`is_starred`, 'N') as `is_starred`,
           IFNULL(pp.`is_muted`, 'N') as `is_muted`,
           IFNULL(pp.`points`, 0) as `points`,
           (SELECT SUM(pts.`points`) as `total` FROM `PostAction` pts
             WHERE pts.`is_deleted` = 'N' and pts.`points` <> 0 and pts.`post_id` = po.`id`) as `total_points`,

           IFNULL(pr.`follows`, 'N') as `persona_follow`,
           IFNULL(pr.`is_muted`, 'N') as `persona_muted`,
           IFNULL(pr.`is_blocked`, 'N') as `persona_blocked`,
           IFNULL(pr.`is_starred`, 'N') as `persona_starred`,
           CASE WHEN IFNULL(pr.`pin_type`, '') = '' THEN 'pin.none' ELSE pr.`pin_type` END as `persona_pin`,

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
                                     ORDER BY CASE WHEN `in_since_unix` = 0 THEN 1 ELSE tmpPosts.`posted_at` END, tmpPosts.`posted_at` DESC
                                     LIMIT `in_count`) tmp ON po.`id` = tmp.`post_id`
                   LEFT OUTER JOIN (SELECT pp.`post_id`, pp.`pin_type`, pp.`is_starred`, pp.`is_muted`, pp.`points`
                                      FROM `PostAction` pp INNER JOIN `Persona` pz ON pp.`persona_id` = pz.`id`
                                     WHERE pp.`is_deleted` = 'N' and pz.`is_deleted` = 'N' and pz.`account_id` = `in_account_id`) pp ON po.`id` = pp.`post_id`
                   LEFT OUTER JOIN `tmpRelations` pr ON pa.`id` = pr.`persona_id`
     WHERE su.`is_deleted` = 'N' and si.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and po.`is_deleted` = 'N' and pa.`is_deleted` = 'N'
       and ch.`type` = 'channel.site' and su.`is_active` = 'Y'
       and po.`privacy_type` IN ('visibility.public', 'visibility.private', 'visibility.none')
       and 'Y' = CASE WHEN ch.`privacy_type` = 'visibility.public' AND po.`privacy_type` = 'visibility.public' THEN 'Y'
                      WHEN pa.`account_id` = `in_account_id` THEN 'Y'
                      WHEN po.`privacy_type` = 'visibility.public' THEN 'Y'
                      WHEN po.`privacy_type` = 'visibility.private' THEN IFNULL(pr.`follows`, 'N')
                      ELSE 'N' END
     ORDER BY CASE WHEN `in_since_unix` = 0 THEN 0 ELSE 1 END, tmp.`posted_at` DESC;

END ;;
DELIMITER ;