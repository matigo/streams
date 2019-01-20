SELECT th.`id` as `post_id`, th.`publish_at`
  FROM `Post` po INNER JOIN `Post` th ON IFNULL(po.`thread_id`, po.`id`) = IFNULL(th.`thread_id`, th.`id`)
 WHERE th.`is_deleted` = 'N' and po.`is_deleted` = 'N'
   and th.`publish_at` <= Now() and IFNULL(th.`expires_at`, Now()) >= Now()
   and po.`guid` = '[POST_GUID]'
 ORDER BY th.`publish_at`;