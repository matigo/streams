UPDATE `Post` po INNER JOIN `PostAction` pp ON po.`id` = pp.`post_id`
                 INNER JOIN `Persona` pa ON pp.`persona_id` = pa.`id`
   SET pp.`pin_type` = CASE WHEN '[IS_POST]' = 'Y' AND LOWER('[VALUE]') IN ('pin.black', 'pin.blue', 'pin.green', 'pin.orange', 'pin.red', 'pin.yellow') THEN LOWER('[VALUE]') ELSE 'pin.none' END,
       pp.`updated_at` = Now()
 WHERE po.`is_deleted` = 'N' and po.`guid` = '[POST_GUID]'
   and pa.`is_deleted` = 'N' and pa.`guid` = '[PERSONA_GUID]'
   and pa.`account_id` = [ACCOUNT_ID];