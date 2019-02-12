SELECT po.`type`, REPLACE(po.`type`, 'post.', 'nav_') as `label`, REPLACE(po.`type`, 'post.', '/') as `url`, 'N' as `is_default`, COUNT(po.`id`) as `items`, 50 as `sort_order`, 'Y' as `is_visible`
  FROM `Post` po INNER JOIN `Channel` ch ON po.`channel_id` = ch.`id`
                 INNER JOIN `Site` si ON ch.`site_id` = si.`id`
 WHERE si.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and po.`is_deleted` = 'N'
   and po.`type` IN ('post.article', 'post.bookmark', 'post.note', 'post.quotation') and si.`id` = [SITE_ID]
 GROUP BY po.`type`
 UNION ALL
SELECT 'home' as `type`, 'nav_home' as `label`, '/' as `url`, 'Y' as `is_default`, 0 as `items`, 0 AS `sort_order`, 'Y' as `is_visible`
 UNION ALL
SELECT 'contact' as `type`, 'nav_contact' as `label`, '/contact' as `url`, 'Y' as `is_default`, 0 as `items`, 90 as `sort_order`, 'Y' as `is_visible`
 UNION ALL
SELECT 'about' as `type`, 'nav_about' as `label`, '/about' as `url`, 'Y' as `is_default`, 0 as `items`, 95 as `sort_order`, 'Y' as `is_visible`
 ORDER BY `sort_order`, `type`;