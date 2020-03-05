DELIMITER ;;
DROP PROCEDURE IF EXISTS GetSyndicationUrlsToUpdate;;
CREATE PROCEDURE GetSyndicationUrlsToUpdate( IN `in_minutes` int(11), IN `in_limit` int(11) )
BEGIN

    /** ********************************************************************** **
     *  Function collects the next round of RSS feeds to read and parse.
     *
     *  Usage: CALL GetSyndicationUrlsToUpdate( 1, 5 );
     ** ********************************************************************** **/

    /* Ensure the Gap Between Requests Isn't Too Short */
    IF IFNULL(`in_minutes`, 0) < 15 THEN
        SET `in_minutes` = 15;
    END IF;

    /* Ensure the number of feeds returned is logical */
    IF IFNULL(`in_limit`, 0) NOT BETWEEN 1 AND 100 THEN
        SET `in_limit` = 10;
    END IF;

    /* Collect the Feeds */
    SELECT sf.`title`, sf.`description`, sf.`site_url`, sf.`feed_url`, sf.`icon`, sf.`guid`, sf.`polled_at`,
           COUNT(DISTINCT acct.`id`) as `subscribers`, MAX(sfi.`publish_at`) as `recent_post_at`
      FROM `SyndFeed` sf INNER JOIN `SyndSubscribe` ss ON sf.`id` = ss.`feed_id`
                         INNER JOIN `Account` acct ON ss.`account_id` = acct.`id`
                    LEFT OUTER JOIN `SyndFeedItem` sfi ON sf.`id` = sfi.`feed_id`
     WHERE sf.`is_deleted` = 'N' and sf.`polled_at` <= DATE_SUB(Now(), INTERVAL `in_minutes` MINUTE)
     GROUP BY sf.`title`, sf.`description`, sf.`site_url`, sf.`feed_url`, sf.`icon`, sf.`guid`, sf.`polled_at`
     ORDER BY sf.`polled_at`
     LIMIT `in_limit`;

END;;
DELIMITER ;