SELECT pa.`id`, pa.`name`
  FROM `Account` a INNER JOIN `Persona` pa ON a.`id` = pa.`account_id`
 WHERE pa.`is_deleted` = 'N' and a.`is_deleted` = 'N'
 ORDER BY pa.`name`;