SELECT po.`id` as `locker_id`, po.`guid`, po.`publish_at`, po.`expires_at`
  FROM `Post` po INNER JOIN `Channel` ch ON po.`channel_id` = ch.`id`
 WHERE ch.`is_deleted` = 'N' and po.`is_deleted` = 'N' and IFNULL(po.`expires_at`, DATE_ADD(Now(), INTERVAL 1 MINUTE)) > Now()
   and 'Y' = CASE WHEN po.`id` = [ITEM_IDX] THEN 'Y'
                  WHEN po.`guid` = '[ITEMGUID]' THEN 'Y'
                  ELSE 'N' END
 LIMIT 1;