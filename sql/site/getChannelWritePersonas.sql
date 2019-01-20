SELECT p.`name`, p.`guid`, p.`avatar_img`
  FROM `Persona` p INNER JOIN `ChannelAuthor` ca ON p.`id` = ca.`persona_id`
                   INNER JOIN `Channel` ch ON ca.`channel_id` = ch.`id`
 WHERE ca.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and p.`is_deleted` = 'N'
   and ca.`can_write` = 'Y' and p.`is_active` = 'Y' 
   and ch.`id` = [CHANNEL_ID] and p.`account_id` = [ACCOUNT_ID]
 ORDER BY p.`name`, p.`guid`;