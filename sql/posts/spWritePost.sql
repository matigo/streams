DELIMITER ;;
DROP PROCEDURE IF EXISTS WritePost;;
CREATE PROCEDURE WritePost( IN `in_account_id` int(11), IN `in_channel_guid` varchar(36), IN `in_persona_guid` varchar(36), IN `in_token_guid` varchar(64), IN `in_token_id` int(11),
                            IN `in_title` varchar(512), IN `in_content` text, IN `in_words` text,
                            IN `in_canon_url` varchar(512), IN `in_reply_to` varchar(512), IN `in_slug` varchar(255),
                            IN `in_type` varchar(64), IN `in_privacy` varchar(64), IN `in_publish_at` varchar(40), IN `in_expires_at` varchar(40),
                            IN `in_thread` int(11), IN `in_parent` int(11), IN `in_post_id` int(11) )
BEGIN
    DECLARE `x_post_id`     int(11);
    DECLARE `x_publish_at`  datetime;
    DECLARE `x_expires_at`  datetime;
    DECLARE `x_word_limit`  int(11);

    /** ********************************************************************** **
     *  Function Inserts or Updates a record in the Post table and returns
     *      the Post.id value.
     *
     *  Usage: CALL WritePost(1, '91c46924-5461-11e8-99a0-54ee758049c3', '07d2f4ec-545f-11e8-99a0-54ee758049c3', '1eda3a28-8cc8-11e9-98bd-7b3182f95ce1-f8d1-a87ff679', 538,
                              'Short Title', 'A short body, too.', 'a,short,body,too',
                              '/2019/06/18/short-title', '', 'short-title',
                              'post.article', 'visibility.public', '2019-06-18 04:00:00', '',
                              0, 0, 0);
     ** ********************************************************************** **/

    DECLARE EXIT HANDLER FOR SQLEXCEPTION, SQLWARNING
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    /* If the Channel GUID is bad, Exit */
    IF LENGTH(IFNULL(`in_channel_guid`, '')) <> 36 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Channel GUID Provided';
    END IF;

    /* If the Persona GUID is bad, Exit */
    IF LENGTH(IFNULL(`in_persona_guid`, '')) <> 36 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Persona GUID Provided';
    END IF;

    /* Prep the Post Record Accordingly */
      DROP TEMPORARY TABLE IF EXISTS tmp;
    CREATE TEMPORARY TABLE tmp AS
    SELECT po.`id`, pa.`id` as `persona_id`, tt.`client_id`,
           CASE WHEN IFNULL(`in_thread`, 0) > 0 THEN IFNULL(`in_thread`, 0) ELSE NULL END as `thread_id`,
           CASE WHEN IFNULL(`in_parent`, 0) > 0 THEN IFNULL(`in_parent`, 0) ELSE NULL END as `parent_id`,
           CASE WHEN IFNULL(`in_title`, '') <> '' THEN LEFT(IFNULL(`in_title`, ''), 512) ELSE NULL END as `title`,
           IFNULL(`in_content`, '') as `value`,
           CASE WHEN IFNULL(`in_canon_url`, '') <> '' THEN LEFT(IFNULL(`in_canon_url`, ''), 512) ELSE NULL END as `canonical_url`,
           CASE WHEN IFNULL(`in_reply_to`, '')  <> '' THEN LEFT(IFNULL(`in_reply_to`, ''), 512) ELSE NULL END as `reply_to`, ca.`channel_id`,
           CASE WHEN IFNULL(`in_slug`, '') <> '' THEN LEFT(IFNULL(`in_slug`, ''), 255) ELSE NULL END as `slug`,
           IFNULL(`in_type`, '') as `type`, CASE WHEN IFNULL(`in_privacy`, '') <> '' THEN IFNULL(`in_privacy`, '') ELSE NULL END as `privacy_type`,
           IFNULL((SELECT z.`value` FROM `AccountMeta` z WHERE z.`account_id` = tt.`account_id` and z.`key` = 'post.has_published' LIMIT 1), 'N') as `has_published`,
           IFNULL((SELECT CAST(FROM_UNIXTIME(z.`value`) AS datetime) FROM `AccountMeta` z
                    WHERE z.`account_id` = tt.`account_id` and z.`key` = 'post.recent_at' LIMIT 1), Now()) as `recent_at`,
           CASE WHEN IFNULL(`in_publish_at`, '') <> '' THEN DATE_FORMAT(IFNULL(`in_publish_at`, ''), '%Y-%m-%d %H:%i:%s') ELSE Now() END as `publish_at`,
           CASE WHEN IFNULL(`in_expires_at`, '2000-01-01 00:00:00') > DATE_FORMAT(Now(), '%Y-%m-%d %H:%i:%s') THEN IFNULL(`in_expires_at`, '2000-01-01 00:00:00') ELSE NULL END as `expires_at`,
           tt.`account_id` as `created_by`, tt.`account_id` as `updated_by`
      FROM `Tokens` tt INNER JOIN `Account` a ON tt.`account_id` = a.`id`
                       INNER JOIN `Persona` pa ON a.`id` = pa.`account_id`
                       INNER JOIN `ChannelAuthor` ca ON pa.`id` = ca.`persona_id`
                       INNER JOIN `Channel` ch ON ca.`channel_id` = ch.`id`
                  LEFT OUTER JOIN `Post` po ON po.`is_deleted` = 'N' and po.`id` = IFNULL(`in_post_id`, 0)
     WHERE tt.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and a.`is_deleted` = 'N'
       and ca.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and ca.`can_write` = 'Y'
       and tt.`updated_at` >= DATE_SUB(Now(), INTERVAL 30 DAY) and ch.`guid` = `in_channel_guid`
       and pa.`guid` = `in_persona_guid` and tt.`guid` = `in_token_guid` and tt.`id` = `in_token_id`
     LIMIT 1;

    /* Record the Data to the Post table */
    INSERT INTO `Post` (`id`, `persona_id`, `client_id`, `thread_id`, `parent_id`,
                        `title`, `value`,
                        `canonical_url`, `reply_to`, `channel_id`,
                        `slug`, `type`, `privacy_type`,
                        `publish_at`, `expires_at`, `created_by`, `updated_by`)
    SELECT tmp.`id`, tmp.`persona_id`, tmp.`client_id`, tmp.`thread_id`, tmp.`parent_id`,
           tmp.`title`, tmp.`value`,
           tmp.`canonical_url`, tmp.`reply_to`, tmp.`channel_id`,
           tmp.`slug`, tmp.`type`, tmp.`privacy_type`,
           tmp.`publish_at`, tmp.`expires_at`, tmp.`created_by`, tmp.`updated_by`
      FROM tmp
     WHERE tmp.`value` <> ''
        ON DUPLICATE KEY UPDATE `persona_id` = tmp.`persona_id`,
                                `thread_id` = tmp.`thread_id`,
                                `parent_id` = tmp.`parent_id`,
                                `channel_id` = tmp.`channel_id`,
                                `title` = tmp.`title`,
                                `value` = tmp.`value`,
                                `canonical_url` = CASE WHEN tmp.`canonical_url` <> '' THEN tmp.`canonical_url` ELSE Post.`canonical_url` END,
                                `reply_to` = tmp.`reply_to`,
                                `slug` = CASE WHEN tmp.`slug` <> '' THEN tmp.`slug` ELSE Post.`slug` END,
                                `type` = tmp.`type`,
                                `privacy_type` = tmp.`privacy_type`,
                                `publish_at` = tmp.`publish_at`,
                                `expires_at` = tmp.`expires_at`,
                                `updated_by` = tmp.`updated_by`,
                                `updated_at` = Now();

        SELECT LAST_INSERT_ID() INTO `x_post_id`;

        IF IFNULL(`x_post_id`, 0) <= 0 AND `in_post_id` > 0 THEN
            SET `x_post_id` = `in_post_id`;
        END IF;

        IF IFNULL(`x_post_id`, 0) <= 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Could not insert into Post';
        END IF;

        /* Update the Site Version */
        UPDATE `Channel` ch INNER JOIN `Site` si ON ch.`site_id` = si.`id`
           SET si.`version` = UNIX_TIMESTAMP(Now()),
               si.`updated_at` = Now()
         WHERE si.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and ch.`guid` = `in_channel_guid`;

        /* Ensure the Post.Type Exists in the Site Meta (Used for the NavBar on Sites) */
        INSERT INTO `SiteMeta` (`site_id`, `key`, `value`)
        SELECT ch.`site_id`, REPLACE(po.`type`, 'post.', 'has_') as `key`, 'Y' as `value`
          FROM `Post` po INNER JOIN `Channel` ch ON po.`channel_id` = ch.`id`
         WHERE po.`is_deleted` = 'N' and po.`id` = `x_post_id`
            ON DUPLICATE KEY UPDATE `is_deleted` = 'N';

        /* Mark any Files as Detached */
        DELETE FROM `PostFile` pf WHERE pf.`is_deleted` = 'N' and pf.`post_id` = `x_post_id`;

        /* Mark any Mentions as Deleted */
        DELETE FROM `PostMention` pm WHERE pm.`is_deleted` = 'N' and pm.`post_id` = `x_post_id`;

        /* Mark any Meta as Deleted */
        DELETE FROM `PostMeta` pm WHERE pm.`is_deleted` = 'N' and pm.`post_id` = `x_post_id`;

        /* Mark any Search Items as Deleted */
        DELETE FROM `PostSearch` ps WHERE ps.`is_deleted` = 'N' and ps.`post_id` = `x_post_id`;

        /* Set the Post Search Items Accordingly */
        SELECT ROUND(ROUND(LENGTH(`in_words`) / 3.25, 0), -1) + 50 as `chars` INTO `x_word_limit`;

        INSERT INTO `PostSearch` (`post_id`, `word`)
        SELECT zz.`post_id`, zz.`word`
          FROM (SELECT DISTINCT IFNULL(`x_post_id`, `in_post_id`) as `post_id`, LOWER(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(`in_words`, ',', num.`id`), ',', -1))) as `word`
                  FROM (SELECT (h*1000+t*100+u*10+v+1) as `id`
                          FROM (SELECT 0 h UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
                               (SELECT 0 t UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b,
                               (SELECT 0 u UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) c,
                               (SELECT 0 v UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) d) num
                 WHERE num.`id` <= IFNULL(`x_word_limit`, 250)) zz
         WHERE zz.`word` NOT IN ('')
         ORDER BY zz.`word`;

        /* Set any Post Mentions that Might Exist */
        INSERT INTO `PostMention` (`post_id`, `persona_id`, `created_at`)
        SELECT ps.`post_id`, pa.`id` as `persona_id`, ps.`created_at`
          FROM `PostSearch` ps INNER JOIN `Persona` pa ON ps.`word` = CONCAT('@', pa.`name`)
         WHERE ps.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and ps.`post_id` = IFNULL(`x_post_id`, `in_post_id`)
            ON DUPLICATE KEY UPDATE `is_deleted` = 'N',
                                    `updated_at` = Now();

        /* Mark any Files contained in the Post as Attached */
        INSERT INTO `PostFile` (`post_id`, `file_id`, `created_at`, `updated_at`)
        SELECT po.`id` as `post_id`, fi.`id` as `file_id`, po.`created_at`, po.`updated_at`
          FROM `File` fi INNER JOIN `Post` po ON po.`is_deleted` = 'N' and po.`id` = IFNULL(`x_post_id`, `in_post_id`)
         WHERE LOCATE(CONCAT(fi.`location`, fi.`hash`), po.`value`) > 0
         ORDER BY po.`id`, fi.`id`
            ON DUPLICATE KEY UPDATE `is_deleted` = 'N',
                                    `updated_at` = Now();

        /* Update the AccountMeta for the values */
        INSERT INTO `AccountMeta` (`account_id`, `key`, `value`)
        SELECT acct.`id` as `account_id`, meta.`key`, meta.`value`
          FROM `Account` acct INNER JOIN (SELECT 'post.recent_at' as `key`, CAST(UNIX_TIMESTAMP(Now()) AS CHAR(64)) as `value` UNION ALL
                                          SELECT 'post.has_published' as `key`, 'Y' as `value`) meta ON meta.`key` IS NOT NULL
         WHERE acct.`id` = `in_account_id`
            ON DUPLICATE KEY UPDATE `value` = meta.`value`,
                                    `updated_at` = Now();

        /* Check for Duplicate Post Objects and Handle them Accordingly */
        UPDATE `Post` p INNER JOIN (SELECT SHA1(CONCAT(p.`persona_id`, IFNULL(p.`client_id`, 0), IFNULL(p.`thread_id`, 0), IFNULL(p.`parent_id`, 0), p.`value`,
                                                       IFNULL(p.`reply_to`, ''), p.`channel_id`, p.`privacy_type`, p.`created_by`, p.`updated_by`)) as `sha1`,
                                           COUNT(p.`id`) as `posts`, MAX(p.`id`) as `max_id`, GROUP_CONCAT(p.`id`) as `post_ids`
                                      FROM `Post` p
                                     WHERE p.`is_deleted` = 'N' and p.`created_by` = `in_account_id` and p.`created_at` >= DATE_SUB(Now(), INTERVAL 15 MINUTE)
                                     GROUP BY `sha1`) tmp ON FIND_IN_SET(p.`id`, tmp.`post_ids`)
           SET p.`is_deleted` = CASE WHEN p.`id` = tmp.`max_id` THEN 'N' ELSE 'Y' END
         WHERE tmp.`posts` > 1;

        /* Return the Post.id for this Object */
        SELECT IFNULL(`x_post_id`, `in_post_id`) as `post_id`, t.`has_published`, DATEDIFF(Now(), t.`recent_at`) as `days_since`
          FROM `tmp` t
         LIMIT 1;
END;;
DELIMITER ;