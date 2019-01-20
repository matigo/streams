UPDATE `File` f
   SET `name` = '[FILE_NAME]',
       `expires_at` = CASE WHEN '[FILE_EXPY]' != '' THEN DATE_FORMAT('[FILE_EXPY]', '%Y-%m-%d 23:59:59') ELSE NULL END,
       `updated_at` = Now()
 WHERE f.`is_deleted` = 'N' and f.`id` = [FILE_ID]
   and (f.`name` != '[FILE_NAME]' OR '[FILE_EXPY]' != '') AND '[FILE_NAME]' != '';