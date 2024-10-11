DELIMITER ;;
DROP PROCEDURE IF EXISTS SendWelcomeBotMsg;;
CREATE PROCEDURE SendWelcomeBotMsg( IN `in_account_id` int(11), IN `in_msg` varchar(2048) )
BEGIN
    DECLARE `x_post_id`     int(11);

    /** ********************************************************************** **
     *  Function sends a "Welcome Message" to a given account.
     *
     *  Usage: CALL SendWelcomeBotMsg(274, 'Welcome to 10Centuries, @{name}!');
     ** ********************************************************************** **/

    /* If the Account ID is bad, Use Zero to ensure that we can generally continue */
    IF IFNULL(`in_account_id`, 0) <= 0 THEN
        SET `in_account_id` = 0;
    END IF;

    INSERT INTO `Post` (`persona_id`, `client_id`, `value`, `channel_id`, `type`, `privacy_type`, `publish_at`, `created_by`, `updated_by`)
    SELECT pa.`id` as `persona_id`,
           (SELECT z.`id` FROM `Client` z WHERE z.`is_deleted` = 'N' ORDER BY z.`id` LIMIT 1) as `client_id`,
           (SELECT REPLACE(`in_msg`, '{name}', z.`name`) FROM `Persona` z
             WHERE z.`is_deleted` = 'N' and z.`account_id` = `in_account_id`
             ORDER BY z.`id` LIMIT 1) as `value`,
           ca.`channel_id`, 'post.note' as `type`, 'visibility.public' as `privacy_type`,
           DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 SECOND) as `publish_at`, pa.`account_id`, pa.`account_id`
      FROM `Persona` pa INNER JOIN `ChannelAuthor` ca ON pa.`id` = ca.`persona_id`
     WHERE ca.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and ca.`can_write` = 'Y'
       and pa.`name` = 'welcomebot'
     LIMIT 1;

    SELECT LAST_INSERT_ID() INTO `x_post_id`;

    INSERT INTO `PostMention` (`post_id`, `persona_id`)
    SELECT `x_post_id` as `post_id`, pa.`id`
      FROM `Persona` pa
     WHERE pa.`is_deleted` = 'N' and pa.`account_id` = `in_account_id`
     LIMIT 1;

    SELECT `x_post_id` as `post_id`;
END ;;
DELIMITER ;