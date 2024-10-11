SELECT COUNT(fi.`id`) as `file_count`, COUNT(DISTINCT fi.`hash`) as `file_uniques`,
       SUM(fi.`bytes`) as `file_bytes`,
       CASE WHEN acct.`type` IN ('account.global', 'account.admin') THEN 1000
            WHEN IFNULL(cdn.`is_current`, 'N') = 'Y' THEN 50 
            ELSE 2 END * 1024 * 1024 * 1024 `cdn_limit`
  FROM `Account` acct INNER JOIN `File` fi ON acct.`id` = fi.`account_id`
               LEFT OUTER JOIN (SELECT txn.`account_id`,
                                       CASE WHEN DATE_FORMAT(CURRENT_TIMESTAMP, '%Y-%m-%d 23:59:59') <= DATE_FORMAT(DATE_ADD(txn.`received_at`, INTERVAL 1 YEAR), '%Y-%m-%d 23:59:59')
                                            THEN 'Y' ELSE 'N' END as `is_current`
                                  FROM `PayPalTXN` txn 
                                 WHERE txn.`is_deleted` = 'N' and txn.`account_id` = [ACCOUNT_ID]
                                 ORDER BY txn.`id` DESC LIMIT 1) cdn ON acct.`id` = cdn.`account_id`
 WHERE acct.`is_deleted` = 'N' and fi.`is_deleted` = 'N' 
   and acct.`id` = [ACCOUNT_ID]
 GROUP BY acct.`id`, acct.`type`, cdn.`is_current`
 LIMIT 1;