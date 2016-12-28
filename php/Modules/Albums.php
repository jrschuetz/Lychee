<?php

namespace Lychee\Modules;

final class Albums {

	/**
	 * @return boolean Returns true when successful.
	 */
	public function __construct() {

		return true;

	}

	/**
	 * @return array|false Returns an array of albums or false on failure.
	 */
	public function get($public = true) {

		// Call plugins
		Plugins::get()->activate(__METHOD__, 0, func_get_args());

		// Initialize return var
		$return = array(
			'smartalbums' => null,
			'albums'      => null,
			'num'         => 0
		);

		// Get SmartAlbums
		if ($public===false) $return['smartalbums'] = $this->getSmartAlbums();

		// Albums query
		if ($public===false) {
            if ($_SESSION['role'] === 'admin') {
                $query = Database::prepare(Database::get(), "SELECT id, title, public, sysstamp, password FROM ? " . Settings::get()['sortingAlbums'], array(LYCHEE_TABLE_ALBUMS));
            } else {
                $query = Database::prepare(Database::get(), "SELECT id, title, public, sysstamp, password FROM ? WHERE user_id = ? UNION SELECT a.id, a.title, a.public, a.sysstamp, a.password FROM ? a JOIN ? p on ( a.id = p.album_id) WHERE p.user_id = ? AND p.view = 1 " . Settings::get()['sortingAlbums'], array(LYCHEE_TABLE_ALBUMS,  $_SESSION['userid'], LYCHEE_TABLE_ALBUMS, LYCHEE_TABLE_PRIVILEGES, $_SESSION['userid']));
            }
        }
		else {
            $query = Database::prepare(Database::get(), 'SELECT id, title, public, sysstamp, password FROM ? WHERE public = 1 AND visible <> 0 ' . Settings::get()['sortingAlbums'], array(LYCHEE_TABLE_ALBUMS));
        }

		// Execute query
		$albums = Database::execute(Database::get(), $query, __METHOD__, __LINE__);

		if ($albums===false) return false;

		// For each album
		while ($album = $albums->fetch_assoc()) {

			// Turn data from the database into a front-end friendly format
			$album = Album::prepareData($album);

			// Thumbs
			if (($public===true && $album['password']==='0')||
				($public===false)) {

					// Execute query
					$query  = Database::prepare(Database::get(), "
                        SELECT thumbUrl FROM ? p
                            JOIN ? p_u
                                ON p.id = p_u.photo_id
                            WHERE p_u.album_id = '?' ORDER BY p_u.star DESC, " . substr(Settings::get()['sortingPhotos'], 9) . " LIMIT 3", array(LYCHEE_TABLE_PHOTOS, LYCHEE_TABLE_PHOTOS_USERS, $album['id']));
					$thumbs = Database::execute(Database::get(), $query, __METHOD__, __LINE__);

					if ($thumbs===false) return false;

					// For each thumb
					$k = 0;
					while ($thumb = $thumbs->fetch_object()) {
						$album['thumbs'][$k] = LYCHEE_VIEW_FILE . LYCHEE_URL_UPLOADS_THUMB . $thumb->thumbUrl;
						$k++;
					}

			}

			// Add to return
			$return['albums'][] = $album;

		}

		// Num of albums
		$return['num'] = $albums->num_rows;

		// Call plugins
		Plugins::get()->activate(__METHOD__, 1, func_get_args());

		return $return;

	}

	/**
	 * @return array|false Returns an array of smart albums or false on failure.
	 */
	private function getSmartAlbums() {

		// Initialize return var
		$return = array(
			'unsorted' => null,
			'public'   => null,
			'starred'  => null,
			'recent'   => null
		);

		/**
		 * Unsorted
		 */

        if ($_SESSION['role'] === 'admin') {
            $query = Database::prepare(Database::get(), "
                SELECT thumbUrl FROM ? p
                    LEFT JOIN ? p_u
                        ON p_u.photo_id = p.id
                WHERE p_u.album_id is NULL
                " . Settings::get()['sortingPhotos'], array(LYCHEE_TABLE_PHOTOS, LYCHEE_TABLE_PHOTOS_USERS));
        } else {
            $query = Database::prepare(Database::get(), "
                SELECT p.thumbUrl FROM ? p
                    LEFT JOIN ? p_u
                        ON p_u.photo_id = p.id
                WHERE p_u.user_id = ? AND p_u.album_id is NULL
                " . Settings::get()['sortingPhotos'], array(LYCHEE_TABLE_PHOTOS, LYCHEE_TABLE_PHOTOS_USERS, $_SESSION['userid'])); // WHERE ALBUM IS NULL  // TODO: multiple albums per photo possible
        }

        $unsorted = Database::execute(Database::get(), $query, __METHOD__, __LINE__);
		$i        = 0;

		if ($unsorted===false) return false;

		$return['unsorted'] = array(
			'thumbs' => array(),
			'num'    => $unsorted->num_rows
		);

		while($row = $unsorted->fetch_object()) {
			if ($i<3) {
				$return['unsorted']['thumbs'][$i] = LYCHEE_VIEW_FILE . LYCHEE_URL_UPLOADS_THUMB . $row->thumbUrl;
				$i++;
			} else break;
		}

		/**
		 * Starred
		 */
        // TODO: with duplicate photos, same photo can be shown more than once -> add DISTINCT?
        if ($_SESSION['role'] === 'admin') {
            $query = Database::prepare(Database::get(), "
                SELECT p.thumbUrl FROM ? p
                    JOIN ? p_u
                        ON p.id = p_u.photo_id
                WHERE p_u.star = 1
                " . Settings::get()['sortingPhotos'], array(LYCHEE_TABLE_PHOTOS, LYCHEE_TABLE_PHOTOS_USERS));
        } else {
            $query = Database::prepare(Database::get(), "
                SELECT p.thumbUrl FROM ? p
                    JOIN ? p_u
                        ON p.id = p_u.photo_id
                WHERE p_u.user_id = '?' AND p_u.star = 1
                " . Settings::get()['sortingPhotos'], array(LYCHEE_TABLE_PHOTOS, LYCHEE_TABLE_PHOTOS_USERS, $_SESSION['userid']));
        }
		$starred = Database::execute(Database::get(), $query, __METHOD__, __LINE__);
		$i       = 0;

		if ($starred===false) return false;

		$return['starred'] = array(
			'thumbs' => array(),
			'num'    => $starred->num_rows
		);

		while($row3 = $starred->fetch_object()) {
			if ($i<3) {
				$return['starred']['thumbs'][$i] = LYCHEE_VIEW_FILE . LYCHEE_URL_UPLOADS_THUMB . $row3->thumbUrl;
				$i++;
			} else break;
		}

		/**
		 * Public
		 */

        if ($_SESSION['role'] === 'admin') {
            $query = Database::prepare(Database::get(), "
                SELECT p.thumbUrl FROM ? p
                    JOIN ? p_u
                        ON p.id = p_u.photo_id
                WHERE p_u.public = 1
                " . Settings::get()['sortingPhotos'], array(LYCHEE_TABLE_PHOTOS, LYCHEE_TABLE_PHOTOS_USERS));
        } else {
            $query = Database::prepare(Database::get(), "
                SELECT p.thumbUrl FROM ? p
                    JOIN ? p_u
                        ON p.id = p_u.photo_id
                WHERE p_u.user_id = '?' AND p_u.public = 1
                " . Settings::get()['sortingPhotos'], array(LYCHEE_TABLE_PHOTOS, LYCHEE_TABLE_PHOTOS_USERS, $_SESSION['userid']));
        }
		$public = Database::execute(Database::get(), $query, __METHOD__, __LINE__);
		$i      = 0;

		if ($public===false) return false;

		$return['public'] = array(
			'thumbs' => array(),
			'num'    => $public->num_rows
		);

		while($row2 = $public->fetch_object()) {
			if ($i<3) {
				$return['public']['thumbs'][$i] = LYCHEE_VIEW_FILE . LYCHEE_URL_UPLOADS_THUMB . $row2->thumbUrl;
				$i++;
			} else break;
		}

		/**
		 * Recent
		 */

        if ($_SESSION['role'] === 'admin') {
            $query = Database::prepare(Database::get(), "
                SELECT thumbUrl FROM ? p
                    JOIN ? p_u
                        ON p.id = p_u.photo_id
                WHERE LEFT(p.id, 10) >= unix_timestamp(DATE_SUB(NOW(), INTERVAL 1 DAY))
                " . Settings::get()['sortingPhotos'], array(LYCHEE_TABLE_PHOTOS, LYCHEE_TABLE_PHOTOS_USERS));
        } else {
            $query = Database::prepare(Database::get(), "
                SELECT p.thumbUrl FROM ? p
                    JOIN ? p_u
                        ON p.id = p_u.photo_id
                WHERE p_u.user_id = '?' AND LEFT(p.id, 10) >= unix_timestamp(DATE_SUB(NOW(), INTERVAL 1 DAY))
                " . Settings::get()['sortingPhotos'], array(LYCHEE_TABLE_PHOTOS, LYCHEE_TABLE_PHOTOS_USERS, $_SESSION['userid']));
        }
		$recent = Database::execute(Database::get(), $query, __METHOD__, __LINE__);
		$i      = 0;

		if ($recent===false) return false;

		$return['recent'] = array(
			'thumbs' => array(),
			'num'    => $recent->num_rows
		);

		while($row3 = $recent->fetch_object()) {
			if ($i<3) {
				$return['recent']['thumbs'][$i] = LYCHEE_VIEW_FILE . LYCHEE_URL_UPLOADS_THUMB . $row3->thumbUrl;
				$i++;
			} else break;
		}

		// Return SmartAlbums
		return $return;

	}

}

?>
