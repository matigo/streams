DELIMITER ;;
DROP PROCEDURE IF EXISTS GetSiteNav;;
CREATE PROCEDURE GetSiteNav( IN `in_site_id` int(11) )
BEGIN

   /** ********************************************************************** **
     *  Function builds the Navigation Menu for a Given Site
     *
     *  Usage: CALL GetSiteNav(1);
     ** ********************************************************************** **/

    DECLARE EXIT HANDLER FOR SQLEXCEPTION, SQLWARNING
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    /* If the Persona GUID is bad, Exit */
    IF IFNULL(`in_site_id`, 0) <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid Site ID Provided';
    END IF;

    /* Collect the Navigation Menu Items for the Site */
    SELECT REPLACE(sm.`key`, 'has_', 'post.') as `type`, '' as `title`, REPLACE(sm.`key`, 'has_', 'nav_') as `label`, REPLACE(sm.`key`, 'has_', '/') as `url`, 'N' as `is_default`, 50 as `sort_order`, 'Y' as `is_visible`
      FROM `Channel` ch INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                        INNER JOIN `SiteMeta` sm ON si.`id` = sm.`site_id`
     WHERE sm.`is_deleted` = 'N' and si.`is_deleted` = 'N' and ch.`is_deleted` = 'N'
       and sm.`key` IN ('has_article', 'has_bookmark', 'has_note', 'has_quotation') and si.`id` = `in_site_id`
     UNION ALL
    SELECT 'home' as `type`, '' as `title`, 'nav_home' as `label`, '/' as `url`, 'Y' as `is_default`, 0 AS `sort_order`, 'Y' as `is_visible`
     UNION ALL
    SELECT 'contact' as `type`, '' as `title`, 'nav_contact' as `label`, '/contact' as `url`, 'Y' as `is_default`, 98 as `sort_order`, 'Y' as `is_visible`
     UNION ALL
    SELECT tmp.`type`, '' as `title`, tmp.`label`, tmp.`url`, tmp.`is_default`, tmp.`sort_order`, tmp.`is_visible`
      FROM (SELECT 'archive' as `type`, 'nav_archive' as `label`, '/archive' as `url`, 'Y' as `is_default`, 90 as `sort_order`, 'Y' as `is_visible`
              FROM `Post` po INNER JOIN `Channel` ch ON po.`channel_id` = ch.`id`
                             INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                             INNER JOIN `SiteMeta` sm ON si.`id` = sm.`site_id`
             WHERE sm.`is_deleted` = 'N' and si.`is_deleted` = 'N' and ch.`is_deleted` = 'N'
               and po.`type` IN ('post.article', 'post.bookmark', 'post.quotation') and si.`id` = `in_site_id`
             LIMIT 1) tmp
     UNION ALL
    SELECT po.`type`, po.`title`, '' as `label`, po.`canonical_url` as `url`, 'N' as `is_default`, 80 as `sort_order`, 'Y' as `is_visible`
      FROM `Channel` ch INNER JOIN `Post` po ON ch.`id` = po.`channel_id`
     WHERE po.`is_deleted` = 'N' and po.`privacy_type` = 'visibility.public' and po.`type` = 'post.page'
       and ch.`is_deleted` = 'N' and ch.`site_id` = `in_site_id`
     ORDER BY `sort_order`, `type`, `title`;
END ;;
DELIMITER ;