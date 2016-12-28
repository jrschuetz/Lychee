# Dump of table lychee_photos_users
# ------------------------------------------------------------

# Photo can be duplicated by user, 'id' must be used as unique link so that albums can contain multiple copies of same photo.

CREATE TABLE IF NOT EXISTS `?` (
    `id` bigint(14) unsigned NOT NULL,
    `photo_id` bigint(14) unsigned NOT NULL,
    `user_id`  int(11) NOT NULL,
    `album_id` bigint(20) unsigned DEFAULT NULL,
    `title` varchar(100) NOT NULL DEFAULT '',
    `description` varchar(1000) DEFAULT '',
    `tags` varchar(1000) NOT NULL DEFAULT '',
    `public` tinyint(1) NOT NULL,
    `star` tinyint(1) NOT NULL,
    PRIMARY KEY (id),
    KEY `Index_photo_user` (`photo_id`, `user_id`),
    KEY `Index_star` (`star`),
    KEY `Index_public` (`public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
