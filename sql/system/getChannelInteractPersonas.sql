SELECT p.`name`, p.`guid`, p.`avatar_img`, ch.`guid` as `channel_guid`, ch.`name` as `channel_name`
  FROM `Persona` p INNER JOIN `ChannelAuthor` ca ON p.`id` = ca.`persona_id`
                   INNER JOIN `Channel` ch ON ca.`channel_id` = ch.`id`
 WHERE ca.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and p.`is_deleted` = 'N'
   and p.`is_active` = 'Y' and ca.`can_write` = 'Y'
   and p.`account_id` = [ACCOUNT_ID]
 ORDER BY `name`, `channel_name`;