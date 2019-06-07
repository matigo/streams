DELIMITER ;;
DROP PROCEDURE IF EXISTS SetPostWriteContext;;
CREATE PROCEDURE SetPostWriteContext( IN `in_channel_guid` varchar(36), IN `in_post_id` int(11) )
BEGIN
    DECLARE `x_account_id`  int(11);
    DECLARE `x_word_limit`  int(11);
    DECLARE `x_post_id`     int(11);

    /** ********************************************************************** **
     *  Function returns the Visible Timeline for a Given Persona GUID
     *
     *  Usage: CALL SetPostWriteContext('91c46924-5461-11e8-99a0-54ee758049c3', 321709);
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

    /* Reset the Site Version */
    UPDATE `Channel` ch INNER JOIN `Site` si ON ch.`site_id` = si.`id`
       SET si.`version` = UNIX_TIMESTAMP(Now()),
           si.`updated_at` = Now()
     WHERE si.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and ch.`guid` = `in_channel_guid`;

    /* Ensure the Post.id Supplied Is Valid for the Channel */
    SELECT po.`id` INTO `x_post_id`
      FROM `Channel` ch INNER JOIN `Post` po ON ch.`id` = po.`channel_id`
     WHERE ch.`is_deleted` = 'N' and po.`is_deleted` = 'N' and ch.`guid` = `in_channel_guid`
       and po.`id` = IFNULL(`in_post_id`, 0)
     LIMIT 1;

    /* If a Post.id exists, we have a valid post. Set/Reset the Meta Values. */
    IF IFNULL(`x_post_id`, 0) > 0 THEN
        /* Determine the Word-Count Limit for this Post */
        SELECT ROUND(ROUND(LENGTH(po.`value`) / 3.25, 0), -1) + 50 as `chars` INTO `x_word_limit`
          FROM `Post` po
         WHERE po.`is_deleted` = 'N' and po.`id` = `x_post_id`;

        IF IFNULL(`x_word_limit`, 0) < 100 THEN
            SET `x_word_limit` = 100;
        END IF;

        /* Determine the Account.id of the Post Owner */
        SELECT pa.`account_id` INTO `x_account_id`
          FROM `Post` po INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
         WHERE pa.`is_deleted` = 'N' and po.`is_deleted` = 'N' and po.`id` = `x_post_id`
         LIMIT 1;

        /* Ensure the Post.Type Exists in the Site Meta (Used for the NavBar on Sites) */
        INSERT INTO `SiteMeta` (`site_id`, `key`, `value`)
        SELECT ch.`site_id`, REPLACE(po.`type`, 'post.', 'has_') as `key`, 'Y' as `value`
          FROM `Post` po INNER JOIN `Channel` ch ON po.`channel_id` = ch.`id`
         WHERE po.`is_deleted` = 'N' and po.`id` = `x_post_id`
            ON DUPLICATE KEY UPDATE `is_deleted` = 'N';

        /* Mark any Files as Detached */
        DELETE FROM `PostFile` pf WHERE pf.`post_id` = `x_post_id`;

        /* Mark any Mentions as Deleted */
        DELETE FROM `PostMention` pm WHERE pm.`post_id` = `x_post_id`;

        /* Mark any Meta as Deleted */
        DELETE FROM `PostMeta` pm WHERE pm.`post_id` = `x_post_id`;

        /* Mark the Post Search Items as Deleted */
        DELETE FROM `PostSearch` ps WHERE ps.`post_id` = `x_post_id`;

        /* Set the Post Search Items Accordingly */
        INSERT INTO `PostSearch` (`post_id`, `word`)
        SELECT zz.`post_id`, zz.`word`
          FROM (SELECT DISTINCT po.`id` as `post_id`, LOWER(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(txt.`words`, ',', num.`id`), ',', -1))) as `word`
                  FROM `Post` po INNER JOIN (SELECT (h*1000+t*100+u*10+v+1) as `id`
                                               FROM (SELECT 0 h UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
                                                    (SELECT 0 t UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b,
                                                    (SELECT 0 u UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) c,
                                                    (SELECT 0 v UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) d) num ON num.`id` <= IFNULL(`x_word_limit`, 100)
                                 CROSS JOIN (SELECT REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(z.`value`, '’', ''), '‘', ''), '”', ''), '“', ''), ';', ''), ':', ''), '#', ''), '"', ''), ')', ''), '(', ' '), '[', ' '), ']', ' '), '\'', ''), '/', ''), '
', ' '), '&', ''), '?', ''), '.', ''), '*', ''), ' ', ',') as `words` FROM `Post` z WHERE z.`id` = `x_post_id`) AS txt
                 WHERE po.`is_deleted` = 'N' and po.`id` = `x_post_id`) zz
         WHERE zz.`word` NOT IN ('')
         ORDER BY zz.`word`
            ON DUPLICATE KEY UPDATE `is_deleted` = 'N',
                                    `updated_at` = Now();

        /* Mark any Tags as Deleted */
        DELETE FROM `PostTags` pt WHERE pt.`post_id` = `x_post_id`;

        /* Set any Post Mentions that Might Exist */
        INSERT INTO `PostMention` (`post_id`, `persona_id`, `created_at`)
        SELECT ps.`post_id`, pa.`id` as `persona_id`, ps.`created_at`
          FROM `PostSearch` ps INNER JOIN `Persona` pa ON ps.`word` = CONCAT('@', pa.`name`)
         WHERE ps.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and ps.`post_id` = `x_post_id`
            ON DUPLICATE KEY UPDATE `is_deleted` = 'N',
                                    `updated_at` = Now();

        /* Mark any Files contained in the Post as Attached */
        INSERT INTO `PostFile` (`post_id`, `file_id`, `created_at`, `updated_at`)
        SELECT po.`id` as `post_id`, fi.`id` as `file_id`, po.`created_at`, po.`updated_at`
          FROM `File` fi INNER JOIN `Post` po ON po.`is_deleted` = 'N' and po.`id` = `x_post_id`
         WHERE LOCATE(CONCAT(fi.`location`, fi.`local_name`), po.`value`) > 0
         ORDER BY po.`id`, fi.`id`
            ON DUPLICATE KEY UPDATE `is_deleted` = 'N',
                                    `updated_at` = Now();

        /* Check for Duplicate Post Objects and Handle them Accordingly */
        UPDATE `Post` p INNER JOIN (SELECT SHA1(CONCAT(p.`persona_id`, IFNULL(p.`client_id`, 0), IFNULL(p.`thread_id`, 0), IFNULL(p.`parent_id`, 0), p.`value`,
                                                       IFNULL(p.`reply_to`, ''), p.`channel_id`, p.`privacy_type`, p.`created_by`, p.`updated_by`)) as `sha1`,
                                           COUNT(p.`id`) as `posts`, MAX(p.`id`) as `max_id`, GROUP_CONCAT(p.`id`) as `post_ids`
                                      FROM `Post` p
                                     WHERE p.`is_deleted` = 'N' and p.`created_by` = `x_account_id` and p.`created_at` >= DATE_SUB(Now(), INTERVAL 15 MINUTE)
                                     GROUP BY `sha1`) tmp ON FIND_IN_SET(p.`id`, tmp.`post_ids`)
           SET p.`is_deleted` = CASE WHEN p.`id` = tmp.`max_id` THEN 'N' ELSE 'Y' END
         WHERE tmp.`posts` > 1;
    END IF;

    /* Return the Account.id (if known) */
    SELECT `x_account_id` as `account_id`, `x_post_id` as `post_id`;
END ;;
DELIMITER ;