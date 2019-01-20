UPDATE `FileURL` u
   SET `is_deleted` = 'Y'
 WHERE u.`is_deleted` = 'N' and u.`id` = [RESOURCE_ID];