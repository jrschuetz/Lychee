# Dump of table lychee_photos_users
# ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `?` (
    `user_id`  int(11) NOT NULL,
    `photo_id` bigint(14) unsigned NOT NULL,
    `title` varchar(100) NOT NULL DEFAULT '',
    `description` varchar(1000) DEFAULT '',
    `tags` varchar(1000) NOT NULL DEFAULT '',
    `public` tinyint(1) NOT NULL,
    `star` tinyint(1) NOT NULL,
    `album` bigint(20) unsigned DEFAULT NULL, -- TODO: many-to-many relationship
    PRIMARY KEY (user_id, photo_id),
    KEY `Index_star` (`star`),
    KEY `Index_album` (`album`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8; 