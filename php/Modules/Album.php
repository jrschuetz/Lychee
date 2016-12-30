<?php

namespace Lychee\Modules;

use ZipArchive;

final class Album {

	private $albumIDs = null;

	/**
	 * @return boolean Returns true when successful.
	 */
	public function __construct($albumIDs) {

		// Init vars
		$this->albumIDs = $albumIDs;

		return true;

	}

	/**
	 * @return string|false ID of the created album.
	 */
	public function add($title = 'Untitled') {

		// Call plugins
		Plugins::get()->activate(__METHOD__, 0, func_get_args());

		// Properties
		$id       = generateID();
		$sysstamp = time();
		$public   = 0;
		$visible  = 1;

		// Database
		$query  = Database::prepare(Database::get(), "INSERT INTO ? (id, title, user_id, sysstamp, public, visible) VALUES ('?', '?', '?', '?', '?', '?')", array(LYCHEE_TABLE_ALBUMS, $id, $title, $_SESSION['userid'], $sysstamp, $public, $visible));
		$result = Database::execute(Database::get(), $query, __METHOD__, __LINE__);

		// Call plugins
		Plugins::get()->activate(__METHOD__, 1, func_get_args());

		if ($result===false) return false;
		return $id;

	}

	/**
	 * Rurns album-attributes into a front-end friendly format. Note that some attributes remain unchanged.
	 * @return array Returns album-attributes in a normalized structure.
	 */
	public static function prepareData(array $data) {

		// This function requires the following album-attributes and turns them
		// into a front-end friendly format: id, title, public, sysstamp, password
		// Note that some attributes remain unchanged

		// Init
		$album = null;

		// Set unchanged attributes
		$album['id']     = $data['id'];
		$album['title']  = $data['title'];
		$album['public'] = $data['public'];

		// Additional attributes
		// Only part of $album when available
		if (isset($data['description']))  $album['description'] = $data['description'];
		if (isset($data['visible']))      $album['visible'] = $data['visible'];
		if (isset($data['downloadable'])) $album['downloadable'] = $data['downloadable'];

		// Parse date
		$album['sysdate'] = strftime('%B %Y', $data['sysstamp']);

		// Parse password
		$album['password'] = ($data['password']=='' ? '0' : '1');

		// Parse thumbs or set default value
		$album['thumbs'] = (isset($data['thumbs']) ? explode(',', $data['thumbs']) : array());

		return $album;

	}

	/**
	 * @return array|false Returns an array of photos and album information or false on failure.
	 */
	public function get() {

		// Check dependencies
		Validator::required(isset($this->albumIDs), __METHOD__);

		// Call plugins
		Plugins::get()->activate(__METHOD__, 0, func_get_args());

		// Get album information
		switch ($this->albumIDs) {

			case 'f':
				$return['public'] = '0';
                
                if ($_SESSION['role'] === 'admin') {
                    $query = Database::prepare(Database::get(), "
                        SELECT p_u.id, p_u.title, p_u.tags, p_u.public, p_u.star, p.thumbUrl, p.takestamp, p.url, p.medium, 'f' as album_id FROM ? p
                            JOIN ? p_u
                                ON p.id = p_u.photo_id
                        WHERE p_u.star = 1
                        " . Settings::get()['sortingPhotos'], array(LYCHEE_TABLE_PHOTOS, LYCHEE_TABLE_PHOTOS_USERS));
                } else {
                	$query = Database::prepare(Database::get(), "
                        SELECT p_u.id, p_u.title, p_u.tags, p_u.public, p_u.star, p.thumbUrl, p.takestamp, p.url, p.medium, 'f' as album_id FROM ? p
                            JOIN ? p_u
                                ON p.id = p_u.photo_id
                        WHERE p_u.user_id = '?' AND p_u.star = 1
                        " . Settings::get()['sortingPhotos'], array(LYCHEE_TABLE_PHOTOS, LYCHEE_TABLE_PHOTOS_USERS, $_SESSION['userid']));
                }
                
				break;

			case 's':
				$return['public'] = '0';
				
                if ($_SESSION['role'] === 'admin') {
                    $query = Database::prepare(Database::get(), "
                        SELECT p_u.id, p_u.title, p_u.tags, p_u.public, p_u.star, p.thumbUrl, p.takestamp, p.url, p.medium, 's' as album_id FROM ? p
                            JOIN ? p_u
                                ON p.id = p_u.photo_id
                        WHERE p_u.public = 1
                        " . Settings::get()['sortingPhotos'], array(LYCHEE_TABLE_PHOTOS, LYCHEE_TABLE_PHOTOS_USERS));
                } else {
                    $query = Database::prepare(Database::get(), "
                        SELECT p_u.id, p_u.title, p_u.tags, p_u.public, p_u.star, p.thumbUrl, p.takestamp, p.url, p.medium, 's' as album_id FROM ? p
                            JOIN ? p_u
                                ON p.id = p_u.photo_id
                        WHERE p_u.user_id = '?' AND p_u.public = 1
                        " . Settings::get()['sortingPhotos'], array(LYCHEE_TABLE_PHOTOS, LYCHEE_TABLE_PHOTOS_USERS, $_SESSION['userid']));
                }

				break;

			case 'r':
				$return['public'] = '0';
                
                if ($_SESSION['role'] === 'admin') {
                    $query = Database::prepare(Database::get(), "
                        SELECT p_u.id, p_u.title, p_u.tags, p_u.public, p_u.star, p.thumbUrl, p.takestamp, p.url, p.medium, 'r' as album_id FROM ? p
                            JOIN ? p_u
                                ON p.id = p_u.photo_id
                        WHERE LEFT(p.id, 10) >= unix_timestamp(DATE_SUB(NOW(), INTERVAL 1 DAY))
                        " . Settings::get()['sortingPhotos'], array(LYCHEE_TABLE_PHOTOS, LYCHEE_TABLE_PHOTOS_USERS));
                } else {
                    $query = Database::prepare(Database::get(), "
                        SELECT p_u.id, p_u.title, p_u.tags, p_u.public, p_u.star, p.thumbUrl, p.takestamp, p.url, p.medium, 'r' as album_id FROM ? p
                            JOIN ? p_u
                                ON p.id = p_u.photo_id
                        WHERE p_u.user_id = '?' AND LEFT(p.id, 10) >= unix_timestamp(DATE_SUB(NOW(), INTERVAL 1 DAY))
                        " . Settings::get()['sortingPhotos'], array(LYCHEE_TABLE_PHOTOS, LYCHEE_TABLE_PHOTOS_USERS, $_SESSION['userid']));
                }
                
				break;

			case 'u':
				$return['public'] = '0';
                
                if ($_SESSION['role'] === 'admin') {
                    $query = Database::prepare(Database::get(), "
                        SELECT p_u.id, p_u.title, p_u.tags, p_u.public, p_u.star, p_u.album_id, p.thumbUrl, p.takestamp, p.url, p.medium, 'u' as album_id FROM ? p_u
                            LEFT JOIN ? p
                                ON p_u.photo_id = p.id
                        WHERE p_u.album_id is NULL
                        " . Settings::get()['sortingPhotos'], array(LYCHEE_TABLE_PHOTOS_USERS, LYCHEE_TABLE_PHOTOS));
                } else {
                    $query = Database::prepare(Database::get(), "
                        SELECT p_u.id, p_u.title, p_u.tags, p_u.public, p_u.star, p_u.album_id, p.thumbUrl, p.takestamp, p.url, p.medium, 'u' as album_id FROM ? p_u
                            LEFT JOIN ? p
                                ON p_u.photo_id = p.id
                        WHERE p_u.user_id = '?' AND p_u.album_id is NULL
                        " . Settings::get()['sortingPhotos'], array(LYCHEE_TABLE_PHOTOS_USERS, LYCHEE_TABLE_PHOTOS, $_SESSION['userid']));
                }
                
				break;

			default:
                $query = '';

                if ((!isset($_SESSION['login']) || $_SESSION['login'] === false) || !isset($_SESSION['identifier']) || !isset($_SESSION['role'])) { // Public
					$query  = Database::prepare(Database::get(), "SELECT * FROM ? WHERE id = '?' AND public = 1 LIMIT 1", array(LYCHEE_TABLE_ALBUMS, $this->albumIDs));
                    $albums = Database::execute(Database::get(), $query, __METHOD__, __LINE__);
                } elseif ($_SESSION['role'] === 'admin') {
					$query  = Database::prepare(Database::get(), "SELECT * FROM ? WHERE id = '?' LIMIT 1", array(LYCHEE_TABLE_ALBUMS, $this->albumIDs));
                    $albums = Database::execute(Database::get(), $query, __METHOD__, __LINE__);
                } else {
                    $query	= Database::prepare(Database::get(), "SELECT * FROM ? WHERE id = '?' AND user_id = '?' LIMIT 1", array(LYCHEE_TABLE_ALBUMS, $this->albumIDs, $_SESSION['userid']));
                    $albums = Database::execute(Database::get(), $query, __METHOD__, __LINE__);
                    
                    if ($albums->num_rows === 0) { // Not an album created by the user, check if album is shared with user
                        $query	= Database::prepare(Database::get(), "
                            SELECT a.*, p.view, p.upload, p.erase FROM ? a
                                JOIN ? p
                                    ON a.id = p.album_id
                            WHERE a.id = '?' and p.user_id = '?' and p.view = '1' LIMIT 1", array(LYCHEE_TABLE_ALBUMS, LYCHEE_TABLE_PRIVILEGES, $this->albumIDs, $_SESSION['userid']));
                        $albums = Database::execute(Database::get(), $query, __METHOD__, __LINE__);
                    }
                }

				$return = $albums->fetch_assoc();
				$return = Album::prepareData($return);
				$query  = Database::prepare(Database::get(), "
                    SELECT p_u.id, p_u.title, p_u.tags, p_u.public, p_u.star, p_u.album_id, p.thumbUrl, p.takestamp, p.url, p.medium FROM ? p_u
                        JOIN ? p
                            ON p_u.photo_id = p.id
                    WHERE p_u.album_id = '?'
                    " . Settings::get()['sortingPhotos'], array(LYCHEE_TABLE_PHOTOS_USERS, LYCHEE_TABLE_PHOTOS, $this->albumIDs));
				break;

		}

		// Get photos
		$photos          = Database::execute(Database::get(), $query, __METHOD__, __LINE__);
		$previousPhotoID = '';

		if ($photos===false) return false;

		while ($photo = $photos->fetch_assoc()) {

			// Turn data from the database into a front-end friendly format
			$photo = Photo::prepareData($photo);

			// Set previous and next photoID for navigation purposes
			$photo['previousPhoto'] = $previousPhotoID;
			$photo['nextPhoto']     = '';

			// Set current photoID as nextPhoto of previous photo
			if ($previousPhotoID!=='') $return['content'][$previousPhotoID]['nextPhoto'] = $photo['id'];
			$previousPhotoID = $photo['id'];

			// Add to return
			$return['content'][$photo['id']] = $photo;

		}

		if ($photos->num_rows===0) {

			// Album empty
			$return['content'] = false;

		} else {

			// Enable next and previous for the first and last photo
			$lastElement    = end($return['content']);
			$lastElementId  = $lastElement['id'];
			$firstElement   = reset($return['content']);
			$firstElementId = $firstElement['id'];

			if ($lastElementId!==$firstElementId) {
				$return['content'][$lastElementId]['nextPhoto']      = $firstElementId;
				$return['content'][$firstElementId]['previousPhoto'] = $lastElementId;
			}

		}

		$return['id']  = $this->albumIDs;
		$return['num'] = $photos->num_rows;

        // Add if album is editable by user (if owned by user)
        if ($_SESSION['role'] === 'admin' || $return['user_id'] === $_SESSION['userid']) {
            $return['editable'] = true;
        } else {
            $return['editable'] = false;
        }

		// Call plugins
		Plugins::get()->activate(__METHOD__, 1, func_get_args());

		return $return;

	}

	/**
	 * Starts a download of an album.
	 * @return resource|boolean Sends a ZIP-file or returns false on failure.
	 */
	public function getArchive() {

		// Check dependencies
		Validator::required(isset($this->albumIDs), __METHOD__);

		// Call plugins
		Plugins::get()->activate(__METHOD__, 0, func_get_args());

		// Illicit chars
		$badChars =	array_merge(
			array_map('chr', range(0,31)),
			array("<", ">", ":", '"', "/", "\\", "|", "?", "*")
		);

		// Photos query
		switch($this->albumIDs) {
			case 's':
				$photos   = Database::prepare(Database::get(), "
                    SELECT p_u.title, p.url
                        FROM ? p
                        JOIN ? p_u ON p.id = p_u.photo_id
                    WHERE p_u.public = 1 AND p_u.user_id = '?'", array(LYCHEE_TABLE_PHOTOS, LYCHEE_TABLE_PHOTOS_USERS, $_SESSION['userid']));
				$zipTitle = 'Public';
				break;
			case 'f':
				$photos   = Database::prepare(Database::get(), "
                    SELECT p_u.title, p.url
                        FROM ? p
                        JOIN ? p_u ON p.id = p_u.photo_id
                    WHERE p_u.star = 1 AND p_u.user_id = '?'", array(LYCHEE_TABLE_PHOTOS, LYCHEE_TABLE_PHOTOS_USERS, $_SESSION['userid']));
				$zipTitle = 'Starred';
				break;
            case 'r': // TODO: fix -> recent photos should be determined per user
				$photos   = Database::prepare(Database::get(), "
                    SELECT p_u.title, p.url
                        FROM ? p
                        JOIN ? p_u ON p.id = p_u.photo_id
                    WHERE LEFT(p_u.id, 10) >= unix_timestamp(DATE_SUB(NOW(), INTERVAL 1 DAY)) GROUP BY checksum AND p_u.user_id = '?'", array(LYCHEE_TABLE_PHOTOS, LYCHEE_TABLE_PHOTOS_USERS, $_SESSION['userid']));
				$zipTitle = 'Recent';
				break;
            case 'u':
				$photos   = Database::prepare(Database::get(), "
                    SELECT p_u.title, p.url
                        FROM ? p
                        JOIN ? p_u ON p.id = p_u.photo_id
                    WHERE p_u.album_id is NULL AND p_u.user_id = '?'", array(LYCHEE_TABLE_PHOTOS, LYCHEE_TABLE_PHOTOS_USERS, $_SESSION['userid']));
				$zipTitle = 'Unsorted';
				break;
			default:
				$photos   = Database::prepare(Database::get(), "
                    SELECT p_u.title, p.url
                        FROM ? p
                        JOIN ? p_u ON p.id = p_u.photo_id
                    WHERE p_u.album_id = '?'", array(LYCHEE_TABLE_PHOTOS, LYCHEE_TABLE_PHOTOS_USERS, $this->albumIDs));
                $zipTitle = 'Untitled';
		}

        $album = null;

		// Get title from database when album is not a SmartAlbum
		if (!in_array($this->albumIDs, array('s', 'f', 'r', 'u')) && is_numeric($this->albumIDs)) {

			$query = Database::prepare(Database::get(), "SELECT title FROM ? WHERE id = '?' LIMIT 1", array(LYCHEE_TABLE_ALBUMS, $this->albumIDs));
			$album = Database::execute(Database::get(), $query, __METHOD__, __LINE__);

			if ($album===false) return false;

			// Get album object
			$album = $album->fetch_object();

			// Album not found?
			if ($album===null) {
				Log::error(Database::get(), __METHOD__, __LINE__, 'Could not find specified album');
				return false;
			}

			// Set title
			$zipTitle = $album->title;

		}

		// Escape title
		$zipTitle = str_replace($badChars, '', $zipTitle);

		$filename = LYCHEE_DATA . $zipTitle . '.zip';

		// Create zip
		$zip = new ZipArchive();
		if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
			Log::error(Database::get(), __METHOD__, __LINE__, 'Could not create ZipArchive');
			return false;
		}

		// Execute query
		$photos = Database::execute(Database::get(), $photos, __METHOD__, __LINE__);

		// Check if album empty
		if ($photos->num_rows==0) {
			Log::error(Database::get(), __METHOD__, __LINE__, 'Could not create ZipArchive without images');
			return false;
		}

		// Parse each path
		$files = array();
		while ($photo = $photos->fetch_object()) {

			// Parse url
			$photo->url = LYCHEE_UPLOADS_BIG . $photo->url;

			// Parse title
			$photo->title = str_replace($badChars, '', $photo->title);
			if (!isset($photo->title)||$photo->title==='') $photo->title = 'Untitled';

			// Check if readable
			if (!@is_readable($photo->url)) continue;

			// Get extension of image
			$extension = getExtension($photo->url, false);

			// Set title for photo
			$zipFileName = $zipTitle . '/' . $photo->title . $extension;

			// Check for duplicates
			if (!empty($files)) {
				$i = 1;
				while (in_array($zipFileName, $files)) {

					// Set new title for photo
					$zipFileName = $zipTitle . '/' . $photo->title . '-' . $i . $extension;

					$i++;

				}
			}

			// Add to array
			$files[] = $zipFileName;

			// Add photo to zip
			$zip->addFile($photo->url, $zipFileName);

		}

		// Finish zip
		$zip->close();

		// Send zip
		header("Content-Type: application/zip");
		header("Content-Disposition: attachment; filename=\"$zipTitle.zip\"");
		header("Content-Length: " . filesize($filename));
		readfile($filename);

		// Delete zip
		unlink($filename);

		// Call plugins
		Plugins::get()->activate(__METHOD__, 1, func_get_args());

		return true;

	}

	/**
	 * @return boolean Returns true when successful.
	 */
	public function setTitle($title = 'Untitled') {

		// Check dependencies
		Validator::required(isset($this->albumIDs), __METHOD__);

		// Call plugins
		Plugins::get()->activate(__METHOD__, 0, func_get_args());

		// Execute query
		$query  = Database::prepare(Database::get(), "UPDATE ? SET title = '?' WHERE id IN (?)", array(LYCHEE_TABLE_ALBUMS, $title, $this->albumIDs));
		$result = Database::execute(Database::get(), $query, __METHOD__, __LINE__);

		// Call plugins
		Plugins::get()->activate(__METHOD__, 1, func_get_args());

		if ($result===false) return false;
		return true;

	}

	/**
	 * @return boolean Returns true when successful.
	 */
	public function setDescription($description = '') {

		// Check dependencies
		Validator::required(isset($this->albumIDs), __METHOD__);

		// Call plugins
		Plugins::get()->activate(__METHOD__, 0, func_get_args());

		// Execute query
		$query  = Database::prepare(Database::get(), "UPDATE ? SET description = '?' WHERE id IN (?)", array(LYCHEE_TABLE_ALBUMS, $description, $this->albumIDs));
		$result = Database::execute(Database::get(), $query, __METHOD__, __LINE__);

		// Call plugins
		Plugins::get()->activate(__METHOD__, 1, func_get_args());

		if ($result===false) return false;
		return true;

	}

	/**
	 * @return boolean Returns true when the album is public.
	 */
	public function getPublic() {

		// Check dependencies
		Validator::required(isset($this->albumIDs), __METHOD__);

		// Call plugins
		Plugins::get()->activate(__METHOD__, 0, func_get_args());

		if ($this->albumIDs==='u' || $this->albumIDs==='s' || $this->albumIDs==='f') return false;

		// Execute query
		$query  = Database::prepare(Database::get(), "SELECT public FROM ? WHERE id = '?' LIMIT 1", array(LYCHEE_TABLE_ALBUMS, $this->albumIDs));
		$albums = Database::execute(Database::get(), $query, __METHOD__, __LINE__);

		if ($albums===false) return false;

		// Get album object
		$album = $albums->fetch_object();

		// Album not found?
		if ($album===null) {
			Log::error(Database::get(), __METHOD__, __LINE__, 'Could not find specified album');
			return false;
		}

		// Call plugins
		Plugins::get()->activate(__METHOD__, 1, func_get_args());

		if ($album->public==1) return true;
		return false;

	}

	/**
	 * @return boolean Returns true when the album is downloadable.
	 */
	public function getDownloadable() {

		// Check dependencies
		Validator::required(isset($this->albumIDs), __METHOD__);

		// Call plugins
		Plugins::get()->activate(__METHOD__, 0, func_get_args());

		if ($this->albumIDs==='u'||$this->albumIDs==='s'||$this->albumIDs==='f'||$this->albumIDs==='r') return false;

		// Execute query
		$query  = Database::prepare(Database::get(), "SELECT downloadable FROM ? WHERE id = '?' LIMIT 1", array(LYCHEE_TABLE_ALBUMS, $this->albumIDs));
		$albums = Database::execute(Database::get(), $query, __METHOD__, __LINE__);

		if ($albums===false) return false;

		// Get album object
		$album = $albums->fetch_object();

		// Album not found?
		if ($album===null) {
			Log::error(Database::get(), __METHOD__, __LINE__, 'Could not find specified album');
			return false;
		}

		// Call plugins
		Plugins::get()->activate(__METHOD__, 1, func_get_args());

		if ($album->downloadable==1) return true;
		return false;

	}

	/**
	 * @return boolean Returns true when successful.
	 */
	public function setPublic($public, $password, $visible, $downloadable) { // TODO: don't allow if shared with user!

		// Check dependencies
		Validator::required(isset($this->albumIDs), __METHOD__);

		// Call plugins
		Plugins::get()->activate(__METHOD__, 0, func_get_args());

        // Check if album is owned by user
		$query  = Database::prepare(Database::get(), "SELECT 1 FROM ? WHERE id = '?' && user_id = '?' LIMIT 1", array(LYCHEE_TABLE_ALBUMS, $this->albumIDs, $_SESSION['userid']));
		$allowed = Database::execute(Database::get(), $query, __METHOD__, __LINE__);

        if ($allowed->num_rows === 0) {
            Log::error(Database::get(), __METHOD__, __LINE__, 'Tried to change public settings of other users album');
            return false;
        }

		// Convert values
		$public       = ($public==='1' ? 1 : 0);
		$visible      = ($visible==='1' ? 1 : 0);
		$downloadable = ($downloadable==='1' ? 1 : 0);

		// Set public
		$query  = Database::prepare(Database::get(), "UPDATE ? SET public = '?', visible = '?', downloadable = '?', password = NULL WHERE id IN (?)", array(LYCHEE_TABLE_ALBUMS, $public, $visible, $downloadable, $this->albumIDs));
		$result = Database::execute(Database::get(), $query, __METHOD__, __LINE__);

		if ($result===false) return false;

		// Reset permissions for photos
		if ($public===0) {

			$query  = Database::prepare(Database::get(), "
                UPDATE ? p_u
                SET p_u.public = 0 WHERE p_u.album_id IN (?) and p_u.user_id = '?'", array(LYCHEE_TABLE_PHOTOS_USERS, $this->albumIDs, $_SESSION['userid']));
			$result = Database::execute(Database::get(), $query, __METHOD__, __LINE__);

			if ($result===false) return false;

		}

		// Call plugins
		Plugins::get()->activate(__METHOD__, 1, func_get_args());

		// Set password
		if (isset($password)&&strlen($password)>0) return $this->setPassword($password);
		return true;

	}

	/**
	 * @return boolean Returns true when successful.
	 */
	private function setPassword($password) {

		// Check dependencies
		Validator::required(isset($this->albumIDs), __METHOD__);

		// Call plugins
		Plugins::get()->activate(__METHOD__, 0, func_get_args());

		if (strlen($password)>0) {

			// Get hashed password
			$password = getHashedString($password);

			// Set hashed password
			// Do not prepare $password because it is hashed and save
			// Preparing (escaping) the password would destroy the hash
			$query = Database::prepare(Database::get(), "UPDATE ? SET password = '$password' WHERE id IN (?)", array(LYCHEE_TABLE_ALBUMS, $this->albumIDs));

		} else {

			// Unset password
			$query = Database::prepare(Database::get(), "UPDATE ? SET password = NULL WHERE id IN (?)", array(LYCHEE_TABLE_ALBUMS, $this->albumIDs));

		}

		// Execute query
		$result = Database::execute(Database::get(), $query, __METHOD__, __LINE__);

		// Call plugins
		Plugins::get()->activate(__METHOD__, 1, func_get_args());

		if ($result===false) return false;
		return true;

	}

	/**
	 * @return boolean Returns when album is public.
	 */
	public function checkPassword($password) {

		// Check dependencies
		Validator::required(isset($this->albumIDs), __METHOD__);

		// Call plugins
		Plugins::get()->activate(__METHOD__, 0, func_get_args());

		// Execute query
		$query  = Database::prepare(Database::get(), "SELECT password FROM ? WHERE id = '?' LIMIT 1", array(LYCHEE_TABLE_ALBUMS, $this->albumIDs));
		$albums = Database::execute(Database::get(), $query, __METHOD__, __LINE__);

		if ($albums===false) return false;

		// Get album object
		$album = $albums->fetch_object();

		// Album not found?
		if ($album===null) {
			Log::error(Database::get(), __METHOD__, __LINE__, 'Could not find specified album');
			return false;
		}

		// Call plugins
		Plugins::get()->activate(__METHOD__, 1, func_get_args());

		// Check if password is correct
		if ($album->password=='') return true;
		if ($album->password===crypt($password, $album->password)) return true;
		return false;

	}

	/**
	 * @return boolean Returns true when successful.
	 */
	public function merge() {

		// Check dependencies
		Validator::required(isset($this->albumIDs), __METHOD__);

		// Call plugins
		Plugins::get()->activate(__METHOD__, 0, func_get_args());

		// Convert to array
		$albumIDs = explode(',', $this->albumIDs);

		// Get first albumID
		$albumID = array_splice($albumIDs, 0, 1);
		$albumID = $albumID[0];

		$query  = Database::prepare(Database::get(), "UPDATE ? SET album_id = ? WHERE album_id IN (?)", array(LYCHEE_TABLE_PHOTOS_USERS, $albumID, $this->albumIDs));
		$result = Database::execute(Database::get(), $query, __METHOD__, __LINE__);

		if ($result===false) return false;

		// $albumIDs contains all IDs without the first albumID
		// Convert to string
		$filteredIDs = implode(',', $albumIDs);

        $queries = array();

		array_push($queries, Database::prepare(Database::get(), "DELETE FROM ? WHERE album_id IN (?)", array(LYCHEE_TABLE_PRIVILEGES, $filteredIDs)));

        array_push($queries, Database::prepare(Database::get(), "DELETE FROM ? WHERE id IN (?)", array(LYCHEE_TABLE_ALBUMS, $filteredIDs)));

        // Execute transaction
		$result = Database::executeTransaction(Database::get(), $queries, __METHOD__, __LINE__);

		// Call plugins
		Plugins::get()->activate(__METHOD__, 1, func_get_args());

		if ($result===false) return false;
		return true;

	}

	/**
	 * @return boolean Returns true when successful.
	 */
	public function delete() {

		// Check dependencies
		Validator::required(isset($this->albumIDs), __METHOD__);

		// Call plugins
		Plugins::get()->activate(__METHOD__, 0, func_get_args());

		// Init vars
		$photoIDs = array();

		// Execute query
		$query  = Database::prepare(Database::get(), "SELECT id FROM ? p_u WHERE p_u.album_id IN (?)", array(LYCHEE_TABLE_PHOTOS_USERS, $this->albumIDs));
		$photos = Database::execute(Database::get(), $query, __METHOD__, __LINE__);

		if ($photos===false) return false;

		// Only delete photos when albums contain photos
		if ($photos->num_rows>0) {

			// Add each id to photoIDs
			while ($row = $photos->fetch_object()) $photoIDs[] = $row->id;

			// Convert photoIDs to a string
			$photoIDs = implode(',', $photoIDs);

			// Delete all photos
			$photo = new Photo($photoIDs);
			if ($photo->delete()!==true) return false;

		}

		// Delete albums
		$query  = Database::prepare(Database::get(), "DELETE FROM ? WHERE id IN (?)", array(LYCHEE_TABLE_ALBUMS, $this->albumIDs));
		$result = Database::execute(Database::get(), $query, __METHOD__, __LINE__);

		// Call plugins
		Plugins::get()->activate(__METHOD__, 1, func_get_args());

		if ($result===false) return false;
		return true;

	}

}

?>
