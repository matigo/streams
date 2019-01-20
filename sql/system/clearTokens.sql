UPDATE `Tokens`
   SET `is_deleted` = 'Y'
 WHERE `is_deleted` = 'N' and `updated_at` < DATE_SUB(Now(), INTERVAL 6 HOUR);