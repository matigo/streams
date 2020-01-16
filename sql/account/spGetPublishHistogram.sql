DELIMITER ;;
DROP PROCEDURE IF EXISTS GetPublishHistogram;;
CREATE PROCEDURE GetPublishHistogram( IN `in_persona_guid` varchar(64) )
BEGIN

    /** ********************************************************************** **
     *  Function collects the last 52 weeks of publishing history.
     *
     *  Usage: CALL GetPublishHistogram( 'a8328e7e-9195-4aae-a9df-109ac7ed8f22' );
     ** ********************************************************************** **/

    /* If the Lesson Record is bad, Exit */
    IF LENGTH(IFNULL(`in_persona_guid`, '')) <> 36 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Persona GUID Provided';
    END IF;

    /* Collect the Publish Histogram */
    SELECT YEAR(t.`on_date`) as `year`, WEEK(t.`on_date`) as `week_no`,
           SUM(t.`articles`) as `articles`, SUM(t.`notes`) as `notes`, SUM(t.`quotations`) as `quotations`, SUM(t.`bookmarks`) as `bookmarks`, SUM(t.`locations`) as `locations`
      FROM (SELECT DATE_FORMAT(po.`publish_at`, '%Y-%m-%d 00:00:00') as `on_date`,
                   COUNT(DISTINCT CASE WHEN po.`type` = 'post.article' THEN po.`guid` ELSE NULL END) as `articles`,
                   COUNT(DISTINCT CASE WHEN po.`type` = 'post.note' THEN po.`guid` ELSE NULL END) as `notes`,
                   COUNT(DISTINCT CASE WHEN po.`type` = 'post.quotation' THEN po.`guid` ELSE NULL END) as `quotations`,
                   COUNT(DISTINCT CASE WHEN po.`type` = 'post.bookmark' THEN po.`guid` ELSE NULL END) as `bookmarks`,
                   COUNT(DISTINCT CASE WHEN po.`type` = 'post.location' THEN po.`guid` ELSE NULL END) as `locations`
              FROM `Account` acct INNER JOIN `Persona` pa ON acct.`id` = pa.`account_id`
                                  INNER JOIN `Post` po ON pa.`id` = po.`persona_id`
             WHERE acct.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and po.`is_deleted` = 'N'
              and po.`publish_at` BETWEEN DATE_FORMAT(DATE_SUB(Now(), INTERVAL 55 WEEK), '%Y-%m-%d 00:00:00') AND IFNULL(po.`expires_at`, Now())
              and pa.`guid` = `in_persona_guid`
             GROUP BY `on_date`
             UNION ALL
            SELECT DATE_FORMAT(DATE_SUB(Now(), INTERVAL (h*100+t*10+u+1) - 1 DAY), '%Y-%m-%d 00:00:00') as `on_date`,
                   CAST(0 AS UNSIGNED) as `articles`, CAST(0 AS UNSIGNED) as `notes`, CAST(0 AS UNSIGNED) as `quotations`,
                   CAST(0 AS UNSIGNED) as `bookmarks`, CAST(0 AS UNSIGNED) as `locations`
              FROM (SELECT 0 h UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
                   (SELECT 0 t UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b,
                   (SELECT 0 u UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) c) t
     WHERE t.`on_date` BETWEEN DATE_FORMAT(DATE_SUB(Now(), INTERVAL 1 YEAR), '%Y-%m-%d') AND DATE_FORMAT(Now(), '%Y-%m-%d')
     GROUP BY `year`, `week_no`
     ORDER BY `year`, `week_no`;

END;;
DELIMITER ;