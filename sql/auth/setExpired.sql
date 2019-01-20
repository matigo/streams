UPDATE `Account`
   SET `type` = 'account.expired',
       `updated_at` = Now()
 WHERE `is_deleted` = 'N' and `id` = [ACCOUNT_ID]
 LIMIT 1;