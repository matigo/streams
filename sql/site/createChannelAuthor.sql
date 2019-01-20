INSERT INTO `ChannelAuthor` (`channel_id`, `user_id`, `permissions`, `created_at`)
SELECT c.`id`, c.`owner_id`, 'permissions.readwrite', Now()
  FROM `Channel` c
 WHERE c.`is_deleted` = 'N' and c.`id` = [CHAN_ID] and c.`owner_id` = [USER_ID]
 LIMIT 1;