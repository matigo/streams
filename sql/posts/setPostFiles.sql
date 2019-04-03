UPDATE `PostFile`
   SET `is_deleted` = 'Y',
       `updated_at` = Now()
 WHERE `is_deleted` = 'N' and `post_id` = [POST_ID];
[SQL_SPLITTER]
INSERT INTO `PostFile` (`post_id`, `file_id`, `created_at`, `updated_at`)
SELECT po.`id` as `post_id`, fi.`id` as `file_id`, po.`created_at`, po.`updated_at`
  FROM `File` fi INNER JOIN `Post` po ON po.`is_deleted` = 'N' and po.`id` = [POST_ID]
 WHERE LOCATE(CONCAT(fi.`location`, fi.`local_name`), po.`value`) > 0
 ORDER BY po.`id`, fi.`id`
    ON DUPLICATE KEY UPDATE `is_deleted` = 'N',
                            `updated_at` = Now();