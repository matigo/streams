INSERT INTO `File` (`account_id`, `name`, `local_name`, `public_name`, `hash`, `bytes`, `location`, `type`, `guid`)
SELECT [ACCOUNT_ID] as `account_id`, LEFT('[FILENAME]', 256) as `name`, LEFT('[FILELOCAL]', 80) as `local_name`, LEFT('[FILENAME]', 256) as `public_name`,
       '[FILEHASH]' as `hash`, [FILESIZE] as `bytes`, '[FILEPATH]' as `location`, '[FILETYPE]' as `type`, uuid() as `guid`;