DELIMITER ;;
DROP PROCEDURE IF EXISTS CheckUniqueCanonUrl;;
CREATE PROCEDURE CheckUniqueCanonUrl( IN `in_channel_guid` varchar(36), IN `in_post_guid` varchar(36), IN `in_url` varchar(512) )
BEGIN
    DECLARE `x_channel_id` int(11);

    /** ********************************************************************** **
     *  Function checks to see if a Given Canonical URL is unique for a Channel
     *      and provides an alternative if it is not.
     *
     *  Usage: CALL CheckUniqueCanonUrl('582ef8be-5d2b-11e8-99c0-54ee758049c3', '57d0a7d5-141d-e7ac-6372-c4c155d17a96', 'evernote');
     ** ********************************************************************** **/

    DECLARE EXIT HANDLER FOR SQLEXCEPTION, SQLWARNING
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    /* If the Channel GUID is clearly bad, Exit */
    IF LENGTH(IFNULL(`in_channel_guid`, '')) <> 36 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Channel GUID Provided';
    END IF;

    /* If the Post GUID is clearly bad, Exit */
    IF LENGTH(IFNULL(`in_post_guid`, '')) NOT IN (0, 36) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Post GUID Provided';
    END IF;

    /* If the Canonical URL is clearly bad, Exit */
    IF LENGTH(IFNULL(`in_url`, '')) <= 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Canonical URL Provided';
    END IF;

    /* Get the Channel.id Value */
    SELECT ch.`id` INTO `x_channel_id`
      FROM `Channel` ch
     WHERE ch.`is_deleted` = 'N' and ch.`guid` = `in_channel_guid`
     LIMIT 1;

    SELECT tmp.`url`
      FROM (SELECT num.`idx`, num.`url`, COUNT(DISTINCT po.`id`) as `matches`
              FROM (SELECT (h*100+i*10+j) as `idx`, CONCAT(`in_url`, CASE WHEN (h*100+i*10+j) > 0 THEN CONCAT('-', (h*100+i*10+j)) ELSE '' END) as `url`
                      FROM (SELECT 0 h UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
                           (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b,
                           (SELECT 0 j UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) c) num
                           LEFT OUTER JOIN `Post` po ON po.`is_deleted` = 'N' AND po.`type` NOT IN ('post.note') AND po.`canonical_url` = num.`url`
                                                    AND po.`channel_id` = `x_channel_id` AND po.`guid` <> `in_post_guid`
             GROUP BY num.`idx`, num.`url`
             ORDER BY num.`idx`) tmp
     WHERE tmp.`matches` = 0
     ORDER BY tmp.`idx`
     LIMIT 1;

 END;;
 DELIMITER ;