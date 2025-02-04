INSERT INTO `ServerIP` (`ipv4`, `ipv6`)
SELECT LOWER('[IPV4_ADDR]') as `ipv4`, LOWER('[IPV6_ADDR]') as `ipv6`
  FROM (SELECT src.`id`, src.`ipv4`, src.`ipv6`
          FROM `ServerIP` src
         WHERE src.`is_deleted` = 'N'
         ORDER BY src.`id` DESC LIMIT 1) tmp
 WHERE 'Y' = CASE WHEN LENGTH('[IPV4_ADDR]') > 0 AND tmp.`ipv4` != '[IPV4_ADDR]' THEN 'Y'
                  WHEN LENGTH('[IPV6_ADDR]') > 0 AND tmp.`ipv6` != '[IPV6_ADDR]' THEN 'Y'
                  ELSE 'N' END
 LIMIT 1;