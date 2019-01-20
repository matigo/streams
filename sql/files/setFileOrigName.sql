INSERT INTO `FileMeta` (`file_id`, `type`, `value`, `created_at`, `created_by`, `updated_at`, `updated_by`)
SELECT f.`id` as `file_id`, 'file.orginal_name' as `type`, f.`name` as `value`, Now(), [ACCOUNT_ID], Now(), [ACCOUNT_ID]
  FROM `File` f
 WHERE f.`is_deleted` = 'N' and f.`id` = [FILE_ID]
    ON DUPLICATE KEY UPDATE `is_deleted` = 'N';