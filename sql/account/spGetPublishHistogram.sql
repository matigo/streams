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

    /* Construct a Quick Temporary Table of Data */
      DROP TEMPORARY TABLE IF EXISTS tmp;
    CREATE TEMPORARY TABLE tmp (
        `year`          smallint       UNSIGNED NOT NULL    ,
        `week_no`       tinyint        UNSIGNED NOT NULL    ,

        `articles`      int(11)        UNSIGNED NOT NULL    DEFAULT 0,
        `bookmarks`     int(11)        UNSIGNED NOT NULL    DEFAULT 0,
        `locations`     int(11)        UNSIGNED NOT NULL    DEFAULT 0,
        `notes`         int(11)        UNSIGNED NOT NULL    DEFAULT 0,
        `photos`        int(11)        UNSIGNED NOT NULL    DEFAULT 0,
        `quotations`    int(11)        UNSIGNED NOT NULL    DEFAULT 0
    ) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    /* Populate the Preliminary Data */
    INSERT INTO `tmp` (`year`, `week_no`)
    SELECT DISTINCT YEAR(DATE_SUB(DATE_ADD(Now(), INTERVAL(1-DAYOFWEEK(Now())) DAY), INTERVAL (h*100+t*10+u+1) - 1 DAY)) as `year`,
                    WEEK(DATE_SUB(DATE_ADD(Now(), INTERVAL(1-DAYOFWEEK(Now())) DAY), INTERVAL (h*100+t*10+u+1) - 1 DAY)) as `week_no`
                              FROM (SELECT 0 h UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
                                   (SELECT 0 t UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b,
                                   (SELECT 0 u UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) c
     ORDER BY `year` DESC, `week_no` DESC;

     /* Grab the Persona-specific Information */
     INSERT INTO `tmp` (`year`, `week_no`, `articles`, `bookmarks`, `locations`, `notes`, `photos`, `quotations`)
    SELECT YEAR(DATE_ADD(po.`publish_at`, INTERVAL(1-DAYOFWEEK(po.`publish_at`)) DAY)) as `year`,
           WEEK(DATE_ADD(po.`publish_at`, INTERVAL(1-DAYOFWEEK(po.`publish_at`)) DAY)) as `week_no`,
           COUNT(CASE WHEN po.`type` = 'post.article' THEN po.`id` ELSE NULL END) as `articles`,
           COUNT(CASE WHEN po.`type` = 'post.bookmark' THEN po.`id` ELSE NULL END) as `bookmarks`,
           COUNT(CASE WHEN po.`type` = 'post.location' THEN po.`id` ELSE NULL END) as `locations`,
           COUNT(CASE WHEN po.`type` = 'post.note' THEN po.`id` ELSE NULL END) as `notes`,
           COUNT(CASE WHEN po.`type` = 'post.photo' THEN po.`id` ELSE NULL END) as `photos`,
           COUNT(CASE WHEN po.`type` = 'post.quotation' THEN po.`id` ELSE NULL END) as `quotations`
      FROM `Persona` pa INNER JOIN `Post` po ON pa.`id` = po.`persona_id`
     WHERE po.`is_deleted` = 'N' and pa.`is_deleted` = 'N'
       and Now() BETWEEN po.`publish_at` AND IFNULL(po.`expires_at`, DATE_ADD(Now(), INTERVAL 1 MINUTE))
       and po.`publish_at` >= DATE_FORMAT(DATE_SUB(Now(), INTERVAL 55 WEEK), '%Y-%m-%d 00:00:00')
       and pa.`guid` = `in_persona_guid`
     GROUP BY `year`, `week_no`
     ORDER BY `year` DESC, `week_no` DESC;

    /* Return a Collection of Data */
    SELECT t.`year`, t.`week_no`,
           SUM(t.`articles`) as `articles`, SUM(t.`bookmarks`) as `bookmarks`,
           SUM(t.`locations`) as `locations`, SUM(t.`notes`) as `notes`,
           SUM(t.`photos`) as `photos`, SUM(t.`quotations`) as `quotations`
      FROM `tmp` t
     WHERE t.`week_no` > 0
     GROUP BY t.`year`, t.`week_no`
     ORDER BY t.`year` DESC, t.`week_no` DESC
     LIMIT 52;

END;;
DELIMITER ;