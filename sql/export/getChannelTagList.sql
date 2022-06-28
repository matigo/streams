SELECT pt.`key`, MAX(pt.`value`) as `name`, COUNT(DISTINCT po.`id`) as `posts`
  FROM `Persona` pa INNER JOIN `ChannelAuthor` ca ON pa.`id` = ca.`persona_id`
                    INNER JOIN `Channel` ch ON ca.`channel_id` = ch.`id`
                    INNER JOIN `Post` po ON ch.`id` = po.`channel_id`
                    INNER JOIN `PostTags` pt ON po.`id` = pt.`post_id`
 WHERE pa.`is_deleted` = 'N' and ca.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and po.`is_deleted` = 'N' and pt.`is_deleted` = 'N'
   and IFNULL(po.`expires_at`, Now()) >= Now() and po.`type` IN ([POST_TYPES])
   and ca.`can_write` = 'Y' and ch.`guid` = '[CHANNEL_GUID]'
   and pa.`account_id` = [ACCOUNT_ID]
 GROUP BY pt.`key`
 ORDER BY `posts` DESC;