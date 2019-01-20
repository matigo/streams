INSERT INTO `Tokens` (`guid`, `account_id`, `is_deleted`, `created_at`, `updated_at`)
SELECT uuid() as `guid`, tmp.`account_id`, 'Y' as `is_deleted`,
       DATE_FORMAT(DATE_SUB(Now(), INTERVAL 1 DAY), '%Y-%m-%d 00:00:00') as `created_at`,
       DATE_FORMAT(DATE_SUB(Now(), INTERVAL 1 DAY), '%Y-%m-%d 00:00:00') as `updated_at`
  FROM (SELECT t.`account_id`, max(t.`updated_at`) as `last_action_at` FROM `Tokens` t
         WHERE t.`account_id` = [ACCOUNT_ID]
         UNION ALL
        SELECT [ACCOUNT_ID] as `account_id`, DATE_FORMAT('2000-01-01', '%Y-%m-%d %H:%i:00') as `last_action_at`
         ORDER BY `last_action_at` DESC
         LIMIT 1) tmp
 WHERE 1 = (SELECT count(`account_id`) as `records` FROM `AccountRoles` z
             WHERE z.`is_deleted` = 'N' and IFNULL(z.`expires_at`, DATE_ADD(Now(), INTERVAL 1 SECOND)) > Now()
               and z.`scope` = 'admin' and z.`account_id` = [MY_ACCOUNT])
 LIMIT 1;