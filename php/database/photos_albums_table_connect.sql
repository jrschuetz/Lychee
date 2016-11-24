ALTER TABLE `?`
   ADD CONSTRAINT `lychee_photos_albums_ibfk_1` FOREIGN KEY (`photo_id`) REFERENCES `?` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
   ADD CONSTRAINT `lychee_photos_albums_ibfk_2` FOREIGN KEY (`album_id`) REFERENCES `?` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;