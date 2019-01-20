SELECT ch.`guid` as `channel_guid`
  FROM `Channel` ch INNER JOIN `Post` po ON ch.`id` = po.`channel_id`
 WHERE ch.`is_deleted` = 'N' and po.`is_deleted` = 'N' and po.`guid` = '[POST_GUID]'
 LIMIT 1;