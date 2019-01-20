UPDATE `Account`
   SET `type` = 'account.expired',
       `updated_at` = Now()
 WHERE `is_deleted` = 'N' and `type` = 'account.normal' and `id` = [RECORD_ID];