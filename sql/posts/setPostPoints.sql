UPDATE `Post` po INNER JOIN `PostAction` pp ON po.`id` = pp.`post_id`
                 INNER JOIN `Persona` pa ON pp.`persona_id` = pa.`id`
   SET pp.`points` = CASE WHEN ROUND([VALUE], 0) BETWEEN 0 AND 5 THEN ROUND([VALUE], 0) ELSE 0 END,
       pp.`updated_at` = Now()
 WHERE po.`is_deleted` = 'N' and po.`guid` = '[POST_GUID]'
   and pa.`is_deleted` = 'N' and pa.`guid` = '[PERSONA_GUID]';