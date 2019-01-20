INSERT INTO `FileURL` (`file_id`, `hash`, `single_use`, `from_ip`, `requested_by`)
SELECT f.`id`, uuid(), 'Y', '[VISIT_IP]', [ACCOUNT_ID]
  FROM `File` f INNER JOIN `PersonMeta` pm ON f.`localname` = pm.`value`
                INNER JOIN `Person` p ON pm.`person_id` = p.`id`
 WHERE pm.`is_deleted` = 'N' and pm.`key` = '[REQUEST_KEY]'
   and p.`is_deleted` = 'N' and p.`guid` = '[PERSON_GUID]'
   and f.`is_deleted` = 'N' and IFNULL(f.`expires_at`, DATE_ADD(Now(), INTERVAL 5 MINUTE)) > Now()
 LIMIT 1;