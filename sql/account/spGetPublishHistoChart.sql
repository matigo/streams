DELIMITER ;;
DROP PROCEDURE IF EXISTS GetPublishHistoChart;;
CREATE PROCEDURE GetPublishHistoChart( IN `in_persona_guid` varchar(64) )
BEGIN
    DECLARE `max_posts`   int(11);

    /** ********************************************************************** **
     *  Function collects the last 52 weeks of publishing history.
     *
     *  Usage: CALL GetPublishHistoChart( '0737c327-913d-c0d2-1229-1154f2a3caa9' );
     ** ********************************************************************** **/

    /* If the Lesson Record is bad, Exit */
    IF LENGTH(IFNULL(`in_persona_guid`, '')) <> 36 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Persona GUID Provided';
    END IF;

    DROP TEMPORARY TABLE IF EXISTS `tmpActivity`;
    CREATE TEMPORARY TABLE IF NOT EXISTS `tmpActivity` (
        `year`          smallint        UNSIGNED    NOT NULL    ,
        `week`          tinyint         UNSIGNED    NOT NULL    ,
        `dow`           tinyint         UNSIGNED    NOT NULL    ,
        `date`          datetime                    NOT NULL    ,
        `posts`         int(11)         UNSIGNED    NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    /* First assemble the blank values for 400 days */
    INSERT INTO `tmpActivity` (`year`, `week`, `dow`, `date`, `posts`)
    SELECT YEAR(num.`date`) as `year`, WEEK(num.`date`) as `week`, DAYOFWEEK(num.`date`) as `dow`,
           DATE_FORMAT(num.`date`, '%Y-%m-%d 00:00:00') as `date`, 0 as `posts`
      FROM (SELECT DATE_SUB(Now(), INTERVAL (h*1000+t*100+u*10+v) - 7 DAY) as `date`, (h*1000+t*100+u*10+v) as `id`
              FROM (SELECT 0 h UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
                   (SELECT 0 t UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b,
                   (SELECT 0 u UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) c,
                   (SELECT 0 v UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) d) num
     WHERE num.`id` <= 400
     ORDER BY num.`id`;

    /* Now collect the actual values for the past 53 weeks */
    INSERT INTO `tmpActivity` (`year`, `week`, `dow`, `date`, `posts`)
    SELECT YEAR(po.`created_at`) as `year`, WEEK(po.`created_at`) as `week`, DAYOFWEEK(po.`created_at`) as `dow`,
           DATE_FORMAT(po.`created_at`, '%Y-%m-%d 00:00:00') as `date`, COUNT(po.`id`) as `posts`
      FROM `Persona` pa INNER JOIN `Post` po ON pa.`id` = po.`persona_id`
     WHERE po.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and pa.`guid` = `in_persona_guid`
       and po.`created_at` BETWEEN DATE_FORMAT(DATE_SUB(DATE_SUB(Now(), INTERVAL DAYOFWEEK(Now()) - 1 DAY), INTERVAL 53 WEEK), '%Y-%m-%d') AND Now()
     GROUP BY `year`, `week`, `dow`, `date`
     ORDER BY `year`, `week`, `dow`;

    /* Get the Maximum Post Count for a Period */
    SELECT CAST(MAX(`posts`) AS UNSIGNED) INTO `max_posts`
      FROM `tmpActivity` ta;

    IF IFNULL(`max_posts`, 0) < 1 THEN
        SET `max_posts` = 1;
    END IF;

    /* Return the HistoChart Data */
    SELECT CASE WHEN act.`week` BETWEEN 1 AND 52 THEN act.`year`
                WHEN act.`week` = 0 THEN act.`year` - 1 END as `year`,
           CASE WHEN act.`week` BETWEEN 1 AND 51 THEN act.`week` ELSE 52 END as `week`,
           act.`dow`, act.`date`,
           MAX(act.`posts`) as `posts`,
           CASE WHEN ROUND(CAST(MAX(act.`posts`) AS decimal(16,8)) / `max_posts`, 3) BETWEEN 0.001 AND 0.199 THEN 0.2
                WHEN ROUND(CAST(MAX(act.`posts`) AS decimal(16,8)) / `max_posts`, 3) BETWEEN 0.200 AND 0.399 THEN 0.4
                WHEN ROUND(CAST(MAX(act.`posts`) AS decimal(16,8)) / `max_posts`, 3) BETWEEN 0.400 AND 0.599 THEN 0.6
                WHEN ROUND(CAST(MAX(act.`posts`) AS decimal(16,8)) / `max_posts`, 3) BETWEEN 0.600 AND 0.799 THEN 0.8
                WHEN ROUND(CAST(MAX(act.`posts`) AS decimal(16,8)) / `max_posts`, 3) >= 0.800 THEN 1.0
                ELSE 0 END as `opacity`
      FROM `tmpActivity` act
     WHERE 'Y' = CASE WHEN act.`year` = YEAR(Now()) AND act.`week` <= WEEK(Now()) THEN 'Y'
                      WHEN act.`year` = YEAR(Now()) - 1 AND act.`week` >= WEEK(Now()) THEN 'Y'
                      ELSE 'N' END
     GROUP BY act.`year`, act.`week`, act.`dow`, act.`date`
     ORDER BY act.`year`, act.`week`, act.`dow`;

END;;
DELIMITER ;