INSERT INTO `FileMeta` (`file_id`, `key`, `value`)
SELECT f.`id` as `file_id`, '[KEY]' as `key`, '[VALUE]' as `value`
  FROM `File` f
 WHERE f.`is_deleted` = 'N' and f.`id` = [FILE_ID]
    ON DUPLICATE KEY UPDATE `value` = '[VALUE]',
                            `is_deleted` = 'N',
                            `updated_at` = Now();