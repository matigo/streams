SELECT tmp.`events_at` as `visit_on`, sum(tmp.`hits`) as `hits`, sum(tmp.`uniques`) as `uniques`, sum(tmp.`downloads`) as `downloads`
  FROM (SELECT DATE_FORMAT(DATE_SUB(css.`visit_at`, INTERVAL [TZ_OFFSET] HOUR), '%Y-%m-%d') as `events_at`, css.`hits`, css.`uniques`, css.`downloads`
          FROM `CacheSiteStats` css, `Site` s
         WHERE s.`is_deleted` = 'N' and s.`id` = css.`site_id` and css.`is_deleted` = 'N'
           and css.`visit_at` >= DATE_FORMAT(DATE_SUB(DATE_SUB(Now(), INTERVAL [TZ_OFFSET] HOUR), INTERVAL 15 DAY), '%Y-%m-%d')
           and s.`user_id` = [USER_ID]
         UNION ALL
        SELECT DATE_FORMAT(DATE_SUB(DATE_SUB(Now(), INTERVAL [TZ_OFFSET] HOUR), INTERVAL  0 DAY), '%Y-%m-%d'), 0, 0, 0
         UNION ALL
        SELECT DATE_FORMAT(DATE_SUB(DATE_SUB(Now(), INTERVAL [TZ_OFFSET] HOUR), INTERVAL  1 DAY), '%Y-%m-%d'), 0, 0, 0
         UNION ALL
        SELECT DATE_FORMAT(DATE_SUB(DATE_SUB(Now(), INTERVAL [TZ_OFFSET] HOUR), INTERVAL  2 DAY), '%Y-%m-%d'), 0, 0, 0
         UNION ALL
        SELECT DATE_FORMAT(DATE_SUB(DATE_SUB(Now(), INTERVAL [TZ_OFFSET] HOUR), INTERVAL  3 DAY), '%Y-%m-%d'), 0, 0, 0
         UNION ALL
        SELECT DATE_FORMAT(DATE_SUB(DATE_SUB(Now(), INTERVAL [TZ_OFFSET] HOUR), INTERVAL  4 DAY), '%Y-%m-%d'), 0, 0, 0
         UNION ALL
        SELECT DATE_FORMAT(DATE_SUB(DATE_SUB(Now(), INTERVAL [TZ_OFFSET] HOUR), INTERVAL  5 DAY), '%Y-%m-%d'), 0, 0, 0
         UNION ALL
        SELECT DATE_FORMAT(DATE_SUB(DATE_SUB(Now(), INTERVAL [TZ_OFFSET] HOUR), INTERVAL  6 DAY), '%Y-%m-%d'), 0, 0, 0
         UNION ALL
        SELECT DATE_FORMAT(DATE_SUB(DATE_SUB(Now(), INTERVAL [TZ_OFFSET] HOUR), INTERVAL  7 DAY), '%Y-%m-%d'), 0, 0, 0
         UNION ALL
        SELECT DATE_FORMAT(DATE_SUB(DATE_SUB(Now(), INTERVAL [TZ_OFFSET] HOUR), INTERVAL  8 DAY), '%Y-%m-%d'), 0, 0, 0
         UNION ALL
        SELECT DATE_FORMAT(DATE_SUB(DATE_SUB(Now(), INTERVAL [TZ_OFFSET] HOUR), INTERVAL  9 DAY), '%Y-%m-%d'), 0, 0, 0
         UNION ALL
        SELECT DATE_FORMAT(DATE_SUB(DATE_SUB(Now(), INTERVAL [TZ_OFFSET] HOUR), INTERVAL 10 DAY), '%Y-%m-%d'), 0, 0, 0
         UNION ALL
        SELECT DATE_FORMAT(DATE_SUB(DATE_SUB(Now(), INTERVAL [TZ_OFFSET] HOUR), INTERVAL 11 DAY), '%Y-%m-%d'), 0, 0, 0
         UNION ALL
        SELECT DATE_FORMAT(DATE_SUB(DATE_SUB(Now(), INTERVAL [TZ_OFFSET] HOUR), INTERVAL 12 DAY), '%Y-%m-%d'), 0, 0, 0
         UNION ALL
        SELECT DATE_FORMAT(DATE_SUB(DATE_SUB(Now(), INTERVAL [TZ_OFFSET] HOUR), INTERVAL 13 DAY), '%Y-%m-%d'), 0, 0, 0
         UNION ALL
        SELECT DATE_FORMAT(DATE_SUB(DATE_SUB(Now(), INTERVAL [TZ_OFFSET] HOUR), INTERVAL 14 DAY), '%Y-%m-%d'), 0, 0, 0
         UNION ALL
        SELECT DATE_FORMAT(DATE_SUB(DATE_SUB(Now(), INTERVAL [TZ_OFFSET] HOUR), INTERVAL 15 DAY), '%Y-%m-%d'), 0, 0, 0
        ) tmp
 GROUP BY tmp.`events_at`
 ORDER BY tmp.`events_at` DESC
 LIMIT 0, 14