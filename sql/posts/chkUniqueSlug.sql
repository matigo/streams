SELECT COUNT(p.`id`) as `post_count`
  FROM `Channel` ch INNER JOIN `Post` p ON ch.`id` = p.`channel_id`
 WHERE ch.`is_deleted` = 'N' and p.`is_deleted` = 'N'
   and ch.`guid` = '[CHANNEL_GUID]' and p.`slug` = '[POST_SLUG]'
   and p.`guid` <> '[POST_GUID]'
 ORDER BY p.`id` DESC;