SELECT COUNT(DISTINCT col.`table_name`) as `tables`, COUNT(col.`column_name`) as `columns`,
       CONCAT(SUBSTRING(md5(GROUP_CONCAT(col.`column_name`)), 1, 8), '-',
              RIGHT(SUM(col.`ordinal_position`), 4), '-',
              RIGHT(CONCAT('0000', SUM(CASE WHEN IFNULL(col.`column_default`, '') = '' THEN 0 ELSE 1 END)), 4), '-',
              RIGHT(CONCAT('0000', SUM(CASE WHEN col.`is_nullable` = 'NO' THEN 0 ELSE 1 END)), 4), '-',
              SUBSTRING(md5(GROUP_CONCAT(IFNULL(col.`collation_name`, ''))), 1, 6),
              SUBSTRING(md5(GROUP_CONCAT(IFNULL(col.`character_set_name`, ''))), 1, 6)) as `hash`
  FROM `Information_Schema`.`Columns` col
 WHERE col.`table_schema` = '[DB_NAME]';