SELECT acct.`id` as `account_id`,
       LOWER(CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`)) as `site_url`, pa.`name`, pa.`first_name`, pa.`last_name`, pa.`display_name`
  FROM `Account` acct INNER JOIN `Persona` pa ON acct.`id` = pa.`account_id`
                      INNER JOIN `ChannelAuthor` ca ON pa.`id` = ca.`persona_id`
                      INNER JOIN `Channel` ch ON ca.`channel_id` = ch.`id`
                      INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                      INNER JOIN `SiteUrl` su ON si.`id` = su.`site_id`
 WHERE acct.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and ca.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and si.`is_deleted` = 'N' and su.`is_deleted` = 'N'
   and acct.`email` = '[MAIL_ADDY]'
 LIMIT 1;