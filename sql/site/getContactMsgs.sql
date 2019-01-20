SELECT z.`site_id`, CASE WHEN s.`custom_url` = '' THEN s.`url` ELSE s.`custom_url` END as `site_url`, s.`name` as `site_name`,
       z.`id` as `msg_id`, z.`type` as `msg_type`, z.`is_mailed`, z.`visitor_ip` as `guest_ip`, z.`created_at`,
       z.`name` as `guest_name`, z.`mailaddr` as `guest_mail`, z.`site_url` as `guest_site`, z.`message` as `guest_note`
  FROM `Channel` c, `ChannelAuthor` a, `Site` s, `SiteContact` z
 WHERE a.`is_deleted` = 'N' and a.`channel_id` = c.`id`
   and c.`is_deleted` = 'N' and c.`site_id` = z.`site_id`
   and z.`is_deleted` = 'N' and z.`site_id` = s.`id`
   and s.`is_deleted` = 'N' and c.`type` = 'channel.website'
   and a.`user_id` = [ACCOUNT_ID]
 ORDER BY z.`created_at` DESC
 LIMIT 0, 50;