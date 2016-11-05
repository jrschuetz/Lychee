<?php

namespace Lychee\Modules;

final class Session {

	/**
	 * Reads and returns information about the Lychee installation.
	 * @return array Returns an array with the login status and configuration.
	 */
	public function init($public = true) {

		// Call plugins
		Plugins::get()->activate(__METHOD__, 0, func_get_args());

		// Return settings
		$return['config'] = Settings::get();

		// Path to Lychee for the server-import dialog
		$return['config']['location'] = LYCHEE;

		// Remove sensitive from response
		unset($return['config']['username']);
		unset($return['config']['password']);
		unset($return['config']['identifier']);

		// Check if login credentials exist and login if they don't
		if ($this->noLogin()===true) {
			$public = false;
			$return['config']['login'] = false;
		} else {
			$return['config']['login'] = true;
		}

		// Clear expired sessions
		$query = Database::prepare(Database::get(), "DELETE FROM ? WHERE expires < UNIX_TIMESTAMP(NOW())", array(LYCHEE_TABLE_SESSIONS));
        Database::execute(Database::get(), $query, __METHOD__, __LINE__);
        
		// Check login with crypted hash
		if(isset( $_COOKIE['SESSION']) && $this->sessionExists($_COOKIE['SESSION']) ){
			$_SESSION['login']		= true;
			$_SESSION['identifier']	= Settings::get()['identifier'];
			$public = false;
		}
 
		if ($public===false) {

			// Logged in
			$return['status'] = LYCHEE_STATUS_LOGGEDIN;

		} else {

			// Logged out
			$return['status'] = LYCHEE_STATUS_LOGGEDOUT;

			// Unset unused vars
			unset($return['config']['skipDuplicates']);
			unset($return['config']['sortingAlbums']);
			unset($return['config']['sortingPhotos']);
			unset($return['config']['dropboxKey']);
			unset($return['config']['login']);
			unset($return['config']['location']);
			unset($return['config']['imagick']);
			unset($return['config']['plugins']);
			unset($return['role']);

		}

		// Call plugins
		Plugins::get()->activate(__METHOD__, 1, func_get_args());

		return $return;

	}

	/**
	 * Sets the session values when username and password correct.
	 * @return boolean Returns true when login was successful.
	 */
	public function login($username, $password) {

		// Call plugins
		Plugins::get()->activate(__METHOD__, 0, func_get_args());

        // Check the login
        $users = new Users(Database::get());

		if ($result = $users->checkLogin($username,$password)) {
				$_SESSION['login']	= true;
				$_SESSION['identifier']	= Settings::get()['identifier'];
				$_SESSION['username']	= $username;
				$_SESSION['userid']	= $result['userid'];
				$_SESSION['role']	= $result['role'];

				$expire = time() + 60 * Settings::get()['sessionLength'];
				$hash = hash("sha1", $expire.Settings::get()['identifier'].$username.$password);
				$query = Database::prepare(Database::get(), "INSERT INTO ? (value, expires) VALUES ('?', ?)", array(LYCHEE_TABLE_SESSIONS, $hash, $expire));
				$result = Database::execute(Database::get(), $query, __METHOD__, __LINE__);

				setcookie("SESSION", $hash, $expire, "/","", false, true);

				return array('role' => $_SESSION['role']);

		}

		// No login
		if ($this->noLogin()===true) return true;

		// Call plugins
		Plugins::get()->activate(__METHOD__, 1, func_get_args());

		// Log failed log in
		Log::error(Database::get(), __METHOD__, __LINE__, 'User (' . $username . ') has tried to log in from ' . $_SERVER['REMOTE_ADDR']);

		return false;

	}

	/**
	 * Sets the session values when no there is no username and password in the database.
	 * @return boolean Returns true when no login was found.
	 */
	private function noLogin() {

		// Check if login credentials exist and login if they don't
        $query = Database::prepare(Database::get(), "SELECT * FROM ?", array(LYCHEE_TABLE_USERS));
        $result = Database::execute(Database::get(), $query, __METHOD__, __LINE__);
		
        if($result->num_rows === 0) {

				$_SESSION['login']      = true;
				$_SESSION['identifier'] = Settings::get()['identifier'];
				$_SESSION['role']	= 'admin';
				return true;
		}

		return false;

	}

	/**
	 * Unsets the session values.
	 * @return boolean Returns true when logout was successful.
	 */
	public function logout() {

		// Call plugins
		Plugins::get()->activate(__METHOD__, 0, func_get_args());

		session_unset();
        
		$query = Database::prepare(Database::get(), "DELETE FROM ? WHERE value = '?'", array(LYCHEE_TABLE_SESSIONS, $_COOKIE['SESSION']));
		Database::execute(Database::get(), $query, __METHOD__, __LINE__);
        Log::info(Database::get(), __METHOD__, __LINE__, "Logged out session " . $_COOKIE['SESSION']);
        
		session_destroy();

		// Call plugins
		Plugins::get()->activate(__METHOD__, 1, func_get_args());

		return true;

	}

	private function sessionExists($sessionId){
	      $query = Database::prepare(Database::get(), "SELECT * FROM ? WHERE value = '?'", array(LYCHEE_TABLE_SESSIONS, $sessionId));
	      $result = Database::execute(Database::get(), $query, __METHOD__, __LINE__);
	      return $result->num_rows === 1;
	}

}

?>