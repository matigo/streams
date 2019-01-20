SELECT c.`site_id` FROM `Channel` c
 WHERE c.`is_deleted` = 'N' and c.`owner_id` = [ACCOUNT_ID]
   and c.`guid` = '[CHANNEL_GUID]'
 ORDER BY c.`id`
 LIMIT 1