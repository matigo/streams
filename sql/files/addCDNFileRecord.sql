INSERT INTO `File` (`name`, `localname`, `hash`, `bytes`, `location`, `type`, `created_at`)
SELECT tmp.`name`, tmp.`name`, tmp.`hash`, tmp.`bytes`, tmp.`location`, tmp.`type`, tmp.`created_at`
  FROM (SELECT '[FILE_NAME]' as `name`, '[FILE_HASH]' as `hash`, [FILE_SIZE] as `bytes`,
               '[FILE_PATH]' as `location`, '[FILE_TYPE]' as `type`, '[CREATEDAT]' as `created_at`,
               (SELECT count(f.`id`) FROM `File` f WHERE f.`hash` = '[FILE_HASH]') as `exists`) tmp
 WHERE tmp.`exists` = 0
 LIMIT 1;