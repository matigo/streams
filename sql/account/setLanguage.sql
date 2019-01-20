UPDATE `Account` a INNER JOIN `Person` p ON a.`person_id` = p.`id`
   SET p.`language_code` = IFNULL((SELECT z.`code` FROM `Language` z
                                    WHERE z.`is_deleted` = 'N' and z.`code` = LOWER('[LANG_CODE]')), 'en'),
       a.`updated_at` = Now(),
       p.`updated_at` = Now()
 WHERE p.`is_deleted` = 'N' and a.`is_deleted` = 'N' 
   and a.`type` IN ('account.admin', 'account.normal', 'account.student')
   and a.`id` = [ACCOUNT_ID];