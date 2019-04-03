SELECT REPLACE(sm.`key`, 'has_', 'post.') as `type`, REPLACE(sm.`key`, 'has_', 'nav_') as `label`, REPLACE(sm.`key`, 'has_', '/') as `url`, 'N' as `is_default`, 50 as `sort_order`, 'Y' as `is_visible`
  FROM `Channel` ch INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                    INNER JOIN `SiteMeta` sm ON si.`id` = sm.`site_id`
 WHERE sm.`is_deleted` = 'N' and si.`is_deleted` = 'N' and ch.`is_deleted` = 'N'
   and sm.`key` IN ('has_article', 'has_bookmark', 'has_note', 'has_quotation') and si.`id` = [SITE_ID]
 UNION ALL
SELECT 'home' as `type`, 'nav_home' as `label`, '/' as `url`, 'Y' as `is_default`, 0 AS `sort_order`, 'Y' as `is_visible`
 UNION ALL
SELECT 'contact' as `type`, 'nav_contact' as `label`, '/contact' as `url`, 'Y' as `is_default`, 90 as `sort_order`, 'Y' as `is_visible`
 UNION ALL
SELECT 'about' as `type`, 'nav_about' as `label`, '/about' as `url`, 'Y' as `is_default`, 95 as `sort_order`, 'N' as `is_visible`
 ORDER BY `sort_order`, `type`;