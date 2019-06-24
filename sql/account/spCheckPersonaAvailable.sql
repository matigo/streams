DELIMITER ;;
DROP PROCEDURE IF EXISTS CheckPersonaAvailable;;
CREATE PROCEDURE CheckPersonaAvailable( IN `in_name` varchar(40), IN `in_domain` varchar(100) )
BEGIN
    DECLARE `name_exists` smallint;
    DECLARE `url_address` varchar(140);

    /** ********************************************************************** **
     *  Function checks whether a supplied Persona name is available or not.
     *
     *  Usage: CALL CheckPersonaAvailable('api', '.10centuries.org');
     ** ********************************************************************** **/

    /* If the Persona name is too short, Exit */
    IF LENGTH(IFNULL(`in_name`, '')) < 2 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'The Persona Name is Too Short';
    END IF;

    /* If the base domain appears bad, Exit */
    IF LENGTH(IFNULL(`in_domain`, '')) <= 3 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'The Domain Name appears Invalid';
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

    /* If Everything Checks Out, Return the Persona Name and URL */
    SELECT CASE WHEN `name_exists` = 0 THEN `in_name` ELSE NULL END as `persona_name`,
           CASE WHEN `name_exists` = 0 AND IFNULL(`url_address`, '') <> '' THEN LOWER(`url_address`) ELSE NULL END as `domain_url`;
END ;;
DELIMITER ;