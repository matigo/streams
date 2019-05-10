INSERT INTO `SiteMeta` (`site_id`, `key`, `value`)
SELECT si.`id` as `site_id`, tmp.`key`, CASE WHEN tmp.`value` = 'Y' THEN 'Y' ELSE 'N' END as `value`
  FROM `Channel` ch INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                    INNER JOIN (SELECT 'show_geo' as `key`, '[SHOW_GEO]' as `value` UNION ALL
                                SELECT 'show_note' as `key`, '[SHOW_NOTE]' as `value` UNION ALL
                                SELECT 'show_article' as `key`, '[SHOW_BLOG]' as `value` UNION ALL
                                SELECT 'show_bookmark' as `key`, '[SHOW_BKMK]' as `value` UNION ALL
                                SELECT 'show_location' as `key`, '[SHOW_LOCS]' as `value` UNION ALL
                                SELECT 'show_quotation' as `key`, '[SHOW_QUOT]' as `value`) tmp
 WHERE ch.`guid` = '[CHANNEL_GUID]' and si.`account_id` = [ACCOUNT_ID]
    ON DUPLICATE KEY UPDATE `value` = CASE WHEN tmp.`value` = 'Y' THEN 'Y' ELSE 'N' END,
                            `updated_at` = Now();