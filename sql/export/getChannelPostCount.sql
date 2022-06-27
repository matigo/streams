SELECT COUNT(po.`id`) as `post_count`
  FROM `Persona` pp INNER JOIN `ChannelAuthor` ca ON pp.`id` = ca.`persona_id`
                    INNER JOIN `Channel` ch ON ca.`channel_id` = ch.`id`
                    INNER JOIN `Post` po ON ch.`id` = po.`channel_id`
                    INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
                    INNER JOIN `Account` acct ON pa.`account_id` = acct.`id`
 WHERE acct.`is_deleted` = 'N' and ca.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and po.`is_deleted` = 'N' and pa.`is_deleted` = 'N' and pp.`is_deleted` = 'N'
   and IFNULL(po.`expires_at`, Now()) >= Now() and po.`type` IN ([POST_TYPES])
   and ca.`can_write` = 'Y' and ch.`guid` = '[CHANNEL_GUID]'
   and pp.`account_id` = [ACCOUNT_ID];