SELECT `ipv4`, `ipv6` FROM `ServerIP`
 WHERE `is_deleted` = 'N'
 ORDER BY `id` DESC LIMIT 1;