UPDATE `Channel` c,
       (SELECT `code` FROM `Type`
         WHERE `is_deleted` = 'N' and `code` LIKE 'visibility.%'
           and `code` = '[PRIVACY]'
         UNION ALL
        SELECT `code` FROM `Type`
         WHERE `is_deleted` = 'N' and `code` = 'visibility.public'
         LIMIT 1) tmp
   SET c.`privacy_type` = tmp.`code`,
       c.`updated_at` = Now()
 WHERE c.`is_deleted` = 'N' and c.`type` = 'channel.website'
   and c.`site_id` = [SITE_ID] and c.`guid` = '[CHANNEL_GUID]';