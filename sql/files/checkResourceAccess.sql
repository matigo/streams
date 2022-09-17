SELECT fi.`public_name`, fi.`type` as `mimetype`,
       CASE WHEN acct.`id` = me.`id` THEN 'Y' 
            WHEN me.`type` IN ('account.global', 'account.admin') THEN 'Y'
            WHEN LENGTH(IFNULL(pw.`value`, '')) > 0 AND pw.`value` = '[PASSWORD]' THEN 'Y'
            WHEN LENGTH(IFNULL(pw.`value`, '')) <= 0 THEN 'Y'
            ELSE 'N' END as `can_access`
  FROM `Account` acct INNER JOIN `File` fi ON acct.`id` = fi.`account_id`
                 LEFT OUTER JOIN `FileMeta` pw ON fi.`id` = pw.`file_id` AND pw.`is_deleted` = 'N' and pw.`key` = 'access.password'
                 LEFT OUTER JOIN `Account` me ON me.`is_deleted` = 'N' and me.`id` = [ACCOUNT_ID]
 WHERE acct.`is_deleted` = 'N' and fi.`is_deleted` = 'N' and fi.`account_id` = [OWNER]
   and fi.`location` = '/[LOCATION]/' and fi.`local_name` = '[FILE_NAME]'
 ORDER BY fi.`id` DESC LIMIT 1;