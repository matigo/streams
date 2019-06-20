DELIMITER ;;
DROP PROCEDURE IF EXISTS SetPersonaRelation;;
CREATE PROCEDURE SetPersonaRelation( IN `in_account_id` int(11), IN `in_persona_guid` varchar(64), IN `in_related_guid` varchar(64),
                                     IN `in_follows` char(1), IN `in_muted` char(1), IN `in_blocked` char(1), IN `in_starred` char(1), IN `in_pin` varchar(64) )
BEGIN

    /** ********************************************************************** **
     *  Function sets a relation record between two Personas
     *
     *  Usage: CALL SetPersonaRelation(1, '07d2f4ec-545f-11e8-99a0-54ee758049c3', '28234e57-67bf-11e8-99c0-54ee758049c3', 'N', 'N', 'N', 'N', '');
     ** ********************************************************************** **/

    /* If the Persona GUID Length is Wrong, Exit */
    IF LENGTH(IFNULL(`in_persona_guid`, '')) <> 36 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Persona GUID Supplied';
    END IF;

    /* If the Related Persona GUID Length is Wrong, Exit */
    IF LENGTH(IFNULL(`in_related_guid`, '')) <> 36 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Related Persona GUID Provided';
    END IF;

    /* If the Follows value is invalid, blank it */
    IF IFNULL(`in_follows`, '') NOT IN ('', 'N', 'Y') THEN
        SET `in_follows` = '';
    END IF;

    /* If the Muted value is invalid, blank it */
    IF IFNULL(`in_muted`, '') NOT IN ('', 'N', 'Y') THEN
        SET `in_muted` = '';
    END IF;

    /* If the Blocked value is invalid, blank it */
    IF IFNULL(`in_blocked`, '') NOT IN ('', 'N', 'Y') THEN
        SET `in_blocked` = '';
    END IF;

    /* If the Starred value is invalid, blank it */
    IF IFNULL(`in_starred`, '') NOT IN ('', 'N', 'Y') THEN
        SET `in_starred` = '';
    END IF;

    /* Ensure the Pin Type is Valid */
    IF (SELECT COUNT(`code`) FROM `Type` z WHERE z.`is_deleted` = 'N' and z.`code` LIKE 'pin.%' and z.`code` = `in_pin`) <= 0 THEN
        SET `in_pin` = 'pin.none';
    END IF;

    /* If we're Blocking an account, unfollow and mute them as well */
    IF IFNULL(`in_blocked`, '') = 'Y' THEN
        SET `in_pin` = 'pin.none';
        SET `in_starred` = 'N';
        SET `in_follows` = 'N';
        SET `in_muted` = 'Y';
    END IF;

    /* Create or Update the PersonaRelation Record */
    INSERT INTO `PersonaRelation` (`persona_id`, `related_id`, `pin_type`, `follows`, `is_muted`, `is_blocked`, `is_starred`)
    SELECT pa.`id` as `persona_id`, ra.`id` as `related_id`, `in_pin`,
           CASE WHEN `in_follows` IN ('N','Y') THEN `in_follows` ELSE IFNULL(pr.`follows`, 'N') END as `follows`,
           CASE WHEN `in_muted`   IN ('N','Y') THEN `in_muted`   ELSE IFNULL(pr.`is_muted`, 'N') END as `is_muted`,
           CASE WHEN `in_blocked` IN ('N','Y') THEN `in_blocked` ELSE IFNULL(pr.`is_blocked`, 'N') END as `is_blocked`,
           CASE WHEN `in_starred` IN ('N','Y') THEN `in_starred` ELSE IFNULL(pr.`is_starred`, 'N') END as `is_starred`
      FROM `Account` acct INNER JOIN `Persona` pa ON acct.`id` = pa.`account_id`
                     LEFT OUTER JOIN `Persona` ra ON ra.`is_deleted` = 'N' and ra.`guid` = `in_related_guid`
                     LEFT OUTER JOIN `PersonaRelation` pr ON pa.`id` = pr.`persona_id` AND ra.`id` = pr.`related_id` AND pr.`is_deleted` = 'N'
     WHERE acct.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and acct.`id` = `in_account_id` and pa.`guid` = `in_persona_guid`
        ON DUPLICATE KEY UPDATE `follows` = CASE WHEN `in_follows` IN ('N','Y') THEN `in_follows` ELSE IFNULL(pr.`follows`, 'N') END,
                                `is_muted` = CASE WHEN `in_muted` IN ('N','Y') THEN `in_muted` ELSE IFNULL(pr.`is_muted`, 'N') END,
                                `is_blocked` = CASE WHEN `in_blocked` IN ('N','Y') THEN `in_blocked` ELSE IFNULL(pr.`is_blocked`, 'N') END,
                                `is_starred` = CASE WHEN `in_starred` IN ('N','Y') THEN `in_starred` ELSE IFNULL(pr.`is_starred`, 'N') END,
                                `pin_type` = `in_pin`;

    /* Return the PersonaRelation Record for Confirmation */
    SELECT pa.`name`, pa.`last_name`, pa.`first_name`, pa.`display_name`, pa.`avatar_img`, pa.`guid`,
           ra.`name` as `related_name`, ra.`last_name` as `related_last_name`, ra.`first_name` as `related_first_name`, ra.`display_name` as `related_display_name`,
           ra.`avatar_img` as `related_avatar_img`, ra.`guid` as `related_guid`,
           IFNULL(pr.`follows`, 'N') as `follows`, IFNULL(pr.`is_muted`, 'N') as `is_muted`, IFNULL(pr.`is_blocked`, 'N') as `is_blocked`,
           IFNULL(pr.`is_starred`, 'N') as `is_starred`, IFNULL(pr.`pin_type`, 'pin.none') as `pin_type`,
           IFNULL(pr.`created_at`, pa.`created_at`) as `created_at`, IFNULL(pr.`updated_at`, pa.`updated_at`) as `updated_at`
      FROM `Account` acct INNER JOIN `Persona` pa ON acct.`id` = pa.`account_id`
                     LEFT OUTER JOIN `Persona` ra ON ra.`is_deleted` = 'N' and ra.`guid` = `in_related_guid`
                     LEFT OUTER JOIN `PersonaRelation` pr ON pa.`id` = pr.`persona_id` AND ra.`id` = pr.`related_id` AND pr.`is_deleted` = 'N'
     WHERE acct.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and acct.`id` = `in_account_id` and pa.`guid` = `in_persona_guid`
     LIMIT 1;
END;;
DELIMITER ;