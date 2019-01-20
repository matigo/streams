UPDATE `File` f INNER JOIN (SELECT [FILE_ID] as `file_id`,
                                   max(CASE WHEN ar.`scope` IN ('admin', 'level 1', 'level 2', 'manager', 'counsellor', 'uploader')
                                            THEN 'Y' ELSE 'N' END) as `has_scope`
                              FROM `AccountRoles` ar
                             WHERE ar.`is_deleted` = 'N' and IFNULL(ar.`expires_at`, DATE_ADD(Now(), INTERVAL 5 MINUTE)) >= Now()
                               and ar.`account_id` = [ACCOUNT_ID]) tmp ON f.`id` = tmp.`file_id`
   SET f.`is_deleted` = 'Y',
       f.`updated_at` = Now(),
       f.`updated_by` = [ACCOUNT_ID]
 WHERE f.`is_deleted` = 'N' and IFNULL(f.`expires_at`, DATE_ADD(Now(), INTERVAL 5 MINUTE)) > Now()
   and tmp.`has_scope` = 'Y';