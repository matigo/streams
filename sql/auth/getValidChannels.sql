SELECT pa.`id` as `persona_id`, pa.`name`, pa.`last_name`, pa.`first_name`, pa.`display_name`, pa.`avatar_img`, pa.`guid`, pa.`is_active`,
       si.`id` as `site_id`, si.`name` as `site_name`, CONCAT(CASE WHEN si.`https` = 'Y' THEN 'https' ELSE 'http' END, '://', su.`url`) as `site_url`,
       ch.`guid` as `channel_guid`, si.`guid` as `site_guid`
  FROM `Persona` pa INNER JOIN `ChannelAuthor` ca ON pa.`id` = ca.`persona_id`
                    INNER JOIN `Channel` ch ON ca.`channel_id` = ch.`id`
                    INNER JOIN `Site` si ON ch.`site_id` = si.`id`
                    INNER JOIN `SiteUrl` su ON si.`id` = su.`site_id`
 WHERE pa.`is_deleted` = 'N' and si.`is_deleted` = 'N' and ch.`is_deleted` = 'N'
   and ca.`is_deleted` = 'N' and ca.`can_write` = 'Y'
   and su.`is_deleted` = 'N' and su.`is_active` = 'Y'
   and pa.`account_id` = [ACCOUNT_ID]
 ORDER BY `persona_id`, `site_id`;