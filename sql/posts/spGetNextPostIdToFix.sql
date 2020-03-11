DELIMITER ;;
DROP PROCEDURE IF EXISTS GetNextPostIdToFix;;
CREATE PROCEDURE GetNextPostIdToFix( IN `in_since_id` int(11) )
BEGIN
    DECLARE `x_word_limit`  int(11);

    /** ********************************************************************** **
     *  Function returns a list of post ids that do not contain information in
     *      the PostSearch table.
     *
     *  Usage: CALL GetNextPostIdToFix( 0 );
     ** ********************************************************************** **/

    DECLARE EXIT HANDLER FOR SQLEXCEPTION, SQLWARNING
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    SELECT tmp.`post_id`, tmp.`value`, tmp.`words`
      FROM (SELECT po.`id` as `post_id`, po.`value`, COUNT(DISTINCT ps.`word`) as `words`
              FROM `Post` po LEFT OUTER JOIN `PostSearch` ps ON po.`id` = ps.`post_id` AND ps.`is_deleted` = 'N'
             WHERE po.`is_deleted` = 'N' and po.`id` > `in_since_id`
             GROUP BY po.`id`, po.`value`
             ORDER BY `words`, po.`id`
             LIMIT 250) tmp
     WHERE tmp.`words` <= 0;
END;;
DELIMITER ;