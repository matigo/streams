SELECT src.`post_id`, COUNT(DISTINCT src.`word`) as `words`, SUM(src.`score`) as `scoring`
  FROM (SELECT CAST(0 AS UNSIGNED) as `post_id`, CAST('' AS VARCHAR(200)) as `word`, CAST(0 AS UNSIGNED) as `score`[CRITERIA]) src
 GROUP BY src.`post_id`
 ORDER BY `scoring` DESC, src.`post_id` DESC
 LIMIT 500;