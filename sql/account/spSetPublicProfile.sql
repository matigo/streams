DELIMITER ;;
DROP PROCEDURE IF EXISTS SetPublicProfile;;
CREATE PROCEDURE SetPublicProfile( IN `in_account_id` int(11), IN `in_persona_guid` varchar(64),
                                   IN `in_bio` varchar(2048), IN `in_avatar_type` varchar(64), IN `in_avatar_file` varchar(128) )
BEGIN

    /** ********************************************************************** **
     *  Function sets the Public Profile for an Account/Persona combination and
     *      returns a quick summary.
     *
     *  Usage: CALL SetPublicProfile(1, '0737c327-913d-c0d2-1229-1154f2a3caa9', 'Here we have a simple little bio or whatever.', 'own', 'jason_fox_box.jpg');
     ** ********************************************************************** **/

    /* If the Persona GUID Length is Wrong, Exit */
    IF LENGTH(IFNULL(`in_persona_guid`, '')) <> 36 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Persona GUID Supplied';
    END IF;

    /* Set the Avatar Record First (If Applicable) */
    IF LENGTH(IFNULL(`in_avatar_file`, '')) BETWEEN 5 AND 128 THEN
        UPDATE `Account` acct INNER JOIN `Persona` pa ON acct.`id` = pa.`account_id`
           SET `avatar_img` = IFNULL(`in_avatar_file`, 'default.png')
         WHERE acct.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and pa.`account_id` = `in_account_id`
           and pa.`guid` = `in_persona_guid`;
    END IF;

    /* Set the Meta Records */
    INSERT INTO `PersonaMeta` (`persona_id`, `key`, `value`)
    SELECT tmp.`persona_id`, tmp.`key`, tmp.`value`
      FROM (SELECT pa.`id` as `persona_id`, 'persona.bio' as `key`, LEFT(`in_bio`, 2048) as `value`
              FROM `Account` acct INNER JOIN `Persona` pa ON acct.`id` = pa.`account_id`
             WHERE acct.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and pa.`account_id` = `in_account_id`
               and pa.`guid` = `in_persona_guid`
             UNION ALL
            SELECT pa.`id` as `persona_id`, 'avatar.gravatar' as `key`, CASE WHEN LOWER(`in_avatar_type`) = 'gravatar' THEN 'Y' ELSE 'N' END as `value`
              FROM `Account` acct INNER JOIN `Persona` pa ON acct.`id` = pa.`account_id`
             WHERE acct.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and pa.`account_id` = `in_account_id`
               and pa.`guid` = `in_persona_guid`) tmp
        ON DUPLICATE KEY UPDATE `value` = tmp.`value`;

    /* Return Some Basic Counts */
    SELECT pa.`id` as `persona_id`, COUNT(pm.`key`) as `meta_keys`
      FROM `Account` acct INNER JOIN `Persona` pa ON acct.`id` = pa.`account_id`
                     LEFT OUTER JOIN `PersonaMeta` pm ON pa.`id` = pm.`persona_id` AND pm.`is_deleted` = 'N'
     WHERE acct.`is_deleted` = 'N' and pa.`is_deleted` = 'N'
       and acct.`id` = `in_account_id` and pa.`guid` = `in_persona_guid`
     GROUP BY pa.`id`;

END;;
DELIMITER ;