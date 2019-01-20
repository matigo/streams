INSERT INTO `File` (`account_id`, `name`, `local_name`, `public_name`, `hash`, `bytes`, `location`, `type`, `guid`)
SELECT [ACCOUNT_ID] as `account_id`, '[FILENAME]' as `name`, '[FILELOCAL]' as `local_name`, '[FILENAME]' as `public_name`,
       '[FILEHASH]' as `hash`, [FILESIZE] as `bytes`, '[FILEPATH]' as `location`, '[FILETYPE]' as `type`, uuid() as `guid`;