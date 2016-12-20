# Dump of table lychee_photos_albums
# ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `?` (
    `photo_id` bigint(14) unsigned NOT NULL,
    `album_id` bigint(20) unsigned DEFAULT NULL,
    // TODO: add title -> fixed for photo per user or album-photo?
    PRIMARY KEY (photo_id, album_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
