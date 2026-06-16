UPDATE `AccountPushTokens`
   SET `is_deleted` = 'Y',
       `updated_at` = CURRENT_TIMESTAMP
 WHERE `is_deleted` = 'N' and `device_token` = '[TOKEN]';