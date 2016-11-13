# Dump of table lychee_photos
# ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `?` (
  `id` bigint(14) unsigned NOT NULL,
  `url` varchar(100) NOT NULL,
  `type` varchar(10) NOT NULL,
  `width` int(11) NOT NULL,
  `height` int(11) NOT NULL,
  `size` varchar(20) NOT NULL,
  `iso` varchar(15) NOT NULL,
  `aperture` varchar(20) NOT NULL,
  `make` varchar(50) NOT NULL,
  `model` varchar(50) NOT NULL,
  `shutter` varchar(30) NOT NULL,
  `focal` varchar(20) NOT NULL,
  `takestamp` int(11) DEFAULT NULL,
  `thumbUrl` char(37) NOT NULL,
  `checksum` char(40) DEFAULT NULL,
  `media_type` VARCHAR(100) DEFAULT 'photo',
  `medium` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;