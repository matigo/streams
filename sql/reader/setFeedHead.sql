INSERT INTO `SyndFeed` (`title`, `description`, `url`, `guid`, `hash`)
SELECT tmp.`title`, tmp.`description`, tmp.`url`, tmp.`guid`, tmp.`hash`
  FROM (SELECT '[FEED_TITLE]' as `title`,
               '[FEED_DESCR]' as `description`,
               LOWER('[FEED_LINK]') as `url`,
               '[FEED_GUID]' as `guid`,
               '[FEED_HASH]' as `hash`,
               (SELECT COUNT(z.`id`) FROM `SyndFeed` z WHERE z.`is_deleted` = 'N' and z.`guid` = '[FEED_GUID]') as `exists`) tmp
 WHERE tmp.`exists` = 0;
[SQL_SPLITTER]
UPDATE `SyndFeed`
   SET `title` = '[FEED_TITLE]',
       `description` = '[FEED_DESCR]',
       `url` = LOWER('[FEED_LINK]'),
       `hash` = '[FEED_HASH]',
       `updated_at` = Now()
 WHERE `is_deleted` = 'N' and `guid` = '[FEED_GUID]' and `hash` <> '[FEED_HASH]';
[SQL_SPLITTER]
UPDATE `SyndFeed`
   SET `polled_at` = Now()
 WHERE `is_deleted` = 'N' and `guid` = '[FEED_GUID]';