DELIMITER ;;
DROP PROCEDURE IF EXISTS SetPostWebMention;;
CREATE PROCEDURE SetPostWebMention( IN `in_site_id` int(11), IN `in_canon_url` varchar(512),
                                    IN `in_source_url` varchar(2048), IN `in_avatar_url` varchar(2048), IN `in_author` varchar(512), IN `in_comment` text )
BEGIN

    /** ********************************************************************** **
     *  Function records a WebMention into the database
     *
     *  Usage: CALL SetPostWebMention(1, '/2019/09/15/five-things-10',
                                         'https://stream.jeremycherfas.net/2020/funny-to-feel-a-close-affinity-with',
                                         'https://stream.jeremycherfas.net/file/ef801e05730eba56004c5b712ad84731/thumb.jpg',
                                         'Jeremy Cherfas',
                                         'Funny to feel a close affinity with what is happening to a friend half a world away, and interesting that Jason too needs his parks and open spaces. The biggest park here is the remnants of an old established family villa, and so is surrounded by high walls. That is how they manage to close it off. Another big park on the other side of town is much more open, and the rumours are that the city cops are just hanging out around the perimeter, trying to keep people out.');
     ** ********************************************************************** **/

    /* If the Site.id is clearly bad, Exit */
    IF IFNULL(`in_site_id`, 0) <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Site.id Provided';
    END IF;

    /* If the Canonical URL is clearly bad, Exit */
    IF LENGTH(IFNULL(`in_canon_url`, '')) <= 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Canonical URL Provided';
    END IF;

    /* If the Source URL is clearly bad, Exit */
    IF LENGTH(IFNULL(`in_source_url`, '')) <= 10 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Source URL Provided';
    END IF;

    /* Record or Update the WebMention */
    INSERT INTO `PostWebMention` (`post_id`, `url_hash`, `url`, `avatar_url`, `author`, `comment`)
    SELECT po.`id` as `post_id`, sha2(`in_source_url`, 256) as `url_hash`, LEFT(`in_source_url`, 1024) as `url`,
           LEFT(`in_avatar_url`, 1024) as `avatar_url`, LEFT(`in_author`, 80) as `author`,
           `in_comment` as `comment`
      FROM `Channel` ch INNER JOIN `Post` po ON ch.`id` = po.`channel_id`
     WHERE ch.`is_deleted` = 'N' and po.`is_deleted` = 'N'
       and ch.`privacy_type` = 'visibility.public' and ch.`site_id` = `in_site_id`
       and po.`privacy_type` = 'visibility.public' and po.`canonical_url` = `in_canon_url`
     LIMIT 1
        ON DUPLICATE KEY UPDATE `avatar_url` = LEFT(`in_avatar_url`, 1024),
                                `author` = LEFT(`in_author`, 80),
                                `comment` = `in_comment`,
                                `updated_at` = Now();

 END;;
 DELIMITER ;