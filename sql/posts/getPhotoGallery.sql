SELECT pf.`post_id`, po.`title`, po.`value`, po.`canonical_url`, po.`type`, po.`guid`, po.`publish_at`,
       pf.`file_id`, fi.`name`, fi.`location`, fi.`local_name`, fi.`hash`, fi.`bytes`, fi.`type`, fi.`guid` as `file_guid`,
       fi.`expires_at`, fi.`created_at`, fi.`updated_at`,

       IFNULL(MAX(CASE WHEN fm.`key` IN ('gallery.visible', 'visible') THEN fm.`value` ELSE NULL END), 'Y') as `is_visible`,
       MAX(CASE WHEN fm.`key` IN ('gallery.view-class', 'view-class') THEN fm.`value` ELSE NULL END) as `view_class`,

       MAX(CASE WHEN fm.`key` IN ('image.has_medium', 'has_medium') THEN fm.`value` ELSE NULL END) as `has_medium`,
       MAX(CASE WHEN fm.`key` IN ('image.has_thumb', 'has_thumb') THEN fm.`value` ELSE NULL END) as `has_thumb`,

       MAX(CASE WHEN fm.`key` IN ('camera.make', 'make') THEN fm.`value` ELSE NULL END) as `camera_make`,
       MAX(CASE WHEN fm.`key` IN ('camera.model', 'model') THEN fm.`value` ELSE NULL END) as `camera_model`,
       MAX(CASE WHEN fm.`key` IN ('image.lens', 'lens') THEN fm.`value` ELSE NULL END) as `camera_lens`,

       MAX(CASE WHEN fm.`key` IN ('image.width', 'width') THEN fm.`value` ELSE NULL END) as `image_width`,
       MAX(CASE WHEN fm.`key` IN ('image.height', 'height') THEN fm.`value` ELSE NULL END) as `image_height`,
       MAX(CASE WHEN fm.`key` IN ('image.shutter', 'shutter') THEN fm.`value` ELSE NULL END) as `image_shutter`,
       MAX(CASE WHEN fm.`key` IN ('image.iso', 'iso') THEN fm.`value` ELSE NULL END) as `image_iso`,
       MAX(CASE WHEN fm.`key` IN ('image.aperture', 'aperture') THEN fm.`value` ELSE NULL END) as `image_aperture`,
       MAX(CASE WHEN fm.`key` IN ('image.exposure', 'exposure') THEN fm.`value` ELSE NULL END) as `image_exposure`,
       MAX(CASE WHEN fm.`key` IN ('image.focallength', 'focallength') THEN fm.`value` ELSE NULL END) as `image_focallength`,

       MAX(CASE WHEN fm.`key` IN ('geo.direction', 'direction') THEN fm.`value` ELSE NULL END) as `geo_direction`,
       MAX(CASE WHEN fm.`key` IN ('geo.latitude', 'latitude') THEN fm.`value` ELSE NULL END) as `geo_latitude`,
       MAX(CASE WHEN fm.`key` IN ('geo.longitude', 'longitude') THEN fm.`value` ELSE NULL END) as `geo_longitude`,
       MAX(CASE WHEN fm.`key` IN ('geo.altitude', 'altitude') THEN fm.`value` ELSE NULL END) as `geo_altitude`
  FROM `SiteUrl` su INNER JOIN `Site` si ON su.`site_id` = si.`id`
                    INNER JOIN `Channel` ch ON si.`id` = ch.`site_id`
                    INNER JOIN `Post` po ON ch.`id` = po.`channel_id`
                    INNER JOIN `PostFile` pf ON po.`id` = pf.`post_id`
                 INNER JOIN `File` fi ON pf.`file_id` = fi.`id`
            LEFT OUTER JOIN `FileMeta` fm ON fi.`id` = fm.`file_id` AND fm.`is_deleted` = 'N'
 WHERE su.`is_deleted` = 'N' and si.`is_deleted` = 'N' and ch.`is_deleted` = 'N' and po.`is_deleted` = 'N' and pf.`is_deleted` = 'N' and fi.`is_deleted` = 'N'
   and po.`type` IN ('post.article', 'post.photo') and po.`privacy_type` = 'visibility.public'
   and po.`publish_at` < Now() and IFNULL(po.`expires_at`, DATE_ADD(Now(), INTERVAL 5 SECOND)) > Now()
   and su.`is_active` = 'Y' and fi.`type` LIKE 'image/%'
   and si.`id` = [SITE_ID]
 GROUP BY pf.`post_id`, po.`title`, po.`value`, po.`canonical_url`, po.`type`, po.`guid`, po.`publish_at`,
          pf.`file_id`, fi.`name`, fi.`local_name`, fi.`hash`, fi.`bytes`, fi.`location`, fi.`type`, fi.`guid`,
          fi.`expires_at`, fi.`created_at`, fi.`updated_at`
 ORDER BY po.`publish_at` DESC, pf.`file_id`
 LIMIT [COUNT];