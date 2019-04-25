SELECT LOWER(am.`key`) AS `type`, am.`value`, am.`created_at`, am.`updated_at`
  FROM `AccountMeta` am
 WHERE am.`is_deleted` = 'N' and am.`key` = 'preference.[TYPE_KEY]' and am.`account_id` = [ACCOUNT_ID]
 LIMIT 1;