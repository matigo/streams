SELECT po.`value`,
       (SELECT z.`value` FROM `PostMeta` z WHERE z.`is_deleted` = 'N' and z.`key` = 'encrypt_passhash' and z.`post_id` = po.`id`) as `passhash`,
       (SELECT z.`value` FROM `PostMeta` z WHERE z.`is_deleted` = 'N' and z.`key` = 'encrypt_cipher' and z.`post_id` = po.`id`) as `cipher`,
       (SELECT z.`value` FROM `PostMeta` z WHERE z.`is_deleted` = 'N' and z.`key` = 'encrypt_iv' and z.`post_id` = po.`id`) as `iv`
  FROM `Post` po INNER JOIN `Channel` ch ON po.`channel_id` = ch.`id`
 WHERE ch.`is_deleted` = 'N' and po.`is_deleted` = 'N' and IFNULL(po.`expires_at`, DATE_ADD(Now(), INTERVAL 1 MINUTE)) > Now()
   and po.`guid` = '[RECORD_GUID]'
 LIMIT 1;