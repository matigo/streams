SELECT pa.`id` as `author_id`, pa.`name` as `nickname`, pa.`last_name`, pa.`first_name`, pa.`display_name`, pa.`avatar_img`, pa.`email`,
       acct.`last_name` as `account_lastname`, acct.`first_name` as `account_firstname`, acct.`display_name` as `account_displayname`,
       acct.`email` as `account_email`,
       si.`id` as `site_id`, si.`name` as `site_name`, si.`description` as `site_description`, si.`keywords` as `site_keywords`,
       si.`guid` as `site_guid`, si.`version` as `site_version`, si.`https`,
       (SELECT z.`url` FROM `SiteUrl` z WHERE z.`is_deleted` = 'N' and z.`site_id` = si.`id` ORDER BY z.`is_active` DESC, z.`id` DESC LIMIT 1) as `site_url`
  FROM `Account` acct INNER JOIN `Persona` pa ON acct.`id` = pa.`account_id`
                      INNER JOIN `ChannelAuthor` ca ON pa.`id` = ca.`persona_id`
                      INNER JOIN `Channel` ch ON ca.`channel_id` = ch.`id`
                      INNER JOIN `Site` si ON ch.`site_id` = si.`id`
 WHERE acct.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and ca.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and si.`is_deleted` = 'N'
   and ca.`can_write` = 'Y' and ch.`guid` = '[CHANNEL_GUID]'
   and pa.`account_id` = [ACCOUNT_ID]
 LIMIT 1;