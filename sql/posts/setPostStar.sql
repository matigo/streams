UPDATE `Post` po INNER JOIN `PostAction` pp ON po.`id` = pp.`post_id`
                 INNER JOIN `Persona` pa ON pp.`persona_id` = pa.`id`
   SET pp.`is_starred` = CASE WHEN '[VALUE]' = 'Y' THEN 'Y' ELSE 'N' END,
       pp.`updated_at` = Now()
 WHERE po.`is_deleted` = 'N' and po.`guid` = '[POST_GUID]'
   and pa.`is_deleted` = 'N' and pa.`guid` = '[PERSONA_GUID]';