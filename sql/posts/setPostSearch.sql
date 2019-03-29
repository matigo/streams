UPDATE `PostSearch`
   SET `is_deleted` = 'Y',
       `updated_at` = Now()
 WHERE `is_deleted` = 'N' and `post_id` = [POST_ID];
[SQL_SPLITTER]
INSERT INTO `PostSearch` (`post_id`, `word`)
SELECT DISTINCT po.`id` as `post_id`, TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(txt.`words`, ',', num.`id`), ',', -1)) as `word`
  FROM `Post` po INNER JOIN (SELECT (h*1000+t*100+u*10+v+1) as `id`
                               FROM (SELECT 0 h UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
                                    (SELECT 0 t UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b,
                                    (SELECT 0 u UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) c,
                                    (SELECT 0 v UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) d) num ON num.`id` > 0
                 CROSS JOIN (SELECT '[WORD_LIST]' as `words`) AS txt
 WHERE po.`is_deleted` = 'N' and po.`id` = [POST_ID]
 ORDER BY `word`
    ON DUPLICATE KEY UPDATE `is_deleted` = 'N';