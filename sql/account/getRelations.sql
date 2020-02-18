SELECT DISTINCT tmp.`id`,
       tmp.`name`, tmp.`last_name`, tmp.`first_name`, tmp.`display_name`, tmp.`avatar_img`, tmp.`persona_guid`,
       tmp.`follows`, tmp.`follows_you`, tmp.`is_muted`, tmp.`is_blocked`, tmp.`is_starred`, tmp.`pin_type`,
       tmp.`rel_name`, tmp.`rel_last_name`, tmp.`rel_first_name`, tmp.`rel_display_name`, tmp.`rel_avatar_img`, tmp.`rel_guid`,
       pp.`last_at`
  FROM (SELECT pa.`id`, pa.`name`, pa.`last_name`, pa.`first_name`, pa.`display_name`, pa.`avatar_img`, pa.`guid` as `persona_guid`,
               pr.`follows`, pr.`is_muted`, pr.`is_blocked`, pr.`is_starred`, pr.`pin_type`,
               IFNULL((SELECT z.`follows` FROM `PersonaRelation` z
                        WHERE z.`is_deleted` = 'N' and z.`persona_id` = pr.`related_id` and z.`related_id` = pr.`persona_id`), 'N') as `follows_you`,
               ra.`id` as `rel_id`, ra.`name` as `rel_name`, ra.`last_name` as `rel_last_name`, ra.`first_name` as `rel_first_name`, ra.`guid` as `rel_guid`,
               ra.`display_name` as `rel_display_name`, ra.`avatar_img` as `rel_avatar_img`
          FROM `Account` acct INNER JOIN `Persona` pa ON acct.`id` = pa.`account_id`
                              INNER JOIN `PersonaRelation` pr ON pa.`id` = pr.`persona_id`
                              INNER JOIN `Persona` ra ON pr.`related_id` = ra.`id`
                              INNER JOIN `Account` rcct ON ra.`account_id` = rcct.`id`
         WHERE acct.`is_deleted` = 'N' and pr.`is_deleted` = 'N' and pa.`is_deleted` = 'N'
           and rcct.`is_deleted` = 'N' and ra.`is_deleted` = 'N' and ra.`is_active` = 'Y'
           and pa.`is_active` = 'Y' and acct.`id` = [ACCOUNT_ID]
         UNION ALL
        SELECT pa.`id`, pa.`name`, pa.`last_name`, pa.`first_name`, pa.`display_name`, pa.`avatar_img`, pa.`guid` as `persona_guid`,
               IFNULL(z.`follows`, 'N') as `follows`, IFNULL(z.`is_muted`, 'N') as `is_muted`, IFNULL(z.`is_blocked`, 'N') as `is_blocked`,
               IFNULL(z.`is_starred`, 'N') as `is_starred`, IFNULL(z.`pin_type`, 'pin.none') as `pin_type`, pr.`follows` as `follows_you`,
               ra.`id` as `rel_id`, ra.`name` as `rel_name`, ra.`last_name` as `rel_last_name`, ra.`first_name` as `rel_first_name`, ra.`guid` as `rel_guid`,
               ra.`display_name` as `rel_display_name`, ra.`avatar_img` as `rel_avatar_img`
          FROM `Account` acct INNER JOIN `Persona` pa ON acct.`id` = pa.`account_id`
                              INNER JOIN `PersonaRelation` pr ON pa.`id` = pr.`related_id`
                              INNER JOIN `Persona` ra ON pr.`persona_id` = ra.`id`
                              INNER JOIN `Account` rcct ON ra.`account_id` = rcct.`id`
                         LEFT OUTER JOIN `PersonaRelation` z ON pa.`id` = z.`persona_id` AND ra.`id` = z.`related_id`
         WHERE acct.`is_deleted` = 'N' and pa.`is_deleted` and pr.`is_deleted` and pr.`follows` = 'Y'
           and acct.`id` = [ACCOUNT_ID]) tmp LEFT OUTER JOIN (SELECT po.`persona_id`, MAX(po.`publish_at`) as `last_at`
                                                                FROM `Post` po
                                                               WHERE po.`is_deleted` = 'N'
                                                               GROUP BY po.`persona_id`) pp ON tmp.`rel_id` = pp.`persona_id`
 ORDER BY tmp.`id`, tmp.`rel_name`;

