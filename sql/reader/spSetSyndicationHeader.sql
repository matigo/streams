DELIMITER ;;
DROP PROCEDURE IF EXISTS SetSyndicationHeader;;
CREATE PROCEDURE SetSyndicationHeader( IN `in_title` varchar(512), IN `in_description` varchar(2048), IN `in_url` varchar(512), IN `in_feed` varchar(512),
                                       IN `in_language` varchar(10), IN `in_icon` varchar(512),
                                       IN `in_guid` varchar(64), IN `in_hash` varchar(64),
                                       IN `in_generator` varchar(512), IN `in_update_period` varchar(64), IN `in_update_freq` varchar(64),
                                       IN `in_image` varchar(512), IN `in_subtitle` varchar(2048), IN `in_explicit` varchar(64) )
BEGIN
    DECLARE `x_feed_id`     int(11);
    DECLARE `do_update`     enum('N','Y');

    /** ********************************************************************** **
     *  Function Inserts or Updates a Record in the SyndFeed Table and returns
     *      the SyndFeed.id Value for use elsewhere.
     *
     *  Usage: CALL SetSyndicationHeader( 'xkcd.com', 'xkcd.com: A webcomic of romance and math humor.', 'https://xkcd.com/',
                                          'en', 'https:/xkcd.com/s/919f27.ico',
                                          '3d4e84d5-645b-45b3-038f-4239bdefdbf3', '5cdfce1e74dd1edaa302d65066ea65e1'
                                          '', '', '',
                                          '', '', '' );
     ** ********************************************************************** **/

    /* If the GUID is bad, Exit */
    IF LENGTH(IFNULL(`in_guid`, '')) <> 36 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid GUID Provided';
    END IF;

    /* If the Header Hash is bad, Exit */
    IF LENGTH(IFNULL(`in_hash`, '')) <= 20 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Hash Provided';
    END IF;

    /* Determine the Feed.id */
    SELECT sf.`id` INTO `x_feed_id`
      FROM `SyndFeed` sf
     WHERE sf.`is_deleted` = 'N' and sf.`guid` = `in_guid`
     ORDER BY sf.`id`
     LIMIT 1;

     /* If there is no Feed.id, Create a Record */
     IF IFNULL(`x_feed_id`, 0) <= 0 THEN
         INSERT INTO `SyndFeed` (`title`, `description`, `site_url`, `feed_url`, `language_code`, `icon`, `guid`, `hash`, `polled_at`)
         SELECT LEFT(`in_title`, 512) as `title`, LEFT(`in_description`, 2048) as `description`,
                LOWER(LEFT(`in_url`, 512)) as `site_url`, LOWER(LEFT(`in_feed`, 512)) as `feed_url`,
                LOWER(LEFT(`in_language`, 10)) as `language_code`, LEFT(`in_icon`, 512) as `icon`,
                `in_guid` as `guid`, `in_hash` as `hash`, Now() as `polled_at`;
         SELECT LAST_INSERT_ID() INTO `x_feed_id`;
         SET `do_update` = 'N';
     END IF;

     /* Update the Record (If Applicable) */
     IF IFNULL(`do_update`, 'Y') = 'Y' THEN
        UPDATE `SyndFeed` sf
           SET `title` = LEFT(`in_title`, 512),
               `description` = LEFT(`in_description`, 2048),
               `site_url` = LOWER(LEFT(`in_url`, 512)),
               `feed_url` = LOWER(LEFT(`in_feed`, 512)),
               `language_code` = LOWER(LEFT(`in_language`, 10)),
               `icon` = LOWER(LEFT(`in_icon`, 512)),
               `hash` = `hash`,
               `polled_at` = Now(),
               `is_deleted` = 'N'
         WHERE sf.`id` = `x_feed_id`;
     END IF;

    /* Update the Meta Values (If Applicable) */
    INSERT INTO `SyndFeedMeta` (`feed_id`, `key`, `value`)
    SELECT `x_feed_id` as `feed_id`, tmp.`key`, tmp.`value`
      FROM (SELECT 'generator' as `key`, LEFT(`in_generator`, 512) as `value` UNION ALL
            SELECT 'update.period' as `key`, LOWER(LEFT(`in_update_period`, 64)) as `value` UNION ALL
            SELECT 'update.frequency' as `key`, LOWER(LEFT(`in_update_freq`, 64)) as `value` UNION ALL
            SELECT 'itunes.image' as `key`, LEFT(`in_image`, 512) as `value` UNION ALL
            SELECT 'itunes.subtitle' as `key`, LEFT(`in_subtitle`, 2048) as `value` UNION ALL
            SELECT 'itunes.explicit' as `key`, LOWER(LEFT(`in_explicit`, 64)) as `value`) tmp
        ON DUPLICATE KEY UPDATE `value` = tmp.`value`;

    /* Return the Feed.id for this Object */
    SELECT `x_feed_id` as `feed_id`;
END;;
DELIMITER ;