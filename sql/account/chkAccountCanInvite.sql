SELECT acct.`id` as `account_id`, 
       DATEDIFF(CURRENT_TIMESTAMP, acct.`created_at`) as `account_days`,
       SUM(txn.`payment_gross`) as `pp_gross`,
       UNIX_TIMESTAMP(MAX(txn.`received_at`)) as `pp_recent_unix`,
       DATEDIFF(CURRENT_TIMESTAMP, MAX(txn.`received_at`)) as `pp_days`,
       tmp.`posts_365`, tmp.`posts_90`,
       CASE WHEN acct.`type` IN ('account.global', 'account.admin') THEN 'Y'
            WHEN acct.`created_at` <= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 10 YEAR) THEN 'Y'
            WHEN DATEDIFF(CURRENT_TIMESTAMP, acct.`created_at`) < 180 THEN 'N'
            WHEN COUNT(inv.`id`) >= 3 THEN 'N'

            WHEN DATEDIFF(CURRENT_TIMESTAMP, MAX(txn.`received_at`)) <= 366 THEN 'Y'
            WHEN SUM(txn.`payment_gross`) > 250 THEN 'Y'

            WHEN IFNULL(tmp.`posts_90`, 0) < 30 THEN 'N'
            ELSE 'N' END as `can_invite`,
       COUNT(inv.`id`) as `invites_created`,
       COUNT(DISTINCT inv.`consumed_by`) as `invites_accepted`
  FROM `Account` acct LEFT OUTER JOIN `PayPalTXN` txn ON acct.`id` = txn.`account_id` AND txn.`is_deleted` = 'N'
                                                     AND txn.`type` = 'subscr_payment' AND txn.`status` = 'Completed'
                                                     AND txn.`payment_gross` > 5
                      LEFT OUTER JOIN `AccountInvite` inv ON acct.`id` = inv.`account_id` AND inv.`is_deleted` = 'N'
                                                     AND DATEDIFF(CURRENT_TIMESTAMP, inv.`created_at`) < 365
                      LEFT OUTER JOIN (SELECT pa.`account_id`,
                                              COUNT(DISTINCT CASE WHEN po.`created_at` >= DATE_SUB(CURRENT_DATE, INTERVAL 365 DAY) THEN po.`id` ELSE NULL END) as `posts_365`,
                                              COUNT(DISTINCT CASE WHEN po.`created_at` >= DATE_SUB(CURRENT_DATE, INTERVAL 90 DAY) THEN po.`id` ELSE NULL END) as `posts_90`
                                         FROM `Post` po INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
                                        WHERE po.`is_deleted` = 'N' and pa.`is_deleted` = 'N' 
                                          and IFNULL(po.`expires_at`, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 5 MINUTE)) >= CURRENT_TIMESTAMP
                                          and po.`created_at` >= DATE_SUB(CURRENT_DATE, INTERVAL 370 DAY)
                                          and pa.`account_id` = [ACCOUNT_ID]
                                        GROUP BY pa.`account_id`) tmp ON acct.`id` = tmp.`account_id`
 WHERE acct.`is_deleted` = 'N' and acct.`id` = [ACCOUNT_ID]
 GROUP BY acct.`id` ORDER BY acct.`id` LIMIT 1;