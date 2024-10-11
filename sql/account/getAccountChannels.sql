SELECT DISTINCT ch.`id` as `channel_id`, ch.`guid` as `channel_guid`, ch.`name` as `channel_name`, ch.`type` as `channel_type`, ch.`privacy_type`,
       si.`version`,
       ch.`site_id`, si.`guid` as `site_guid`, si.`name` as `site_name`, si.`description` as `site_description`, si.`https`, su.`url`,
       ch.`created_at`, ROUND(UNIX_TIMESTAMP(ch.`created_at`)) as `created_unix`,
       ch.`updated_at`, ROUND(UNIX_TIMESTAMP(ch.`updated_at`)) as `updated_unix`
  FROM `Channel` ch INNER JOIN `ChannelAuthor` ca ON ch.`id` = ca.`channel_id`
                    INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                    INNER JOIN `SiteUrl` su ON si.`id` = su.`site_id`
 WHERE ch.`is_deleted` = 'N' and ca.`is_deleted` = 'N' and si.`is_deleted` = 'N' and su.`is_deleted` = 'N'
   and si.`theme` NOT IN ('admin', 'docs', 'landing', 'locker', 'midori', 'murasaki', 'social')
   and su.`is_active` = 'Y' and ca.`can_write` = 'Y' and ch.`account_id` = [ACCOUNT_ID]
 ORDER BY si.`version` DESC;