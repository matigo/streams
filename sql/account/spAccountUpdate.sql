DELIMITER ;;
DROP PROCEDURE IF EXISTS AccountUpdate;;
CREATE PROCEDURE AccountUpdate( IN `in_account_id` int(11), IN `in_token_guid` varchar(80), IN `in_token_id` int(11), IN `in_chan_guid` varchar(64),
                                IN `in_dispname` varchar(40), IN `in_firstname` varchar(40), IN `in_lastname` varchar(40),
                                IN `in_email` varchar(256), IN `in_lang` varchar(6), IN `in_timezone` varchar(40),
                                IN `in_subremind` varchar(20), IN `in_showgeo` varchar(20),
                                IN `in_password` varchar(2048),  IN `in_shasalt` varchar(256) )
BEGIN
    DECLARE `x_valid`       char(1);

    /** ********************************************************************** **
     *  Function updates account data and returns an Account-level summary
     *
     *  Usage: CALL AccountUpdate({account_id}, '{token_guid}', {token_id}, '{channel_guid}',
                                  'Matigo', 'Jason', 'Irwin',
                                  'jason@noaddy.com', 'en-us', 'Asia/Tokyo',
                                  'Y', 'Y',
                                  'superSecretPassword', '{salt}' );
     ** ********************************************************************** **/

    /* If the Persona name is too short, Exit */
    IF LENGTH(IFNULL(`in_chan_guid`, '')) <> 36 THEN
        SELECT 'bad_guid' as `error`;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Channel GUID Provided';
    END IF;

    /* If the password appears bad, Exit */
    IF LENGTH(IFNULL(`in_password`, '')) BETWEEN 1 AND 6 THEN
        SELECT 'bad_pass' as `error`;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Password Provided';
    END IF;

    /* Ensure we have some sort of name */
    IF GREATEST(`in_dispname`, `in_firstname`, `in_lastname`) = '' THEN
        SELECT 'bad_name' as `error`;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Name Provided';
    END IF;

    /* Determine if the update has the requisite valid data */
    SELECT 'Y' INTO `x_valid`
      FROM `Tokens` tt INNER JOIN `Account` acct ON tt.`account_id` = acct.`id`
                       INNER JOIN `Channel` ch ON acct.`id` = ch.`account_id`
     WHERE acct.`is_deleted` = 'N' and tt.`is_deleted` = 'N' and ch.`is_deleted` = 'N'
       and acct.`id` = `in_account_id` and ch.`guid` = `in_chan_guid`
       and tt.`id` = `in_token_id` and tt.`guid` = `in_token_guid`
     ORDER BY tt.`id` DESC
     LIMIT 1;

    /* Update the Account If Things Appear Okay */
    IF IFNULL(`x_valid`, 'N') = 'Y' THEN
        UPDATE `Account` acct
           SET `email` = LOWER(LEFT(`in_email`, 256)),
               `password` = CASE WHEN IFNULL(`in_password`, '') <> '' THEN sha2(CONCAT(`in_shasalt`, `in_password`), 512) ELSE `password` END,
               `last_name` = LEFT(`in_lastname`, 40),
               `first_name` = LEFT(`in_firstname`, 40),
               `display_name` = LEFT(`in_dispname`, 40),
               `language_code` = LOWER(`in_lang`),
               `timezone` = CASE WHEN IFNULL(`in_timezone`, '') <> '' THEN LEFT(`in_timezone`, 40) ELSE 'UTC' END,
               `updated_at` = Now()
         WHERE acct.`is_deleted` = 'N' and acct.`id` = `in_account_id`;

        INSERT INTO `AccountMeta` (`account_id`, `key`, `value`)
        SELECT acct.`id` as `account_id`, 'paypal.reminder.mail' as `key`, CASE WHEN `in_subremind` = 'Y' THEN 'Y' ELSE 'N' END as `value`
          FROM `Account` acct
         WHERE acct.`is_deleted` = 'N' and acct.`id` = `in_account_id`
            ON DUPLICATE KEY UPDATE `value` = CASE WHEN `in_subremind` = 'Y' THEN 'Y' ELSE 'N' END,
                                    `updated_at` = Now();

        /* If the Geo locations should be hidden, update every site owned by the account to "No" */
        IF IFNULL(`in_showgeo`, 'Y') = 'N' THEN
            UPDATE `SiteMeta` sm INNER JOIN `Site` si ON sm.`site_id` = si.`id`
                                 INNER JOIN `Channel` ch ON si.`id` = ch.`site_id`
                                 INNER JOIN `Account` acct ON ch.`account_id` = acct.`id`
               SET sm.`value` = 'N',
                   si.`version` = UNIX_TIMESTAMP(Now()),
                   sm.`updated_at` = Now(),
                   si.`updated_at` = Now()
             WHERE acct.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and si.`is_deleted` = 'N' and sm.`is_deleted` = 'N'
               and sm.`key` = 'show_geo' and sm.`value` = 'Y' and acct.`id` = `in_account_id`;
        END IF;
    END IF;

    /* Let's Return an Error Code. Blank is ideal. */
    SELECT CASE WHEN acct.`updated_at` >= DATE_SUB(Now(), INTERVAL 5 SECOND) THEN '' ELSE 'no_dice' END as `error`
      FROM `Account` acct
     WHERE acct.`is_deleted` = 'N' and acct.`id` = `in_account_id`
     LIMIT 1;

END ;;
DELIMITER ;