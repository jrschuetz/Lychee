<?php

use Lychee\Modules\Album;
use Lychee\Modules\Database;
use Lychee\Modules\Photo;
use Lychee\Modules\Settings;

/**
 * @return array|false Returns an array with albums and photos.
 */
function search($term) {

	// Initialize return var
	$return = array(
		'photos' => null,
		'albums' => null,
		'hash'   => ''
	);

	/**
	 * Photos
	 */
     
    if($_SESSION['role'] == 'admin' ) { // Can search through all photos
        $query  = Database::prepare(Database::get(), "SELECT p_u.id, p_u.title, p_u.description, p_u.tags, p_u.public, p_u.star, p.thumbUrl, p.takestamp, p.url, p.medium FROM ? p JOIN ? p_u ON p.id = p_u.photo_id WHERE p_u.title LIKE '%?%' OR p_u.description LIKE '%?%' OR p_u.tags LIKE '%?%'", array(LYCHEE_TABLE_PHOTOS, LYCHEE_TABLE_PHOTOS_USERS , $term, $term, $term));
	} else { // Limited to own, shared and public photos
        $query  = Database::prepare(Database::get(), "
            SELECT id, title, description, tags, public, star, thumbUrl, takestamp, url, medium
            FROM (
                (SELECT p_u.id, p_u.title, p_u.description, p_u.tags, p_u.public, p_u.star, p.thumbUrl, p.takestamp, p.url, p.medium FROM ? p
                    JOIN ? p_u ON p.id = p_u.photo_id
                    WHERE (user_id=? OR public=1)
                ) UNION (SELECT p_u.id, p_u.title, p_u.description, p_u.tags, p_u.public, p_u.star, p.thumbUrl, p.takestamp, p.url, p.medium FROM ? p
                    JOIN ? p_u ON p.id = p_u.photo_id
                    JOIN ? pr ON pr.album_id = p_u.album_id
                    WHERE pr.view = 1 AND pr.user_id = ?)
                ) union_table
            WHERE title LIKE '%?%' OR description LIKE '%?%' OR tags LIKE '%?%'", array(LYCHEE_TABLE_PHOTOS, LYCHEE_TABLE_PHOTOS_USERS, $_SESSION['userid'], LYCHEE_TABLE_PHOTOS, LYCHEE_TABLE_PHOTOS_USERS, LYCHEE_TABLE_PRIVILEGES, $_SESSION['userid'], $term, $term, $term));
    }
    $result = Database::execute(Database::get(), $query, __METHOD__, __LINE__);

	if ($result===false) return false;

	while($photo = $result->fetch_assoc()) {

		$photo = Photo::prepareData($photo);
		$return['photos'][$photo['id']] = $photo;

	}

	/**
	 * Albums
	 */
    
    if($_SESSION['role'] == 'admin' ) { // Can search through all albums
        $query  = Database::prepare(Database::get(), "SELECT id, title, public, sysstamp, password FROM ? WHERE title LIKE '%?%' OR description LIKE '%?%'", array(LYCHEE_TABLE_ALBUMS, $term, $term));
    } else { // Limited to own, shared and public albums
        $query  = Database::prepare(Database::get(), "SELECT alb.id, alb.title, alb.public, alb.sysstamp, alb.password FROM ? alb LEFT JOIN ? priv ON alb.id = priv.album_id WHERE (title LIKE '%?%' OR description LIKE '%?%') AND (alb.user_id = ? OR priv.user_id = ? OR alb.public = 1)", array(LYCHEE_TABLE_ALBUMS, LYCHEE_TABLE_PRIVILEGES, $term, $term, $_SESSION['userid'], $_SESSION['userid']));
	}
    $result = Database::execute(Database::get(), $query, __METHOD__, __LINE__);

	if ($result===false) return false;

	while($album = $result->fetch_assoc()) {

		// Turn data from the database into a front-end friendly format
		$album = Album::prepareData($album);

		// Thumbs
		$query  = Database::prepare(Database::get(), "SELECT p.thumbUrl FROM ? p JOIN ? p_u ON p.id = p_u.photo_id WHERE p_u.album_id = '?' " . Settings::get()['sortingPhotos'] . " LIMIT 0, 3", array(LYCHEE_TABLE_PHOTOS, LYCHEE_TABLE_PHOTOS_USERS, $album['id']));
		$thumbs = Database::execute(Database::get(), $query, __METHOD__, __LINE__);

		if ($thumbs===false) return false;

		// For each thumb
		$k = 0;
		while ($thumb = $thumbs->fetch_object()) {
			$album['thumbs'][$k] = LYCHEE_VIEW_FILE . LYCHEE_URL_UPLOADS_THUMB . $thumb->thumbUrl;
			$k++;
		}

		// Add to return
		$return['albums'][$album['id']] = $album;

	}

	// Hash
	$return['hash'] = md5(json_encode($return));

	return $return;

}

?>
