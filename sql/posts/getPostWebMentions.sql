SELECT pwm.`post_id`, pwm.`url`, pwm.`avatar_url`, pwm.`author`, pwm.`comment`, pwm.`created_at`, pwm.`updated_at`
  FROM `PostWebMention` pwm INNER JOIN `Post` po ON pwm.`post_id` = po.`id`
 WHERE pwm.`is_deleted` = 'N' and po.`is_deleted` = 'N' and po.`id` = [POST_ID]
 ORDER BY pwm.`created_at`;