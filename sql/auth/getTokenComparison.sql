SELECT t.`account_id`, p.`last_ro`, p.`first_ro`, p.`last_ka`, p.`first_ka`, p.`print_ro`, p.`print_ka`,
       MAX(CASE WHEN t.`guid` = '[TOKEN_GUID]' THEN 'Y' ELSE 'N' END) as `is_mine`
  FROM `Tokens` t INNER JOIN `Account` a ON t.`account_id` = a.`id`
                  INNER JOIN `Person` p ON a.`person_id` = p.`id`
 WHERE t.`guid` IN ('[TOKEN_GUID]', '[TPAGE_GUID]')
 GROUP BY t.`account_id`, p.`last_ro`, p.`first_ro`, p.`last_ka`, p.`first_ka`, p.`print_ro`, p.`print_ka`
 ORDER BY `is_mine` DESC;