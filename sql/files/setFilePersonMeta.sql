INSERT INTO `PersonMeta` (`person_id`, `key`, `value`, `created_at`, `created_by`, `updated_at`, `updated_by`)
SELECT p.`id` as `person_id`,
       CASE WHEN '[META_TYPE]' = 'file.category.assessment' THEN CONCAT('file.assess.', idx.`index_id`) 
            WHEN '[META_TYPE]' = 'file.category.progress' THEN CONCAT('file.progress.', idx.`index_id`) 
            WHEN '[META_TYPE]' = 'file.category.final' THEN CONCAT('file.final.', idx.`index_id`)
            WHEN '[META_TYPE]' = 'file.category.counselling' THEN CONCAT('file.counselling.', idx.`index_id`) 
            WHEN '[META_TYPE]' = 'file.category.contract' THEN CONCAT('file.contract.', idx.`index_id`) 
            WHEN '[META_TYPE]' = 'file.category.inquiry' THEN CONCAT('file.inquiry.', idx.`index_id`) 
            WHEN '[META_TYPE]' = 'file.category.sequence' THEN CONCAT('file.sequence.', idx.`index_id`) 
            WHEN '[META_TYPE]' = 'file.category.audio' THEN CONCAT('file.audio.', idx.`index_id`)
            WHEN '[META_TYPE]' = 'file.category.video' THEN CONCAT('file.video.', idx.`index_id`)
            ELSE CONCAT('file.other.', idx.`index_id`) END as `type`,
       (SELECT f.`localname` FROM `File` f WHERE f.`is_deleted` = 'N' and f.`id` = [FILE_ID]) as `value`,
       Now(), [ACCOUNT_ID], Now(), [ACCOUNT_ID]
  FROM `Person` p,
       (SELECT COUNT(DISTINCT pm.`key`) + 100 as `index_id` FROM `Person` p INNER JOIN `PersonMeta` pm ON pm.`person_id` = p.`id`
         WHERE p.`is_deleted` = 'N' and p.`guid` = '[PERSON_GUID]' and pm.`key` LIKE 'file.%') idx
 WHERE p.`is_deleted` = 'N' and p.`guid` = '[PERSON_GUID]'
 LIMIT 1;