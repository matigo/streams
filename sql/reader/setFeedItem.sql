UPDATE `SyndFeedItemSearch` srch INNER JOIN `SyndFeedItem` i ON srch.`item_id` = i.`id`
   SET srch.`is_deleted` = 'Y',
       srch.`updated_at` = Now()
 WHERE srch.`is_deleted` = 'N' and i.`is_deleted` = 'N' and i.`guid` = '[ITEM_GUID]' and i.`hash` <> '[ITEM_HASH]';
[SQL_SPLITTER]
INSERT INTO `SyndFeedItem` (`id`, `feed_id`, `title`, `url`, `publish_at`, `guid`, `hash`)
SELECT tmp.`item_id`, tmp.`feed_id`, tmp.`title`, tmp.`url`, tmp.`publish_at`, tmp.`guid`, tmp.`hash`
  FROM (SELECT (SELECT z.`id` FROM `SyndFeedItem` z WHERE z.`is_deleted` = 'N' and z.`guid` = '[ITEM_GUID]' ORDER BY z.`id` LIMIT 1) as `item_id`,
               sf.`id` as `feed_id`, '[ITEM_TITLE]' as `title`, '[ITEM_LINK]' as `url`, DATE_FORMAT('[ITEM_DATE]', '%Y-%m-%d %H:%i:%s') as `publish_at`,
               '[ITEM_GUID]' as `guid`, '[ITEM_HASH]' as `hash`,
               IFNULL((SELECT 0 FROM `SyndFeedItem` z WHERE z.`is_deleted` = 'N' and z.`guid` = '[ITEM_GUID]' and z.`hash` = '[ITEM_HASH]' ORDER BY z.`id` LIMIT 1), 1) as `can_write`
          FROM `SyndFeed` sf
         WHERE sf.`is_deleted` = 'N' and sf.`guid` = '[FEED_GUID]') tmp
 WHERE tmp.`can_write` = 1
    ON DUPLICATE KEY UPDATE `title` = tmp.`title`,
                            `url` = tmp.`url`,
                            `publish_at` = tmp.`publish_at`,
                            `hash` = tmp.`hash`,
                            `updated_at` = Now();
[SQL_SPLITTER]
INSERT INTO `SyndFeedItemSearch` (`item_id`, `word`)
SELECT DISTINCT i.`id` as `item_id`, TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(txt.`words`, ',', num.`id`), ',', -1)) as `word`
  FROM `SyndFeedItem` i INNER JOIN (SELECT (h*1000+t*100+u*10+v+1) as `id`
                                      FROM (SELECT 0 h UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
                                           (SELECT 0 t UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b,
                                           (SELECT 0 u UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) c,
                                           (SELECT 0 v UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) d) num ON num.`id` > 0
                        CROSS JOIN (SELECT '[ITEM_SRCH]' as `words`) AS txt
 WHERE i.`is_deleted` = 'N' and i.`guid` = '[ITEM_GUID]'
 ORDER BY `word`
    ON DUPLICATE KEY UPDATE `is_deleted` = 'N';