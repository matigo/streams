SELECT tmp.`account_id`, tmp.`display_name`, tmp.`payer_id`, tmp.`subscr_id`,
       tmp.`period`, tmp.`start_at`, tmp.`until_at`,
       CONCAT(tmp.`subscr_id`, '.', DATE_FORMAT(tmp.`until_at`, '%Y-%m')) as `meta_key`
  FROM (SELECT txn.`payer_id`, txn.`subscr_id`, txn.`account_id`, acct.`display_name`,
               CASE WHEN txn.`payment_gross` IN (5.75, 25) THEN 'Monthly'
                    ELSE 'Yearly' END as `period`,
               txn.`payment_gross`,
               MIN(txn.`received_at`) as `start_at`,
               CASE WHEN txn.`payment_gross` IN (5.75, 25)
                    THEN DATE_ADD(MIN(txn.`received_at`), INTERVAL COUNT(txn.`id`) MONTH)
                    ELSE DATE_ADD(MIN(txn.`received_at`), INTERVAL COUNT(txn.`id`) YEAR) END as `until_at`,
               (SELECT `received_at` FROM `PayPalTXN` z
                 WHERE z.`is_deleted` = 'N' and z.`type` IN ('subscr_cancel')
                   and z.`payer_id` = txn.`payer_id` and z.`subscr_id` = txn.`subscr_id`
                 ORDER BY z.`received_at` DESC LIMIT 1) as `cancelled_at`
          FROM `PayPalTXN` txn INNER JOIN `Account` acct ON txn.`account_id` = acct.`id`
         WHERE txn.`is_deleted` = 'N' and acct.`is_deleted` = 'N' and acct.`type` NOT IN ('account.expired')
           and txn.`type` = 'subscr_payment' and txn.`status` = 'Completed'
         GROUP BY txn.`payer_id`, txn.`subscr_id`, txn.`account_id`, acct.`display_name`, txn.`payment_gross`) tmp
 WHERE tmp.`cancelled_at` IS NULL
   and tmp.`until_at` BETWEEN DATE_FORMAT(Now(), '%Y-%m-%d 00:00:00') AND DATE_FORMAT(CASE WHEN tmp.`payment_gross` IN (5.75, 25) THEN DATE_ADD(Now(), INTERVAL 1 WEEK)
                                                                                           ELSE DATE_ADD(Now(), INTERVAL 1 MONTH) END, '%Y-%m-%d 23:59:59')
 ORDER BY `until_at`;