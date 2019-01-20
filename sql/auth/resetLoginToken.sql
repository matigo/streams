UPDATE `Tokens` SET `is_deleted` = 'Y', `updated_at` = Now()
 WHERE `is_deleted` = 'N' and `user_id` = [USER_ID];