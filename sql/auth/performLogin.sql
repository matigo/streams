SELECT zz.`id` as `account_id`, zz.`type`, zz.`display_name`, zz.`language_code`, COUNT(zp.`id`) as `persona_count`, za.`can_read`, za.`can_write`,
       CASE WHEN zz.`type` = 'account.admin' THEN 0
            ELSE DATEDIFF(Now(), IFNULL((SELECT max(t.`updated_at`) FROM `Tokens` t WHERE t.`account_id` = zz.`id`), Now())) END as `last_activity`
  FROM `Channel` zh INNER JOIN `ChannelAuthor` za ON zh.`id` = za.`channel_id`
                    INNER JOIN `Persona` zp ON za.`persona_id` = zp.`id`
                    INNER JOIN `Account` zz ON zp.`account_id` = zz.`id`
 WHERE zz.`is_deleted` = 'N' and zp.`is_deleted` = 'N' and za.`is_deleted` = 'N' and zh.`is_deleted` = 'N'
   and zz.`type` IN ('account.admin', 'account.normal') and zh.`guid` = '[CHANNEL_GUID]'
   and LOWER(zz.`email`) = LOWER('[USERADDR]') and zz.`password` = sha2(CONCAT('[SHA_SALT]', '[USERPASS]'), 512)
 GROUP BY zz.`id`, zz.`type`, zz.`display_name`, zz.`language_code`, za.`can_read`, za.`can_write`, zh.`id`, zz.`type`
 UNION ALL
SELECT zz.`id` as `account_id`, zz.`type`, zz.`display_name`, zz.`language_code`, COUNT(zp.`id`) as `persona_count`, 'Y' as `can_read`, 'N' as `can_write`,
       CASE WHEN zz.`type` = 'account.admin' THEN 0
            ELSE DATEDIFF(Now(), IFNULL((SELECT max(t.`updated_at`) FROM `Tokens` t WHERE t.`account_id` = zz.`id`), Now())) END as `last_activity`
  FROM `Channel` zh INNER JOIN `Persona` zp
                    INNER JOIN `Account` zz ON zp.`account_id` = zz.`id`
 WHERE zz.`is_deleted` = 'N' and zp.`is_deleted` = 'N' and zh.`is_deleted` = 'N'
   and zz.`type` IN ('account.admin', 'account.normal') and zh.`guid` = '[CHANNEL_GUID]'
   and LOWER(zz.`email`) = LOWER('[USERADDR]') and zz.`password` = sha2(CONCAT('[SHA_SALT]', '[USERPASS]'), 512)
 GROUP BY zz.`id`, zz.`type`, zz.`display_name`, zz.`language_code`, zh.`id`, zz.`type`
 LIMIT 1;