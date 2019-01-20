INSERT INTO `PostAction` (`post_id`, `persona_id`)
SELECT po.`id` as `post_id`, pa.`id` as `persona_id`
  FROM `Post` po INNER JOIN `Persona` pa
 WHERE po.`is_deleted` = 'N' and po.`guid` = '[POST_GUID]'
   and pa.`is_deleted` = 'N' and pa.`guid` = '[PERSONA_GUID]'
    ON DUPLICATE KEY UPDATE `is_deleted` = 'N',
                            `updated_at` = Now();