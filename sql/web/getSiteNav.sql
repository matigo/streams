SELECT REPLACE(sm.`key`, 'has_', 'post.') as `type`, REPLACE(sm.`key`, 'has_', 'nav_') as `label`, REPLACE(sm.`key`, 'has_', '/') as `url`, 'N' as `is_default`, 50 as `sort_order`, 'Y' as `is_visible`
  FROM `Channel` ch INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                    INNER JOIN `SiteMeta` sm ON si.`id` = sm.`site_id`
 WHERE sm.`is_deleted` = 'N' and si.`is_deleted` = 'N' and ch.`is_deleted` = 'N'
   and sm.`key` IN ('has_article', 'has_bookmark', 'has_note', 'has_quotation') and si.`id` = [SITE_ID]
 UNION ALL
SELECT 'home' as `type`, 'nav_home' as `label`, '/' as `url`, 'Y' as `is_default`, 0 AS `sort_order`, 'Y' as `is_visible`
 UNION ALL
SELECT 'contact' as `type`, 'nav_contact' as `label`, '/contact' as `url`, 'Y' as `is_default`, 98 as `sort_order`, 'Y' as `is_visible`
 UNION ALL
SELECT tmp.`type`, tmp.`label`, tmp.`url`, tmp.`is_default`, tmp.`sort_order`, tmp.`is_visible`
  FROM (SELECT 'archive' as `type`, 'nav_archive' as `label`, '/archive' as `url`, 'Y' as `is_default`, 90 as `sort_order`, 'Y' as `is_visible`
          FROM `Post` po INNER JOIN `Channel` ch ON po.`channel_id` = ch.`id`
                         INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                         INNER JOIN `SiteMeta` sm ON si.`id` = sm.`site_id`
         WHERE sm.`is_deleted` = 'N' and si.`is_deleted` = 'N' and ch.`is_deleted` = 'N'
           and po.`type` IN ('post.article', 'post.bookmark', 'post.quotation') and si.`id` = [SITE_ID]
         LIMIT 1) tmp
 UNION ALL
SELECT 'about' as `type`, 'nav_about' as `label`, '/about' as `url`, 'Y' as `is_default`, 99 as `sort_order`, 'N' as `is_visible`
 ORDER BY `sort_order`, `type`;