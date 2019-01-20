UPDATE `Account` a INNER JOIN `Employee` e ON a.`person_id` = e.`person_id`
                   INNER JOIN (SELECT max(y.`level`) as `my_level` FROM `AccountRoles` z INNER JOIN `Roles` y ON z.`scope` = y.`scope`
                                WHERE y.`is_deleted` = 'N' and z.`is_deleted` = 'N' and z.`account_id` = [ACCOUNT_ID]) tmp
   SET a.`password` = sha2(CONCAT('[SHA_SALT]', '[USERPASS]'), 512),
       a.`updated_at` = Now(),
       e.`updated_at` = Now(),
       e.`updated_by` = [ACCOUNT_ID]
 WHERE a.`is_deleted` = 'N' and a.`person_id` = e.`person_id` and e.`guid` = '[EMPLOYEE_GUID]'
   and tmp.`my_level` >= (SELECT max(y.`level`) as `my_level` FROM `AccountRoles` z INNER JOIN `Roles` y ON z.`scope` = y.`scope`
                           WHERE y.`is_deleted` = 'N' and z.`is_deleted` = 'N' and z.`account_id` = a.`id`);
[SQL_SPLITTER]
INSERT INTO `AccountPass` (`account_id`, `password`, `created_at`, `created_by`, `updated_by`)
SELECT a.`id` as `account_id`, a.`password`, Now() as `created_at`, 1 as `created_by`, 1 as `updated_by`
  FROM `Account` a INNER JOIN `Employee` e ON a.`person_id` = e.`person_id`
 WHERE e.`is_deleted` = 'N' and e.`guid` = '[EMPLOYEE_GUID]';
[SQL_SPLITTER]
INSERT INTO `AccountMeta` (`account_id`, `type`, `value`)
SELECT a.`id` as `account_id`, 'system.password.reqchange' as `type`, 'Y' as `value`
  FROM `Employee` e INNER JOIN `Account` a ON e.`person_id` = a.`person_id`
 WHERE e.`is_deleted` = 'N' and e.`guid` = '[EMPLOYEE_GUID]'
    ON DUPLICATE KEY UPDATE `value` = 'Y',
                            `is_deleted` = 'N',
                            `updated_at` = Now();