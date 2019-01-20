SELECT `req_url`, sum(`hits`) as `hits`
  FROM (SELECT SUBSTRING_INDEX(ss.`request_uri`, '?', 1) as `req_url`, count(ss.`id`) as `hits`
          FROM `Site` s, `SiteStats` ss
         WHERE ss.`is_deleted` = 'N' and ss.`site_id` = s.`id`
           and ss.`created_at` >= DATE_FORMAT(DATE_SUB(DATE_SUB(Now(), INTERVAL [TZ_OFFSET] HOUR), INTERVAL 15 DAY), '%Y-%m-%d')
           and s.`is_deleted` = 'N' and s.`user_id` = [USER_ID]
           and ss.`request_uri` NOT IN ('/rss/', '/podcast.xml', '/feed/', '/feed', '/rss/feed', '/rss/feeds', '/rss/rss', '/rss/rss.html', '/rss/rss.htm', '/rss')
           and ss.`request_uri` NOT LIKE '%.swf%'
           and ss.`request_uri` NOT LIKE '%.png%'
           and ss.`request_uri` NOT LIKE '%.gif%'
           and ss.`request_uri` NOT LIKE '%.jpg%'
           and ss.`request_uri` NOT LIKE '%.php%'
           and ss.`request_uri` NOT LIKE '%.css%'
           and ss.`request_uri` NOT LIKE '%.txt%'
           and ss.`request_uri` NOT LIKE '%/feed/%'
           and ss.`request_uri` NOT LIKE '%/rss.%'
           and ss.`request_uri` NOT LIKE '%/assets%'
         GROUP BY ss.`request_uri` ORDER BY `hits` DESC LIMIT 100) tmp
 GROUP BY `req_url`
 ORDER BY `hits` DESC
 LIMIT 50;

