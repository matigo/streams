INSERT INTO `SiteMeta` (`site_id`, `key`, `value`, `is_deleted`, `updated_at`)
SELECT ch.`site_id`, 'site.password' as `key`,
       CASE WHEN ch.`privacy_type` = 'visibility.password' THEN sha2(CONCAT(UNIX_TIMESTAMP(Now()), '.', '[SITE_PASS]'), 512)
            ELSE '' END as `value`,
       CASE WHEN ch.`privacy_type` = 'visibility.password' THEN 'N' ELSE 'Y' END as `is_deleted`,
       Now() as `updated_at`
  FROM `Channel` ch INNER JOIN `Site` si ON ch.`site_id` = si.`id`
 WHERE ch.`is_deleted` = 'N' and ch.`guid` = '[CHANNEL_GUID]'
   and si.`is_deleted` = 'N' and si.`account_id` = [ACCOUNT_ID]
    ON DUPLICATE KEY UPDATE `value` = CASE WHEN ch.`privacy_type` = 'visibility.password' THEN sha2(CONCAT(UNIX_TIMESTAMP(Now()), '.', '[SITE_PASS]'), 512)
                                      ELSE '' END,
                            `is_deleted` = CASE WHEN ch.`privacy_type` = 'visibility.password' THEN 'N' ELSE 'Y' END,
                            `updated_at` = Now();