SELECT count(s.`id`) as `sites` FROM `Site` s
 WHERE s.`is_deleted` = 'N' and s.`url` = CONCAT('[PREFIX]', '.10centuries.org')