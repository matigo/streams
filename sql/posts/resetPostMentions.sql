UPDATE `PostMention`
   SET `is_deleted` = 'Y',
       `updated_at` = Now()
 WHERE `is_deleted` = 'N' and `post_id` = [POST_ID];