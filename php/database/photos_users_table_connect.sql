ALTER TABLE `?`
   ADD CONSTRAINT `lychee_photos_users_ibfk_1` FOREIGN KEY (`photo_id`) REFERENCES `?` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
   ADD CONSTRAINT `lychee_photos_users_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `?` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;