SELECT t.`account_id`,
       IFNULL(e.`cosmos_id`, '(Customer II Entry)') as `cosmos_id`,
       IFNULL(e.`school_id`, (SELECT i.`school_id` FROM `Inquiry` i
                               WHERE i.`is_deleted` = 'N' and i.`token_id` = t.`id` LIMIT 1)) as `school_id`,
       DATE_FORMAT(t.`created_at`, '%Y-%m-%dT%H:%i:%sZ') as `created_at`,
       DATE_FORMAT(t.`updated_at`, '%Y-%m-%dT%H:%i:%sZ') as `updated_at`
  FROM `Tokens` t INNER JOIN `Account` a ON t.`account_id` = a.`id`
                  LEFT OUTER JOIN `Employee` e ON a.`person_id` = e.`person_id` AND e.`is_active` = 'Y'
 WHERE a.`is_deleted` = 'N' and a.`id` = t.`account_id`
   and t.`is_deleted` = 'N' and t.`id` = [TOKEN_ID] and t.`guid` = '[TOKEN_GUID]'
   and t.`updated_at` > DATE_SUB(Now(), INTERVAL [LIFESPAN] SECOND)
 LIMIT 1;