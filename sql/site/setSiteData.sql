UPDATE `Channel` ch INNER JOIN `Site` si ON ch.`site_id` = si.`id`
   SET ch.`name` = '[SITE_NAME]',
       si.`name` = '[SITE_NAME]',
       si.`description` = '[SITE_DESCR]',
       si.`keywords` = '[SITE_KEYS]',
       si.`updated_at` = Now(),
       ch.`updated_at` = Now()
 WHERE ch.`guid` = '[CHANNEL_GUID]' and si.`account_id` = [ACCOUNT_ID];