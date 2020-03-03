DELIMITER ;;
DROP PROCEDURE IF EXISTS GetRelationsList;;
CREATE PROCEDURE GetRelationsList( IN `in_account_id` int(11), IN `in_persona_guid` varchar(64) )
BEGIN

    /** ********************************************************************** **
     *  Function returns a list of Relations for a Given Account/Persona
     *
     *  Usage: CALL GetRelationsList(1, '07d2f4ec-545f-11e8-99a0-54ee758049c3');
     ** ********************************************************************** **/

    /* Collect the Persona Relationships */
    DROP TEMPORARY TABLE IF EXISTS tmpRelations;
    CREATE TEMPORARY TABLE tmpRelations AS
    SELECT zz.`persona_id`,
           MAX(zz.`follows`) as `follows`, MAX(zz.`is_muted`) as `is_muted`, MIN(zz.`is_blocked`) as `is_blocked`,
           MAX(zz.`is_starred`) as `is_starred`, MAX(zz.`pin_type`) as `pin_type`, MAX(zz.`is_you`) as `is_you`,
           CASE WHEN GREATEST(MAX(zz.`follows`), MAX(zz.`is_muted`), MIN(zz.`is_blocked`), MAX(zz.`is_starred`), MAX(zz.`is_you`)) = 'Y' THEN 'Y'
                WHEN MAX(zz.`pin_type`) NOT IN ('', 'pin.none') THEN 'Y'
                ELSE 'N' END as `is_visible`,
           CAST(NULL AS UNSIGNED) as `site_id`
      FROM (SELECT pr.`related_id` as `persona_id`, pr.`follows`, pr.`is_muted`, pr.`is_blocked`, pr.`is_starred`, pr.`pin_type`, 'N' as `is_you`
              FROM `PersonaRelation` pr INNER JOIN `Persona` pa ON pr.`persona_id` = pa.`id`
             WHERE pr.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and pa.`account_id` = `in_account_id`
             UNION ALL
            SELECT pa.`id` as `persona_id`, 'Y' as `follows`, 'N' as `is_muted`, 'N' as `is_blocked`, 'N' as `is_starred`, '' as `pin_type`, 'Y' as `is_you`
              FROM `Persona` pa
             WHERE pa.`is_deleted` = 'N' and pa.`account_id` = `in_account_id`) zz
     GROUP BY zz.`persona_id`;

     /* Determine the Default Site.id for the Personas */
    UPDATE `tmpRelations` pr INNER JOIN `PersonaMeta` pm ON pr.`persona_id` = pm.`persona_id` AND pm.`key` = 'site.default'
       SET pr.`site_id` = CAST(pm.`value` AS UNSIGNED)
     WHERE pm.`is_deleted` = 'N' and pm.`value` NOT IN ('', '0');

     /* Return the Completed Persona List */
    SELECT pr.`persona_id`, pa.`guid` as `persona_guid`, pa.`name`, pa.`last_name`, pa.`first_name`, pa.`display_name`,
           (SELECT CASE WHEN IFNULL(zpm.`value`, 'N') = 'Y'
                        THEN CONCAT('https://www.gravatar.com/avatar/', MD5(LOWER(CASE WHEN zpa.`email` <> '' THEN zpa.`email` ELSE zacct.`email` END)), '?s=250&r=pg')
                        ELSE (SELECT CONCAT(CASE WHEN zsi.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', zsu.`url`, '/avatars/', zpa.`avatar_img`) as `avatar_url`
                                FROM `Site` zsi INNER JOIN `SiteUrl` zsu ON zsi.`id` = zsu.`site_id`
                               WHERE zsi.`is_deleted` = 'N' and zsi.`is_default` = 'Y' and zsu.`is_active` = 'Y'
                               LIMIT 1) END as `avatar_url`
              FROM `Account` zacct INNER JOIN `Persona` zpa ON zacct.`id` = zpa.`account_id`
                             LEFT OUTER JOIN `PersonaMeta` zpm ON zpa.`id` = zpm.`persona_id` AND zpm.`is_deleted` = 'N' and zpm.`key` = 'avatar.gravatar'
             WHERE zacct.`is_deleted` = 'N' and zpa.`is_deleted` = 'N' and zpa.`id` = pa.`id`) as `avatar_url`,
           CASE WHEN IFNULL(su.`url`, '') <> '' THEN CONCAT(CASE WHEN IFNULL(si.`https`, 'N') = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`) ELSE NULL END as `site_url`,
           si.`name` as `site_name`,
           CASE WHEN IFNULL(su.`url`, '') <> '' THEN CONCAT(CASE WHEN IFNULL(si.`https`, 'N') = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`, '/', pa.`guid`, '/profile') ELSE NULL END as `profile_url`,
           pr.`follows`, pr.`is_muted`, pr.`is_blocked`, pr.`is_starred`,
           CASE WHEN IFNULL(pr.`pin_type`, '') <> '' THEN pr.`pin_type` ELSE 'pin.none' END as `pin_type`, pr.`is_you`
      FROM `tmpRelations` pr INNER JOIN `Persona` pa ON pr.`persona_id` = pa.`id`
                        LEFT OUTER JOIN `SiteUrl` su ON pr.`site_id` = su.`site_id` AND su.`is_active` = 'Y' AND su.`is_deleted` = 'N'
                        LEFT OUTER JOIN `Site` si ON su.`site_id` = si.`id` AND si.`is_deleted` = 'N'
                        LEFT OUTER JOIN (SELECT CONCAT(CASE WHEN zi.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', zu.`url`) as `default_url`
                                           FROM `SiteUrl` zu INNER JOIN `Site` zi ON zu.`site_id` = zi.`id`
                                          WHERE zu.`is_deleted` = 'N' and zi.`is_deleted` = 'N' and zi.`is_default` = 'Y'
                                          ORDER BY zu.`is_active` DESC LIMIT 1) dd ON `default_url` <> ''
     WHERE pa.`is_deleted` = 'N' and pr.`is_visible` = 'Y'
     ORDER BY pr.`is_starred` DESC, pr.`is_blocked`, pr.`is_muted`, pr.`is_you`, pr.`follows` DESC, pa.`name`;
END ;;
DELIMITER ;