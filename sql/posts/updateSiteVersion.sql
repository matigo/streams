UPDATE `Channel` ch INNER JOIN `Site` si ON ch.`site_id` = si.`id`
   SET si.`version` = UNIX_TIMESTAMP(Now())
 WHERE si.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and ch.`guid` = '[CHANNEL_GUID]';