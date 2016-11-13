CREATE TABLE IF NOT EXISTS `?` (
      `user_id` int(11) NOT NULL,
      `album_id` bigint(14) unsigned NOT NULL,
      `view` tinyint(1) NOT NULL DEFAULT '0',
      `upload` tinyint(1) NOT NULL DEFAULT '0',
      `erase` tinyint(1) NOT NULL DEFAULT '0',
      PRIMARY KEY (`user_id`,`album_id`),
      KEY `album_id` (`album_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;