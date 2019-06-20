DELIMITER ;;
DROP PROCEDURE IF EXISTS GetPublicProfile;;
CREATE PROCEDURE GetPublicProfile( IN `in_account_id` int(11), IN `in_persona_guid` varchar(36) )
BEGIN

    /** ********************************************************************** **
     *  Function returns the public profile of a Persona based on the GUID
     *      provided.
     *
     *  Usage: CALL GetPublicProfile(1, 'f6c797cc-8a79-5259-bbd4-e88de728b90e');
     ** ********************************************************************** **/

    /* If the Persona GUID Length is Wrong, Exit */
    IF LENGTH(IFNULL(`in_persona_guid`, '')) <> 36 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Persona GUID Supplied';
    END IF;

    /* Collect the Public Profile */
    SELECT tmp.`name`, tmp.`last_name`, tmp.`first_name`, tmp.`display_name`, tmp.`avatar_img`, tmp.`persona_guid`, tmp.`site_url`, tmp.`persona_bio`,
           MAX(tmp.`follows`) as `follows`, MAX(tmp.`is_muted`) as `is_muted`, MAX(tmp.`is_blocked`) as `is_blocked`, MAX(tmp.`is_starred`) as `is_starred`,
           CASE WHEN MAX(CASE WHEN tmp.`pin_type` <> 'pin.none' THEN tmp.`pin_type` ELSE '' END) <> ''
                THEN MAX(CASE WHEN tmp.`pin_type` <> 'pin.none' THEN tmp.`pin_type` ELSE '' END)
                ELSE 'pin.none' END as `pin_type`,
           tmp.`timezone`, tmp.`created_at`, tmp.`days`, tmp.`is_you`
      FROM (SELECT pa.`name`, pa.`last_name`, pa.`first_name`, pa.`display_name`, pa.`avatar_img`, pa.`guid` as `persona_guid`,
                   (SELECT CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`) as `site_url`
                      FROM `SiteUrl` su INNER JOIN `Site` si ON su.`site_id` = si.`id`
                                        INNER JOIN `PersonaMeta` z ON si.`id` = CAST(z.`value` AS UNSIGNED)
                     WHERE su.`is_deleted` = 'N' and si.`is_deleted` = 'N' and z.`is_deleted` = 'N' and su.`is_active` = 'Y'
                       and z.`key` = 'site.default' and z.`value` NOT IN ('', '0') and z.`persona_id` = pa.`id`) as `site_url`,
                   (SELECT z.`value` FROM `PersonaMeta` z WHERE z.`is_deleted` = 'N' and z.`key` = 'persona.bio' and z.`persona_id` = pa.`id`) as `persona_bio`,

                   IFNULL(pr.`follows`, 'N') as `follows`,
                   IFNULL(pr.`is_muted`, 'N') as `is_muted`,
                   IFNULL(pr.`is_blocked`, 'N') as `is_blocked`,
                   IFNULL(pr.`is_starred`, 'N') as `is_starred`,
                   CASE WHEN IFNULL(pr.`pin_type`, '') = '' THEN 'pin.none' ELSE pr.`pin_type` END as `pin_type`,

                   acct.`timezone`, pa.`created_at`, DATEDIFF(DATE_FORMAT(Now(), '%Y-%m-%d 00:00:00'), DATE_FORMAT(pa.`created_at`, '%Y-%m-%d 00:00:00')) as `days`,
                   CASE WHEN acct.`id` = `in_account_id` THEN 'Y' ELSE 'N' END as `is_you`
              FROM `Account` acct INNER JOIN `Persona` pa ON acct.`id` = pa.`account_id`
                                  INNER JOIN `Account` zme ON zme.`is_deleted` = 'N'
                                  INNER JOIN `Persona` zpa ON zme.`id` = zpa.`account_id`
                          LEFT OUTER JOIN `PersonaRelation` pr ON zpa.`id` = pr.`persona_id` AND pa.`id` = pr.`related_id` AND pr.`is_deleted` = 'N'
             WHERE pa.`is_deleted` = 'N' and acct.`is_deleted` = 'N' and pa.`guid` = `in_persona_guid`
               and zpa.`is_deleted` = 'N' and zme.`is_deleted` = 'N' and zme.`id` = `in_account_id`) tmp
     GROUP BY tmp.`name`, tmp.`last_name`, tmp.`first_name`, tmp.`display_name`, tmp.`avatar_img`, tmp.`persona_guid`, tmp.`site_url`, tmp.`persona_bio`,
              tmp.`timezone`, tmp.`created_at`, tmp.`days`, tmp.`is_you`
     LIMIT 1;

END ;;
DELIMITER ;