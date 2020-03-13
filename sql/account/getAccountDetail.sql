SELECT tmp.`account_id`, tmp.`email`, tmp.`last_name`, tmp.`first_name`, tmp.`display_name`, tmp.`language_code`, tmp.`timezone`, tmp.`show_geo`, tmp.`show_reminder`,
       tmp.`created_at`,
       tmp.`sub_until`, CASE WHEN IFNULL(tmp.`sub_until`, '2000-01-01 00:00:00') > Now() THEN 'Y' ELSE 'N' END as `sub_active`
  FROM (SELECT acct.`id` as `account_id`, acct.`email`, acct.`last_name`, acct.`first_name`, acct.`display_name`, acct.`language_code`, acct.`timezone`, acct.`created_at`,
               (SELECT MAX(sm.`value`) as `show_geo`
                  FROM `SiteMeta` sm INNER JOIN `Site` si ON sm.`site_id` = si.`id`
                                     INNER JOIN `Channel` ch ON si.`id` = ch.`site_id`
                 WHERE sm.`is_deleted` = 'N' and si.`is_deleted` = 'N' and ch.`is_deleted` = 'N'
                   and sm.`key` = 'show_geo' and ch.`account_id` = acct.`id` LIMIT 1) as `show_geo`,
               (SELECT z.`value` FROM `AccountMeta` z
                 WHERE z.`is_deleted` = 'N' and z.`key` = 'paypal.reminder.mail' and z.`account_id` = acct.`id` LIMIT 1) as `show_reminder`,
               (SELECT DATE_FORMAT(GREATEST((SELECT IFNULL((SELECT DATE_ADD(txn.`received_at`, INTERVAL CASE WHEN txn.`payment_gross` IN (15, 30, 60) THEN 366
                                                                                                         WHEN txn.`payment_gross` IN (5.75, 25) THEN 30
                                                                                                         ELSE 0 END DAY) as `until_at`
                                                          FROM `Tokens` tt INNER JOIN `Account` acct ON tt.`account_id` = acct.`id`
                                                                           INNER JOIN `PayPalTXN` txn ON acct.`id` = txn.`account_id`
                                                         WHERE txn.`is_deleted` = 'N' and txn.`type` = 'subscr_payment' and txn.`status` = 'Completed' and txn.`txn_id` <> ''
                                                           and acct.`is_deleted` = 'N' and tt.`is_deleted` = 'N' and tt.`guid` = '[TOKEN_GUID]' and tt.`id` = [TOKEN_ID]
                                                         ORDER BY txn.`received_at` DESC
                                                         LIMIT 1), '2000-01-01 00:00:00')),
                                        (SELECT DATE_ADD(acct.`created_at`, INTERVAL CASE WHEN acct.`type` = 'account.admin' THEN DATEDIFF(DATE_ADD(acct.`created_at`, INTERVAL 1000 YEAR), acct.`created_at`)
                                                                                          WHEN acct.`type` = 'account.normal' THEN 30
                                                                                          ELSE 0 END DAY) as `until_at`
                                          FROM `Account` acct INNER JOIN `Tokens` tt ON acct.`id` = tt.`account_id`
                                         WHERE acct.`is_deleted` = 'N' and tt.`is_deleted` = 'N'
                                           and tt.`guid` = '[TOKEN_GUID]' and tt.`id` = [TOKEN_ID])), '%Y-%m-%d 23:59:59')) as `sub_until`
          FROM `Account` acct INNER JOIN `Tokens` tt ON acct.`id` = tt.`account_id`
         WHERE tt.`is_deleted` = 'N' and acct.`is_deleted` = 'N' and acct.`type` IN ('account.admin', 'account.normal')
           and tt.`guid` = '[TOKEN_GUID]' and tt.`id` = [TOKEN_ID]
         ORDER BY acct.`id`
         LIMIT 1) tmp
 WHERE tmp.`account_id` > 0;