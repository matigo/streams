DELIMITER ;;
DROP PROCEDURE IF EXISTS SetSyndicationItem;;
CREATE PROCEDURE SetSyndicationItem( IN `in_feed_id` int(11), IN `in_title` varchar(512), IN `in_content` text, IN `in_url` varchar(512),
                                     IN `in_publish_at` datetime, IN `in_guid` varchar(64), IN `in_hash` varchar(64), IN `in_words` text )
BEGIN
    DECLARE `x_item_id`     int(11);
    DECLARE `do_update`     enum('N','Y');

    /** ********************************************************************** **
     *  Function Inserts or Updates a Record in the SyndFeedItem Table and
     *      returns the SyndFeedItem.id Value
     *
     *  Usage: CALL SetSyndicationItem( 1, 'Self-Isolate', '<img src="https://imgs.xkcd.com/comics/self_isolate.png" title="Turns out I\'ve been &quot;practicing social distancing&quot; for years without even realizing it was a thing!" alt="Turns out I\'ve been &quot;practicing social distancing&quot; for years without even realizing it was a thing!" />', 'https://xkcd.com/2276/', '2020-03-04 05:00:00', '3d4e84d5-645b-45b3-038f-4239bdefdbf3', '5cdfce1e74dd1edaa302d65066ea65e1', '' );
     ** ********************************************************************** **/

    /* If the GUID is bad, Exit */
    IF LENGTH(IFNULL(`in_guid`, '')) <> 36 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid GUID Provided';
    END IF;

    /* If the Header Hash is bad, Exit */
    IF LENGTH(IFNULL(`in_hash`, '')) <= 20 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Hash Provided';
    END IF;

    /* Ensure the Publication Date is Semi-Logical */
    IF `in_publish_at` NOT BETWEEN DATE_SUB(Now(), INTERVAL 100 YEAR) AND DATE_ADD(Now(), INTERVAL 100 YEAR) THEN
        SET `in_publish_at` = Now();
    END IF;

    /* Get the Item.id Value (If Applicable) */
    SELECT sfi.`id` INTO `x_item_id`
      FROM `SyndFeed` sf INNER JOIN `SyndFeedItem` sfi ON sf.`id` = sfi.`feed_id`
     WHERE sfi.`is_deleted` = 'N' and sf.`is_deleted` = 'N' and sf.`id` = `in_feed_id`
       and sfi.`guid` = `in_guid`
     ORDER BY sfi.`id` DESC
     LIMIT 1;

    /* If there is no item, create one */
    IF IFNULL(`x_item_id`, 0) <= 0 THEN
        INSERT INTO `SyndFeedItem` (`feed_id`, `title`, `content`, `url`, `publish_at`, `guid`, `hash`)
        SELECT `in_feed_id` as `feed_id`, LEFT(`in_title`, 512) as `title`, `in_content` as `content`, LOWER(LEFT(`in_url`, 2048)) as `url`,
               `in_publish_at` as `publish_at`, `in_guid` as `guid`, LEFT(`in_hash`, 40) as `hash`;
        SELECT LAST_INSERT_ID() INTO `x_item_id`;
           SET `do_update` = 'N';
    END IF;

    /* Update the Record (If Applicable) */
    IF IFNULL(`do_update`, 'Y') = 'Y' THEN
        UPDATE `SyndFeedItem` sfi
           SET sfi.`title` = LEFT(`in_title`, 512),
               sfi.`content` = `in_content`,
               sfi.`url` = LOWER(LEFT(`in_url`, 2048)),
               sfi.`publish_at` = `in_publish_at`,
               sfi.`hash` = LEFT(`in_hash`, 40),
               sfi.`is_deleted` = 'N'
         WHERE sfi.`id` = `x_item_id`;
    END IF;

    /* Record the Search Strings */
    UPDATE `SyndFeedItemSearch` sfi
       SET sfi.`is_deleted` = 'Y'
     WHERE sfi.`is_deleted` = 'N' and sfi.`item_id` = `x_item_id`;

    INSERT INTO `SyndFeedItemSearch` (`item_id`, `word`)
    SELECT zz.`item_id`, zz.`word`
      FROM (SELECT DISTINCT `x_item_id` as `item_id`, LOWER(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(`in_words`, ',', num.`id`), ',', -1))) as `word`
              FROM (SELECT (h*1000+t*100+u*10+v+1) as `id`
                      FROM (SELECT 0 h UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
                           (SELECT 0 t UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b,
                           (SELECT 0 u UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) c,
                           (SELECT 0 v UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) d) num
             WHERE num.`id` >= 0) zz
     WHERE zz.`word` NOT IN ('')
     ORDER BY zz.`word`
        ON DUPLICATE KEY UPDATE `is_deleted` = 'N';

     /* Return the Feed.id and Item.id For the Record */
     SELECT `in_feed_id` as `feed_id`, `x_item_id` as `item_id`;
 END;;
 DELIMITER ;