DELIMITER ;;
DROP PROCEDURE IF EXISTS GetGlobalTimeline;;
CREATE PROCEDURE GetGlobalTimeline( IN `in_account_id` int(11), IN `in_since_unix` int(11), IN `in_until_unix` int(11), IN `in_count` int(11) )
BEGIN
    DECLARE my_home varchar(255);

    /** ********************************************************************** **
     *  Function returns the Global Timeline for a Given Account
     *
     *  Usage: CALL GetGlobalTimeline(1, 0, 0, 75);
     ** ********************************************************************** **/

    /* If the Count Only value is not cromulent, make it so */
    IF IFNULL(`in_count`, 0) NOT BETWEEN 1 AND 250 THEN
        SET `in_count` = 75;
    END IF;

    /* Collect the Timeline Details into a Temporary Table */
    DROP TEMPORARY TABLE IF EXISTS tmp;
    CREATE TEMPORARY TABLE tmp AS
    SELECT pa.`name` as `persona_name`, pa.`display_name`, pa.`guid` as `persona_guid`,
           CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`, '/avatars/', pa.`avatar_img`) as `avatar_url`,
           CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`, '/', pa.`guid`, '/profile') as `profile_url`,
           po.`id` as `post_id`, po.`thread_id`, po.`parent_id`, po.`title`, po.`value`,
           (SELECT CASE WHEN COUNT(z.`key`) > 0 THEN 'Y' ELSE 'N' END FROM `PostMeta` z WHERE z.`is_deleted` = 'N' and z.`post_id` = po.`id` LIMIT 1) as `has_meta`,
           (SELECT GROUP_CONCAT(z.`value`) as `value` FROM `PostTags` z WHERE z.`is_deleted` = 'N' and z.`post_id` = po.`id`) as `post_tags`,
           CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`, po.`canonical_url`) as `canonical_url`,
           CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`) as `site_url`,
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
     WHERE su.`is_deleted` = 'N' and si.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and po.`is_deleted` = 'N' and pa.`is_deleted` = 'N'
       and ch.`type` = 'channel.site' and su.`is_active` = 'Y'
       and po.`type` IN ('post.article', 'post.bookmark', 'post.note', 'post.quotation')
       and 'Y' = CASE WHEN ch.`privacy_type` = 'visibility.public' THEN 'Y'
                      WHEN pa.`account_id` = `in_account_id` THEN 'Y'
                      ELSE 'N' END
       and 'Y' = CASE WHEN po.`privacy_type` = 'visibility.public' THEN 'Y'
                      WHEN pa.`account_id` = `in_account_id` THEN 'Y'
                      ELSE 'N' END
       and po.`publish_at` BETWEEN CASE WHEN `in_since_unix` = 0 THEN DATE_SUB(Now(), INTERVAL 6 MONTH) ELSE FROM_UNIXTIME(`in_since_unix`) END AND
                                   CASE WHEN `in_until_unix` = 0 THEN Now() ELSE FROM_UNIXTIME(`in_until_unix`) END
     ORDER BY CASE WHEN `in_since_unix` = 0 THEN 1 ELSE po.`publish_at` END, po.`publish_at` DESC
     LIMIT 0, `in_count`;

    DROP TEMPORARY TABLE IF EXISTS refs;
    CREATE TEMPORARY TABLE refs AS
    SELECT pm.`post_id`, GROUP_CONCAT(CONCAT('{"guid": "', pa.`guid`, '", "as": "@', pa.`name`, '", "is_you": ', CASE WHEN pa.`account_id` = `in_account_id` THEN 'true' ELSE 'false' END, '}')) as `mentions`
      FROM `Persona` pa INNER JOIN `PostMention` pm ON pa.`id` = pm.`persona_id`
     WHERE pa.`is_deleted` = 'N' and pm.`is_deleted` = 'N'
     GROUP BY pm.`post_id`;


    /* Output the Completed Timeline */
    SELECT * FROM tmp
     ORDER BY CASE WHEN `in_since_unix` = 0 THEN 1 ELSE tmp.`publish_at` END, tmp.`publish_at` DESC


END ;;
DELIMITER ;