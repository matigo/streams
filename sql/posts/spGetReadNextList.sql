DELIMITER ;;
DROP PROCEDURE IF EXISTS GetReadNextList;;
CREATE PROCEDURE GetReadNextList( IN `in_post_guid` varchar(36) )
BEGIN
    DECLARE `curr_id` int(11);
    DECLARE `rand_id` int(11);

    /** ********************************************************************** **
     *  Function returns the Visible Posts for the Mentions Timeline
     *
     *  Usage: CALL GetReadNextList('8b6ac087-5a0d-11e8-b49f-54ee758049c3');
     ** ********************************************************************** **/

    DROP TEMPORARY TABLE IF EXISTS tmpPosts;
    CREATE TEMPORARY TABLE tmpPosts AS
    SELECT ROW_NUMBER() OVER (PARTITION BY po.`channel_id` ORDER BY po.`publish_at`, po.`id`) AS `post_num`,
           CASE WHEN po.`guid` = src.`guid` THEN 'Y' ELSE 'N' END as `is_current`,
           po.`channel_id`, po.`id` as `post_id`, po.`canonical_url`, po.`title`, ROUND(UNIX_TIMESTAMP(po.`publish_at`)) as `publish_unix`, po.`type`, po.`guid`
      FROM `Post` po INNER JOIN (SELECT z.`channel_id`, z.`type`, z.`guid` FROM `Post` z
                                  WHERE z.`is_deleted` = 'N' and z.`guid` = `in_post_guid`
                                  ORDER BY z.`id` LIMIT 1) src ON po.`channel_id` = src.`channel_id` AND po.`type` = src.`type`
     WHERE po.`is_deleted` = 'N' and po.`privacy_type` = 'visibility.public'
     ORDER BY `post_num`;

    /* Determine the Current Record */
    SET `curr_id` = (SELECT `post_num` FROM tmpPosts WHERE `is_current` = 'Y' LIMIT 1);
    SET `rand_id` = (SELECT `post_num` FROM tmpPosts
                      WHERE `is_current` = 'N' AND `post_num` NOT IN (`curr_id` - 1, `curr_id` + 1)
                      ORDER BY RAND() LIMIT 1);

    /* Get the previous, next, and a random record */
    SELECT CASE WHEN tmp.`post_num` = (`curr_id` - 1) THEN 'previous'
                WHEN tmp.`post_num` = (`curr_id` + 1) THEN 'next'
                WHEN tmp.`post_num` = `curr_id` THEN 'curr'
                ELSE 'random' END as `key`,
           tmp.`post_num`, tmp.`canonical_url`, tmp.`title`, tmp.`publish_unix`, tmp.`type`, tmp.`guid`
      FROM tmpPosts tmp
     WHERE tmp.`post_num` IN (`curr_id` - 1, `curr_id` + 1, `rand_id`)
     ORDER BY tmp.`post_num`;

END ;;
DELIMITER ;