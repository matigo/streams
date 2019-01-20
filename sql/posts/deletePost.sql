DELETE px FROM `PostHistory` px INNER JOIN `Post` po ON px.`post_id` = po.`id`
                                INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
 WHERE po.`is_deleted` = 'N' and pa.`is_deleted` = 'N'
   and po.`guid` = '[POST_GUID]' and pa.`account_id` = [ACCOUNT_ID];
[SQL_SPLITTER]
DELETE px FROM `PostMention` px INNER JOIN `Post` po ON px.`post_id` = po.`id`
                                INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
 WHERE po.`is_deleted` = 'N' and pa.`is_deleted` = 'N'
   and po.`guid` = '[POST_GUID]' and pa.`account_id` = [ACCOUNT_ID];
[SQL_SPLITTER]
DELETE px FROM `PostMeta` px INNER JOIN `Post` po ON px.`post_id` = po.`id`
                             INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
 WHERE po.`is_deleted` = 'N' and pa.`is_deleted` = 'N'
   and po.`guid` = '[POST_GUID]' and pa.`account_id` = [ACCOUNT_ID];
[SQL_SPLITTER]
DELETE px FROM `PostMeta` px INNER JOIN `Post` po ON px.`post_id` = po.`id`
                             INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
 WHERE po.`is_deleted` = 'N' and pa.`is_deleted` = 'N'
   and po.`guid` = '[POST_GUID]' and pa.`account_id` = [ACCOUNT_ID];
[SQL_SPLITTER]
DELETE px FROM `PostFile` px INNER JOIN `Post` po ON px.`post_id` = po.`id`
                             INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
 WHERE po.`is_deleted` = 'N' and pa.`is_deleted` = 'N'
   and po.`guid` = '[POST_GUID]' and pa.`account_id` = [ACCOUNT_ID];
[SQL_SPLITTER]
DELETE px FROM `PostFile` px INNER JOIN `Post` po ON px.`post_id` = po.`id`
                             INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
 WHERE po.`is_deleted` = 'N' and pa.`is_deleted` = 'N'
   and po.`guid` = '[POST_GUID]' and pa.`account_id` = [ACCOUNT_ID];
[SQL_SPLITTER]
DELETE px FROM `PostTags` px INNER JOIN `Post` po ON px.`post_id` = po.`id`
                             INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
 WHERE po.`is_deleted` = 'N' and pa.`is_deleted` = 'N'
   and po.`guid` = '[POST_GUID]' and pa.`account_id` = [ACCOUNT_ID];
[SQL_SPLITTER]
DELETE px FROM `PostAction` px INNER JOIN `Post` po ON px.`post_id` = po.`id`
                               INNER JOIN `Persona` pa ON po.`persona_id` = pa.`id`
 WHERE po.`is_deleted` = 'N' and pa.`is_deleted` = 'N'
   and po.`guid` = '[POST_GUID]' and pa.`account_id` = [ACCOUNT_ID];
[SQL_SPLITTER]
DELETE px FROM `Post` px INNER JOIN `Persona` pa ON px.`persona_id` = pa.`id`
 WHERE px.`is_deleted` = 'N' and px.`guid` = '[POST_GUID]' and pa.`account_id` = [ACCOUNT_ID];