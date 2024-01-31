DELIMITER ;;
DROP PROCEDURE IF EXISTS GetPersonaTimeline;;
CREATE PROCEDURE GetPersonaTimeline( IN `in_account_id` int(11), IN `in_persona_guid` varchar(36), IN `in_type_list` varchar(1024), IN `in_since_unix` int(11), IN `in_until_unix` int(11), IN `in_count` int(11) )
BEGIN
    DECLARE `x_start_at` datetime;
    DECLARE `x_until_at` datetime;

    /** ********************************************************************** **
     *  Function returns the Visible Timeline for a Given Persona GUID
     *
     *  Usage: CALL GetPersonaTimeline(1, '07d2f4ec-545f-11e8-99a0-54ee758049c3', 'post.article, post.bookmark, post.note, post.quotation', 0, 0, 75);
     ** ********************************************************************** **/

    /* If the Type filter is Empty, Add Social Posts (post.note) */
    IF LENGTH(IFNULL(`in_type_list`, '')) < 9 THEN
        SET `in_type_list` = 'post.note';
    END IF;

    /* If the Count Only value is not cromulent, make it so */
    IF IFNULL(`in_count`, 0) NOT BETWEEN 1 AND 250 THEN
        SET `in_count` = 75;
    END IF;

    /* Set the Date Limits */
    SET `x_start_at` = DATE_FORMAT(DATE_SUB(Now(), INTERVAL 14 DAY), '%Y-%m-%d 00:00:00');
    IF IFNULL(`in_since_unix`, 0) > 1000 THEN
        SET `x_start_at` = FROM_UNIXTIME(`in_since_unix`);
    END IF;

    SET `x_until_at` = Now();
    IF IFNULL(`in_until_unix`, 0) > 1000 THEN
        SET `x_until_at` = FROM_UNIXTIME(`in_until_unix`);
    END IF;
    IF `x_until_at` > Now() THEN
        SET `x_until_at` = Now();
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

    /* Collect the Timeline Details into a Temporary Table */
    DROP TEMPORARY TABLE IF EXISTS tmpPosts;
    CREATE TEMPORARY TABLE tmpPosts AS
    SELECT DISTINCT po.`id` as `post_id`, po.`publish_at` as `posted_at`,
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
                        INNER JOIN `tmpTypes` tmp ON po.`type` = tmp.`code`
     WHERE po.`is_deleted` = 'N' and si.`is_deleted` = 'N' and su.`is_deleted` = 'N' and su.`is_active` = 'Y'
       and ch.`is_deleted` = 'N' and ch.`type` = 'channel.site'
       and pa.`is_deleted` = 'N' and pa.`guid` = `in_persona_guid`
       and po.`publish_at` BETWEEN `x_start_at` AND `x_until_at`
       and po.`publish_at` <= Now();

    /* If there aren't enough posts, reach back farther to look for some */
    IF (SELECT COUNT(`post_id`) FROM tmpPosts WHERE `is_visible` = 'Y') < `in_count` THEN
        IF IFNULL(`in_since_unix`, 0) < 1000 THEN
            SET `x_start_at` = '1970-01-01 00:00:00';
        END IF;

        INSERT INTO tmpPosts (`post_id`, `posted_at`, `is_visible`)
        SELECT DISTINCT po.`id` as `post_id`, po.`publish_at` as `posted_at`,
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
                            INNER JOIN `tmpTypes` tmp ON po.`type` = tmp.`code`
         WHERE po.`is_deleted` = 'N' and si.`is_deleted` = 'N' and su.`is_deleted` = 'N' and su.`is_active` = 'Y'
           and ch.`is_deleted` = 'N' and ch.`type` = 'channel.site'
           and pa.`is_deleted` = 'N' and pa.`guid` = `in_persona_guid`
           and po.`publish_at` <= `x_start_at` and po.`publish_at` <= Now()
         LIMIT 5000;
    END IF;

    /* Output the Completed Timeline */
    SELECT pa.`name` as `persona_name`, pa.`display_name`, pa.`guid` as `persona_guid`,
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

           IFNULL(pp.`pin_type`, 'pin.none') as `pin_type`,
           IFNULL(pp.`is_starred`, 'N') as `is_starred`,
           IFNULL(pp.`is_muted`, 'N') as `is_muted`,
           IFNULL(pp.`points`, 0) as `points`,
           (SELECT SUM(pts.`points`) as `total` FROM `PostAction` pts
             WHERE pts.`is_deleted` = 'N' and pts.`points` <> 0 and pts.`post_id` = po.`id`) as `total_points`,

           po.`reply_to`, po.`type`,
           po.`guid` as `post_guid`, po.`privacy_type`,
           po.`publish_at`, ROUND(UNIX_TIMESTAMP(po.`publish_at`)) as `publish_unix`,
           po.`expires_at`, ROUND(UNIX_TIMESTAMP(po.`expires_at`)) as `expires_unix`,
           po.`updated_at`, ROUND(UNIX_TIMESTAMP(po.`updated_at`)) as `updated_unix`,
           (SELECT ROUND(UNIX_TIMESTAMP(GREATEST(z.`updated_at`, MAX(IFNULL(a.`updated_at`, '2000-01-01 00:00:00')), MAX(IFNULL(b.`updated_at`, '2000-01-01 00:00:00'))))) as `version`
              FROM `Post` z LEFT OUTER JOIN `PostMeta` a ON z.`id` = a.`post_id`
                            LEFT OUTER JOIN `PostAction` b ON z.`id` = b.`post_id`
             WHERE z.`is_deleted` = 'N' and z.`id` = po.`id`) as `post_version`,

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
                                       and `posted_at` BETWEEN FROM_UNIXTIME(`in_since_unix`) AND
                                                               CASE WHEN `in_until_unix` = 0 THEN Now() ELSE FROM_UNIXTIME(`in_until_unix`) END
                                     ORDER BY tmpPosts.`posted_at` DESC
                                     LIMIT `in_count`) tmp ON po.`id` = tmp.`post_id`
                   LEFT OUTER JOIN (SELECT pp.`post_id`, pp.`pin_type`, pp.`is_starred`, pp.`is_muted`, pp.`points`
                                      FROM `PostAction` pp INNER JOIN `Persona` pz ON pp.`persona_id` = pz.`id`
                                     WHERE pp.`is_deleted` = 'N' and pz.`is_deleted` = 'N' and pz.`account_id` = `in_account_id`) pp ON po.`id` = pp.`post_id`
     WHERE su.`is_deleted` = 'N' and si.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and po.`is_deleted` = 'N' and pa.`is_deleted` = 'N'
       and ch.`type` = 'channel.site' and su.`is_active` = 'Y'
       and 'Y' = CASE WHEN ch.`privacy_type` = 'visibility.public' THEN 'Y'
                      WHEN pa.`account_id` = `in_account_id` THEN 'Y'
                      ELSE 'N' END
       and 'Y' = CASE WHEN po.`privacy_type` = 'visibility.public' THEN 'Y'
                      WHEN pa.`account_id` = `in_account_id` THEN 'Y'
                      ELSE 'N' END
     ORDER BY tmp.`posted_at` DESC;

END ;;
DELIMITER ;