SELECT acct.`id` as `account_id`, ROUND(UNIX_TIMESTAMP(GREATEST(MAX(am.`updated_at`), acct.`updated_at`))) as `account_version`,
       acct.`guid` as `account_guid`, acct.`type`, acct.`display_name`, acct.`language_code`,
       IFNULL((SELECT z.`avatar_img` FROM `Persona` z
                WHERE z.`is_deleted` = 'N' and z.`account_id` = acct.`id`
                ORDER BY z.`is_active` DESC LIMIT 1), 'default.png') as `avatar_url`,
       acct.`created_at`, ROUND(UNIX_TIMESTAMP(acct.`created_at`)) as `created_unix`,
       acct.`updated_at`, ROUND(UNIX_TIMESTAMP(acct.`updated_at`)) as `updated_unix`
  FROM `Account` acct LEFT OUTER JOIN `AccountMeta` am ON acct.`id` = am.`account_id`
 WHERE acct.`is_deleted` = 'N' and acct.`id` IN ([ACCOUNT_IDS])
 GROUP BY acct.`id`, acct.`guid`, acct.`type`, acct.`display_name`, acct.`language_code`, acct.`created_at`, acct.`updated_at`
 ORDER BY acct.`id`;