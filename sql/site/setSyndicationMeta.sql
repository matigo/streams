INSERT INTO `SiteMeta` (`site_id`, `key`, `value`)
SELECT si.`id` as `site_id`, tmp.`key`, tmp.`value`
  FROM `Channel` ch INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                    INNER JOIN (SELECT 'site.rss-author' as `key`, '[RSS_AUTHOR]' as `value` UNION ALL
                                SELECT 'site.cover-img' as `key`, '[COVER_IMG]' as `value` UNION ALL
                                SELECT 'site.explicit' as `key`, '[EXPLICIT]' as `value` UNION ALL
                                SELECT 'site.rss-summary' as `key`, '[RSS_SUMMARY]' as `value` UNION ALL
                                SELECT 'site.rss-license' as `key`, '[RSS_LICENSE]' as `value` UNION ALL
                                SELECT 'site.rss-items' as `key`, '[RSS_ITEMS]' as `value` UNION ALL
                                SELECT 'site.rss-category1' as `key`, '[RSS_CAT_1]' as `value` UNION ALL
                                SELECT 'site.rss-category1sub' as `key`, '[RSS_SUB_1]' as `value` UNION ALL
                                SELECT 'site.rss-category2' as `key`, '[RSS_CAT_2]' as `value` UNION ALL
                                SELECT 'site.rss-category2sub' as `key`, '[RSS_SUB_2]' as `value` UNION ALL
                                SELECT 'site.rss-category3' as `key`, '[RSS_CAT_3]' as `value` UNION ALL
                                SELECT 'site.rss-category3sub' as `key`, '[RSS_SUB_3]' as `value`) tmp ON tmp.`key` <> ''
 WHERE si.`is_deleted` = 'N' and ch.`is_deleted` = 'N'
   and ch.`account_id` = [ACCOUNT_ID] and ch.`guid` = '[CHANNEL_GUID]'
    ON DUPLICATE KEY UPDATE `value` = tmp.`value`;
[SQL_SPLITTER]
UPDATE `Channel` ch INNER JOIN `Site` si ON ch.`site_id` = si.`id`
   SET si.`version` = UNIX_TIMESTAMP(Now()),
       si.`updated_at` = Now()
 WHERE si.`is_deleted` = 'N' and ch.`is_deleted` = 'N'
    and ch.`account_id` = [ACCOUNT_ID] and ch.`guid` = '[CHANNEL_GUID]';