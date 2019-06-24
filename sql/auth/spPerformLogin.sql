DELIMITER ;;
DROP PROCEDURE IF EXISTS PerformLogin;;
CREATE PROCEDURE PerformLogin( IN `in_account_mail` varchar(140), IN `in_password` varchar(2048), IN `in_channel_guid` varchar(64), IN `in_client_guid` varchar(64), IN `in_salt` varchar(64) )
BEGIN
    DECLARE `x_account_id`  int(11);
    DECLARE `x_token_id`    int(11);

    /** ********************************************************************** **
     *  Function attempts to perform a login and, so long as everything is valid,
     *      returns a Token.id and Token.guid value
     *
     *  Usage: CALL PerformLogin('{email}', '{password}', '{channel_guid}', '{client_guid}', '{salt}');
     ** ********************************************************************** **/

    /* If the Channel GUID is bad, Exit */
    IF LENGTH(IFNULL(`in_channel_guid`, '')) <> 36 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Channel GUID Provided';
    END IF;

    /* If the Client GUID is bad, Exit */
    IF LENGTH(IFNULL(`in_channel_guid`, '')) <> 36 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Client GUID Provided';
    END IF;

    /* If the Login Name is bad, Exit */
    IF LENGTH(IFNULL(`in_account_mail`, '')) < 3 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Login ID Provided';
    END IF;

    /* If the Password is bad, Exit */
    IF LENGTH(IFNULL(`in_password`, '')) < 6 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Password Provided';
    END IF;

    /* Determine if the Credentials are any good */
    DROP TEMPORARY TABLE IF EXISTS tmp;
    CREATE TEMPORARY TABLE tmp AS
    SELECT acct.`id` as `account_id`, acct.`type`, acct.`display_name`, acct.`language_code`,
           CAST(0 AS UNSIGNED) as `persona_count`, CAST('Y' AS CHAR(1)) as `can_read`, CAST('N' AS CHAR(1)) as `can_write`,
           CASE WHEN acct.`type` = 'account.admin' THEN 0
                ELSE DATEDIFF(Now(), IFNULL((SELECT max(tt.`updated_at`) FROM `Tokens` tt WHERE tt.`account_id` = acct.`id`), Now())) END as `last_activity`
      FROM `Account` acct
     WHERE acct.`is_deleted` = 'N' and acct.`type` IN ('account.admin', 'account.normal')
       and LOWER(acct.`email`) = LOWER(`in_account_mail`)
       and acct.`password` IN ( sha2(CONCAT(IFNULL(`in_salt`, ''), `in_password`), 512), sha2(CONCAT(DATE_FORMAT(acct.`created_at`, '%Y-%m-%d %H:%i:00'), `in_password`), 512) )
     LIMIT 1;

    IF (SELECT COUNT(`account_id`) FROM `tmp`) <> 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Bad credentials provided';
    END IF;

    /* Get the Account.id (This is required to get around concurrency issues) */
    SELECT `account_id` INTO `x_account_id` FROM tmp;

    /* Determine the Channel Permissions */
    UPDATE tmp INNER JOIN (SELECT pa.`account_id`, COUNT(pa.`id`) as `persona_count`, ca.`can_read`, ca.`can_write`
                             FROM `Channel` ch INNER JOIN `ChannelAuthor` ca ON ch.`id` = ca.`channel_id`
                                               INNER JOIN `Persona` pa ON ca.`persona_id` = pa.`id`
                            WHERE ch.`is_deleted` = 'N' and ca.`is_deleted` = 'N' and pa.`is_deleted` = 'N'
                              and ch.`guid` = `in_channel_guid` and pa.`account_id` = `x_account_id`
                            GROUP BY pa.`account_id`, ca.`can_read`, ca.`can_write`
                            ORDER BY `can_write` DESC, `persona_count` DESC LIMIT 1) perms ON tmp.`account_id` = perms.`account_id`
       SET tmp.`persona_count` = perms.`persona_count`,
           tmp.`can_write` = perms.`can_write`,
           tmp.`can_read` = perms.`can_read`;

    /* Create the Token Record */
    INSERT INTO `Tokens` (`guid`, `account_id`, `client_id`)
    SELECT CONCAT(uuid(), '-', LEFT(md5(a.`email`), 4), '-', LEFT(md5(count(p.`id`) + a.`id`), 8)) as `guid`, a.`id` as `account_id`, c.`id` as `client_id`
      FROM `Account` a INNER JOIN `Persona` p ON a.`id` = p.`account_id`
                       INNER JOIN `Client` c
     WHERE p.`is_deleted` = 'N' and c.`is_deleted` = 'N' and a.`is_deleted` = 'N'
       and a.`type` IN ('account.admin', 'account.normal') and c.`guid` = `in_client_guid`
       and a.`id` = `x_account_id`
     GROUP BY a.`email`, a.`id`, c.`id`
     LIMIT 1;
    SELECT LAST_INSERT_ID() INTO `x_token_id`;

    /* Return the Token Information */
    SELECT tt.`id` as `token_id`, tt.`guid` as `token_guid`,
           tt.`account_id`, tmp.`type` as `account_type`, tmp.`display_name`, tmp.`language_code`, tmp.`persona_count`,
           tmp.`can_read`, tmp.`can_write`, tmp.`last_activity`
      FROM `Tokens` tt INNER JOIN tmp ON tt.`account_id` = tmp.`account_id`
     WHERE tt.`is_deleted` = 'N' and tt.`id` = `x_token_id`
     LIMIT 1;

END ;;
DELIMITER ;
