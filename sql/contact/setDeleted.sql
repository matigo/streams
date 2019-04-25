UPDATE `SiteContact` sc INNER JOIN `Site` si ON sc.`site_id` = si.`id`
                        INNER JOIN `Account` acct ON si.`account_id` = acct.`id`
   SET sc.`is_deleted` = 'Y',
       sc.`updated_at` = Now()
 WHERE sc.`is_deleted` = 'N' and si.`is_deleted` = 'N' and acct.`is_deleted` = 'N'
   and sc.`guid` = '[MSG_GUID]' and si.`id` = [SITE_ID] and acct.`id` = [ACCOUNT_ID];