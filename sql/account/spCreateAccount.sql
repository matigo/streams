DELIMITER ;;
DROP PROCEDURE IF EXISTS CreateAccount;;
CREATE PROCEDURE CreateAccount( IN `in_name` varchar(40), IN `in_password` varchar(2048), IN `in_email` varchar(256), IN `in_shasalt` varchar(256), IN `in_domain` varchar(100) )
BEGIN
    DECLARE `name_exists`   smallint;
    DECLARE `url_address`   varchar(140);
    DECLARE `is_valid`      char(1);

    DECLARE `x_account_id`  int(11);
    DECLARE `x_persona_id`  int(11);

    /** ********************************************************************** **
     *  Function checks whether a supplied Persona name is available or not,
     *      then creates the Account. A valid authentication token is provided
     *      when complete.
     *
     *  Usage: CALL CreateAccount('jimmy', 'superSecretPassword', 'jimmy@noaddy.com', '{salt}', '{domain}' );
     ** ********************************************************************** **/

    /* If the Persona name is too short, Exit */
    IF LENGTH(IFNULL(`in_name`, '')) < 2 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'The Persona name is too short';
    END IF;

    /* If the password appears bad, Exit */
    IF LENGTH(IFNULL(`in_password`, '')) <= 6 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'The password is far too weak';
    END IF;

    /* First check to see if the Persona Name is available */
    SELECT `exists` INTO `name_exists`
      FROM (SELECT COUNT(DISTINCT pa.`name`) as `exists`
              FROM `Persona` pa INNER JOIN `Account` acct ON pa.`account_id` = acct.`id`
             WHERE acct.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and pa.`name` = `in_name`
             UNION ALL
            SELECT CASE WHEN `in_name` IN ('api', 'cdn', 'www', 'ftp', 'account', 'blog', 'files', 'paypal') THEN 1 ELSE 0 END as `exists`) tmp
     ORDER BY `exists` DESC
     LIMIT 1;

    /* Check to see if the base subdomain is available */
    SELECT CONCAT(tmp.`sub`, `in_domain`) INTO `url_address`
      FROM (SELECT num.`idx`, num.`sub`,
                   CASE WHEN CONCAT(num.`sub`, `in_domain`) IN (SELECT DISTINCT su.`url`
                                                                  FROM `Account` acct INNER JOIN `Site` si ON acct.`id` = si.`account_id`
                                                                                      INNER JOIN `SiteUrl` su ON si.`id` = su.`site_id`
                                                                 WHERE acct.`is_deleted` = 'N' and si.`is_deleted` = 'N' and su.`is_deleted` = 'N') THEN 'N' ELSE 'Y' END as `is_valid`
                      FROM (SELECT (h*10+i) as `idx`, CONCAT(`in_name`, CASE WHEN (h*10+i) > 0 THEN RIGHT(CONCAT('0000', (h*10+i)), 4) ELSE '' END) as `sub`
                                  FROM (SELECT 0 h UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
                                       (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b) num
                     ORDER BY `is_valid` DESC, num.`idx`
                     LIMIT 1) tmp
     WHERE tmp.`is_valid` = 'Y'
     LIMIT 1;

    /* If Everything Checks Out, Mark the Record as Valid and Continue */
    SELECT LEAST(CASE WHEN `name_exists` = 0 THEN 'Y' ELSE 'N' END,
                 CASE WHEN `name_exists` = 0 AND IFNULL(`url_address`, '') <> '' THEN 'Y' ELSE 'N' END) INTO `is_valid`;

    /* Create the Account If Things Appear Okay */
    IF IFNULL(`is_valid`, 'N') = 'Y' THEN
        INSERT INTO `Account` (`email`, `password`, `display_name`, `language_code`, `timezone`, `type`)
        SELECT `in_email` as `email`, sha2(CONCAT(`in_shasalt`, `in_password`), 512) as `password`, `in_name` as `display_name`,
               'en-us' AS `language_code`, 'UTC' as `timezone`, 'account.normal' as `type`;
        SELECT LAST_INSERT_ID() INTO `x_account_id`;

        /* Create the Persona Record */
        INSERT INTO `Persona` (`account_id`, `name`, `last_name`, `first_name`, `display_name`, `avatar_img`, `email`, `is_active`)
        SELECT acct.`id`, `in_name` as `name`, '' as `last_name`, '' as `first_name`, acct.`display_name`, 'default.png' as `avatar_img`, acct.`email`, 'Y' as `is_active`
          FROM `Account` acct
         WHERE acct.`is_deleted` = 'N' and acct.`id` = `x_account_id`;
        SELECT LAST_INSERT_ID() INTO `x_persona_id`;
    END IF;

    /* Let's Try to Return an Account.id and Persona.guid */
    SELECT pa.`account_id`, pa.`guid` as `persona_guid`, LOWER(`url_address`) as `site_url`
      FROM `Account` acct INNER JOIN `Persona` pa ON acct.`id` = pa.`account_id`
     WHERE acct.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and pa.`id` = `x_persona_id`
     LIMIT 1;

END ;;
DELIMITER ;