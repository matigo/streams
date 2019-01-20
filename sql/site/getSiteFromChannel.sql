SELECT c.`site_id` FROM `Channel` c
 WHERE c.`is_deleted` = 'N' and c.`id` = [CHANNEL_ID]
 LIMIT 1;