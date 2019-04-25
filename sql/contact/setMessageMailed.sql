UPDATE `SiteContact` sc
   SET sc.`is_mailed` = 'Y',
       sc.`is_read` = 'Y',
       sc.`updated_at` = Now()
 WHERE sc.`is_deleted` = 'N' and sc.`guid` = '[MSG_GUID]' and sc.`site_id` = [SITE_ID];