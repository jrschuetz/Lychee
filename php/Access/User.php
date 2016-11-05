<?php
namespace Lychee\Access;
use Lychee\Modules\Album;
use Lychee\Modules\Albums;
use Lychee\Modules\Import;
use Lychee\Modules\Photo;
use Lychee\Modules\Response;
use Lychee\Modules\Session;
use Lychee\Modules\Settings;
use Lychee\Modules\Validator;
use Lychee\Modules\Video;

final class User extends Access {

	public static function init($fn) {

		switch ($fn) {

			// Albums functions
			case 'Albums::get':             self::getAlbumsAction(); break;
			// Album functions
			case 'Album::get':				self::getAlbumAction(); break;

			// Photo functions
			case 'Photo::get':				self::getPhotoAction(); break;
			case 'Photo::setTitle':			self::setPhotoTitleAction(); break;
			case 'Photo::setDescription':	self::setPhotoDescriptionAction(); break;
			case 'Photo::setStar':			self::setPhotoStarAction(); break;
			case 'Photo::setPublic':		self::setPhotoPublicAction(); break;
			case 'Photo::setAlbum':			self::setPhotoAlbumAction(); break;
			case 'Photo::setTags':			self::setPhotoTagsAction(); break;
			case 'Photo::duplicate':		self::duplicatePhotoAction(); break;
			case 'Photo::delete':			self::deletePhotoAction(); break;

			// Add functions
			case 'Photo::add':				self::uploadImageAction(); break;
			case 'Video::add':				self::uploadVideoAction(); break;
			case 'Import::url':				self::importUrlAction(); break;
			case 'Import::server':			self::importServerAction(); break;

			// Search functions
			case 'search':					self::searchAction(); break;

			// Session functions
			case 'Session::init':			self::initAction(); break;
			case 'Session::login':			self::loginAction(); break;
			case 'Session::logout':			self::logoutAction(); break;

			// Settings functions
			case 'Settings::setSorting':	self::setSortingAction(); break;

			// $_GET functions
			case 'Album::getArchive':		self::getAlbumArchiveAction(); break;
			case 'Photo::getArchive':		self::getPhotoArchiveAction(); break;

            // User functions
            case 'Users::changePassword':   self::changePasswordAction(); break;

		}

		self::fnNotFound();

	}

	// Albums functions
	private static function getAlbumsAction() {

		$albums = new Albums();
		Response::json($albums->get(false));

	}

    // Album functions
	private static function getAlbumAction() {

		Validator::required(isset($_POST['albumID']), __METHOD__);
		$album = new Album($_POST['albumID']);
		Response::json($album->get());

	}
	// Photo functions

	private static function getPhotoAction() {

		Validator::required(isset($_POST['photoID'], $_POST['albumID']), __METHOD__);
		$photo = new Photo($_POST['photoID']);
		Response::json($photo->get($_POST['albumID']));

	}

	private static function setPhotoTitleAction() {

		Validator::required(isset($_POST['photoIDs'], $_POST['title']), __METHOD__);
		$photo = new Photo($_POST['photoIDs']);
		Response::json($photo->setTitle($_POST['title']));

	}

	private static function setPhotoDescriptionAction() {


		Validator::required(isset($_POST['photoID'], $_POST['description']), __METHOD__);
		$photo = new Photo($_POST['photoID']);
		Response::json($photo->setDescription($_POST['description']));

	}

	private static function setPhotoStarAction() {

		Validator::required(isset($_POST['photoIDs']), __METHOD__);
		$photo = new Photo($_POST['photoIDs']);
		Response::json($photo->setStar());

	}

	private static function setPhotoPublicAction() {

		Validator::required(isset($_POST['photoID']), __METHOD__);
		$photo = new Photo($_POST['photoID']);
		Response::json($photo->setPublic());

	}

	private static function setPhotoAlbumAction() {

		Validator::required(isset($_POST['photoIDs'], $_POST['albumID']), __METHOD__);
		$photo = new Photo($_POST['photoIDs']);
		Response::json($photo->setAlbum($_POST['albumID']));

	}

	private static function setPhotoTagsAction() {

		Validator::required(isset($_POST['photoIDs'], $_POST['tags']), __METHOD__);
		$photo = new Photo($_POST['photoIDs']);
		Response::json($photo->setTags($_POST['tags']));

	}

	private static function duplicatePhotoAction() {

		Validator::required(isset($_POST['photoIDs']), __METHOD__);
		$photo = new Photo($_POST['photoIDs']);
		Response::json($photo->duplicate());

	}

	private static function deletePhotoAction() {

		Validator::required(isset($_POST['photoIDs']), __METHOD__);
		$photo = new Photo($_POST['photoIDs']);
		Response::json($photo->delete());

	}

	// Add functions

	private static function uploadImageAction() {
    
		Validator::required(isset($_FILES, $_POST['albumID']), __METHOD__);
		$photo = new Photo(null);
		Response::json($photo->add($_FILES, $_POST['albumID']), JSON_NUMERIC_CHECK);
        
	}

	private static function uploadVideoAction() {
		
        Validator::required(isset($_FILES, $_POST['albumID']), __METHOD__);
		$video = new Video(null);
		Response::json($video->add($_FILES, $_POST['albumID']), JSON_NUMERIC_CHECK);
        
	}

	private static function importUrlAction() {
	
        Validator::required(isset($_POST['url'], $_POST['albumID']), __METHOD__);
		$import = new Import();
		Response::json($import->url($_POST['url'], $_POST['albumID']));
	
    }

	// Search functions
	private static function searchAction() {
		
        Validator::required(isset($_POST['term']), __METHOD__);
		Response::json(search($_POST['term']));
        
	}

	// Session functions

	private static function initAction() {

    	$session = new Session();
		Response::json($session->init(false));
	
    }


	private static function loginAction() {
    
		Validator::required(isset($_POST['user'], $_POST['password']), __METHOD__);
		$session = new Session();
		Response::json($session->login($_POST['user'], $_POST['password']));

	}

	private static function logoutAction() {
    
		$session = new Session();
		Response::json($session->logout());

	}

	// Settings functions
	private static function setSortingAction() {

		Validator::required(isset($_POST['typeAlbums'], $_POST['orderAlbums'], $_POST['typePhotos'], $_POST['orderPhotos']), __METHOD__);
		$sA = Settings::setSortingAlbums($_POST['typeAlbums'], $_POST['orderAlbums']);
		$sP = Settings::setSortingPhotos($_POST['typePhotos'], $_POST['orderPhotos']);

		if ($sA===true&&$sP===true) Response::json(true);
		else                        Response::json(false);
	
    }
    
	// Get functions
	private static function getAlbumArchiveAction() {

		Validator::required(isset($_GET['albumID']), __METHOD__);
		$album = new Album($_GET['albumID']);
		$album->getArchive();

	}

	private static function getPhotoArchiveAction() {

		Validator::required(isset($_GET['photoID']), __METHOD__);
		$photo = new Photo($_GET['photoID']);
		$photo->getArchive();

	}
    
    private static function changePasswordAction(){
    
        Validator::required(isset($_SESSION['username'], $_POST['oldPassword'], $_POST['newPassword'], $_POST['newPwRepeat']), __METHOD__);
        $users = new Users();
        Response::json($users->changePassword( $_SESSION['username'], $_POST['oldPassword'], $_POST['newPassword'],$_POST['newPwRepeat']));
    
    }

}
