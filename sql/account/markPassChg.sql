UPDATE `Account` a INNER JOIN `AccountMeta` am ON a.`id` = am.`account_id`
   SET am.`value` = 'N',
       am.`is_deleted` = 'N',
       am.`updated_at` = Now()
 WHERE am.`is_deleted` = 'N' and am.`type` = 'system.password.reqchange'
   and a.`id` = [ACCOUNT_ID];