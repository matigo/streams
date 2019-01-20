SELECT tz.`id`, tz.`name`, tz.`description`, tz.`group`
  FROM `Timezone` tz
 WHERE tz.`is_deleted` = 'N'
 ORDER BY tz.`description`;