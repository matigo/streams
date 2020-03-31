INSERT INTO `UsageSummary` (`event_on`, `site_id`, `requests`, `accounts`, `api_calls`, `web_calls`, `gets`, `posts`, `other`,
                            `200s`, `302s`, `400s`, `401s`, `403s`, `404s`, `422s`,
                            `min_seconds`, `avg_seconds`, `max_seconds`, `sqlops`)
SELECT `event_on`, `site_id`, `requests`, `accounts`, `api_calls`, `web_calls`, `gets`, `posts`, `other`,
                            `200s`, `302s`, `400s`, `401s`, `403s`, `404s`, `422s`,
                            `min_seconds`, `avg_seconds`, `max_seconds`, `sqlops`
  FROM (SELECT us.`event_on`, IFNULL(us.`site_id`, si.`id`) as `site_id`, COUNT(us.`id`) as `requests`, COUNT(DISTINCT tt.`account_id`) as `accounts`,
               SUM(CASE WHEN us.`request_uri` LIKE '/api/%' THEN 1 ELSE 0 END) as `api_calls`,
               SUM(CASE WHEN us.`request_uri` NOT LIKE '/api/%' THEN 1 ELSE 0 END) as `web_calls`,
               SUM(CASE WHEN us.`request_type` = 'GET' THEN 1 ELSE 0 END) as `gets`,
               SUM(CASE WHEN us.`request_type` = 'POST' THEN 1 ELSE 0 END) as `posts`,
               SUM(CASE WHEN us.`request_type` NOT IN ('GET', 'POST') THEN 1 ELSE 0 END) as `other`,
               SUM(CASE WHEN us.`http_code` = 200 THEN 1 ELSE 0 END) as `200s`,
               SUM(CASE WHEN us.`http_code` = 302 THEN 1 ELSE 0 END) as `302s`,
               SUM(CASE WHEN us.`http_code` = 400 THEN 1 ELSE 0 END) as `400s`,
               SUM(CASE WHEN us.`http_code` = 401 THEN 1 ELSE 0 END) as `401s`,
               SUM(CASE WHEN us.`http_code` = 403 THEN 1 ELSE 0 END) as `403s`,
               SUM(CASE WHEN us.`http_code` = 404 THEN 1 ELSE 0 END) as `404s`,
               SUM(CASE WHEN us.`http_code` = 422 THEN 1 ELSE 0 END) as `422s`,
               MIN(us.`seconds`) as `min_seconds`, AVG(us.`seconds`) as `avg_seconds`, MAX(us.`seconds`) as `max_seconds`,
               SUM(us.`sqlops`) as `sqlops`
          FROM `UsageStats` us LEFT OUTER JOIN `Tokens` tt ON us.`token_id` = tt.`id`
                               LEFT OUTER JOIN `Site` si ON si.`is_deleted` = 'N' AND si.`is_default` = 'Y'
         WHERE us.`request_uri` NOT LIKE '/api/reader/%' and us.`request_uri` NOT IN ('/api/posts/fixmeta')
           and us.`event_on` = DATE_FORMAT(DATE_SUB(Now(), INTERVAL 1 DAY), '%Y-%m-%d')
         GROUP BY us.`event_on`, us.`site_id`, si.`id`) tmp
 ORDER BY tmp.`site_id`
    ON DUPLICATE KEY UPDATE `requests` = tmp.`requests`,
                            `accounts` = tmp.`accounts`,
                            `api_calls` = tmp.`api_calls`,
                            `web_calls` = tmp.`web_calls`,
                            `gets` = tmp.`gets`,
                            `posts` = tmp.`posts`,
                            `other` = tmp.`other`,
                            `200s` = tmp.`200s`,
                            `302s` = tmp.`302s`,
                            `400s` = tmp.`400s`,
                            `401s` = tmp.`401s`,
                            `403s` = tmp.`403s`,
                            `404s` = tmp.`404s`,
                            `422s` = tmp.`422s`,
                            `min_seconds` = tmp.`min_seconds`,
                            `avg_seconds` = tmp.`avg_seconds`,
                            `max_seconds` = tmp.`max_seconds`,
                            `sqlops` = tmp.`sqlops`,
                            `updated_at` = Now();