SELECT tmp.`place_name`, tmp.`alt_name`,
       (SELECT z.`name` FROM `tmpGeoName` z
         WHERE z.`is_deleted` = 'N' and z.`feature_code` = 'ADM2' and z.`country_code` = tmp.`country_code`
         ORDER BY (CASE WHEN (z.`latitude` - tmp.`latitude`) < 0 THEN (z.`latitude` - tmp.`latitude`) * -1 ELSE (z.`latitude` - tmp.`latitude`) END +
                   CASE WHEN (z.`longitude` - tmp.`longitude`) < 0 THEN (z.`longitude` - tmp.`longitude`) * -1 ELSE (z.`longitude` - tmp.`longitude`) END) LIMIT 1) as `area_name`,
       (SELECT z.`name` FROM `tmpGeoName` z
         WHERE z.`is_deleted` = 'N' and z.`feature_code` = 'ADM1' and z.`country_code` = tmp.`country_code`
         ORDER BY (CASE WHEN (z.`latitude` - tmp.`latitude`) < 0 THEN (z.`latitude` - tmp.`latitude`) * -1 ELSE (z.`latitude` - tmp.`latitude`) END +
                   CASE WHEN (z.`longitude` - tmp.`longitude`) < 0 THEN (z.`longitude` - tmp.`longitude`) * -1 ELSE (z.`longitude` - tmp.`longitude`) END) LIMIT 1) as `state_name`,
       tmp.`country_name`
  FROM (SELECT [COORD_LAT] as `latitude`, [COORD_LONG] as `longitude`,
               CASE WHEN (gn.`latitude` - [COORD_LAT]) < 0 THEN (gn.`latitude` - [COORD_LAT]) * -1 ELSE (gn.`latitude` - [COORD_LAT]) END as `lat_score`,
               CASE WHEN (gn.`longitude` - [COORD_LONG]) < 0 THEN (gn.`longitude` - [COORD_LONG]) * -1 ELSE (gn.`longitude` - [COORD_LONG]) END as `long_score`,
               (CASE WHEN (gn.`latitude` - [COORD_LAT]) < 0 THEN (gn.`latitude` - [COORD_LAT]) * -1 ELSE (gn.`latitude` - [COORD_LAT]) END +
                CASE WHEN (gn.`longitude` - [COORD_LONG]) < 0 THEN (gn.`longitude` - [COORD_LONG]) * -1 ELSE (gn.`longitude` - [COORD_LONG]) END) as `score`,
               gn.`name` as `place_name`,
               CASE WHEN IFNULL(gn.`secondary`, '') <> '' THEN (SELECT z.`name` FROM `tmpGeoName` z WHERE z.`is_deleted` = 'N' and z.`gnid` = gn.`secondary`)
                    ELSE NULL END as `alt_name`,
               gn.`country_code`, co.`name` as `country_name`
          FROM `tmpGeoName` gn INNER JOIN `tmpCountry` co ON gn.`country_code` = co.`code`
         WHERE co.`is_deleted` = 'N' and gn.`is_deleted` = 'N' and gn.`lat_int` = FLOOR([COORD_LAT]) and gn.`long_int` = FLOOR([COORD_LONG])
         ORDER BY `score`
         LIMIT 1) tmp
 LIMIT 1;