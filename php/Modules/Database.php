<?php
namespace Lychee\Modules;
use Mysqli;
final class Database {
	private $connection = null;
	private static $instance = null;
	private static $versions = array(
		'030000', // 3.0.0
		'030001', // 3.0.1
		'030003', // 3.0.3
		'030100', // 3.1.0
		'030102', // 3.1.2
        '030103'  // 3.1.3
	);
	/**
	 * @return object Returns a new or cached connection.
	 */
	public static function get() {
		if (!self::$instance) {
			$credentials = Config::get();
			self::$instance = new self(
				$credentials['host'],
				$credentials['user'],
				$credentials['password'],
				$credentials['name'],
				$credentials['prefix']
			);
		}
		return self::$instance->connection;
	}
	/**
	 * Exits on error.
	 * @return boolean Returns true when successful.
	 */
	private function __construct($host, $user, $password, $name = 'lychee', $dbTablePrefix) {
		// Check dependencies
		Validator::required(isset($host, $user, $password, $name), __METHOD__);
		// Define the table prefix
		defineTablePrefix($dbTablePrefix);
		// Open a new connection to the MySQL server
		$connection = self::connect($host, $user, $password);
		// Check if the connection was successful
		if ($connection===false) Response::error(self::connect_error());

		if (self::setCharset($connection)===false) Response::error('Could not set database charset!');
		// Create database
		if (self::createDatabase($connection, $name)===false) Response::error('Could not create database!');
		// Create tables
		if (self::createTables($connection)===false) Response::error('Could not create tables!');
		// Update database
		if (self::update($connection, $name)===false) Response::error('Could not update database and tables!');
		$this->connection = $connection;
		return true;
	}
	/**
	 * @return object|false Returns the connection when successful.
	 */
	public static function connect($host = 'localhost', $user, $password) {
		// Open a new connection to the MySQL server
		$connection = @new Mysqli($host, $user, $password);
		// Check if the connection was successful
		if ($connection->connect_errno) return false;
		return $connection;
	}
	/**
	 * @return string Returns the string description of the last connect error
	 */
	private static function connect_error() {

		return mysqli_connect_error();

	}

	/**
	 * @return boolean Returns true when successful.
	 */
	private static function setCharset($connection) {
		// Check dependencies
		Validator::required(isset($connection), __METHOD__);
		// Avoid sql injection on older MySQL versions by using GBK
		if ($connection->server_version<50500) @$connection->set_charset('GBK');
		else @$connection->set_charset('utf8');
		// Set unicode
		$query  = 'SET NAMES utf8';
		$result = self::execute($connection, $query, null, null);
		if ($result===false) return false;
		return true;
	}
	/**
	 * @return boolean Returns true when successful.
	 */
	public static function createDatabase($connection, $name = 'lychee') {
		// Check dependencies
		Validator::required(isset($connection), __METHOD__);
		// Check if database exists
		if ($connection->select_db($name)===true) return true;
		// Create database
		$query  = self::prepare($connection, 'CREATE DATABASE IF NOT EXISTS ?', array($name));
		$result = self::execute($connection, $query, null, null);
		if ($result===false) return false;
		if ($connection->select_db($name)===false) return false;
		return true;
	}
	/**
	 * @return boolean Returns true when successful.
	 */
	private static function createTables($connection) {
		// Check dependencies
		Validator::required(isset($connection), __METHOD__);
		// Check if tables exist
		$query  = self::prepare($connection, 'SELECT * FROM ?, ?, ?, ?, ?, ?, ? LIMIT 0', array(LYCHEE_TABLE_PHOTOS, LYCHEE_TABLE_ALBUMS, LYCHEE_TABLE_SETTINGS, LYCHEE_TABLE_LOG, LYCHEE_TABLE_SESSIONS, LYCHEE_TABLE_USERS, LYCHEE_TABLE_PRIVILEGES));
		$result = self::execute($connection, $query, null, null);
		if ($result!==false) return true;
		// Check if log table exists
		$exist  = self::prepare($connection, 'SELECT * FROM ? LIMIT 0', array(LYCHEE_TABLE_LOG));
		$result = self::execute($connection, $exist, null, null);
		if ($result===false) {
			// Read file
			$file  = __DIR__ . '/../database/log_table.sql';
			$query = @file_get_contents($file);
			if ($query===false) return false;
			// Create table
			$query  = self::prepare($connection, $query, array(LYCHEE_TABLE_LOG));
			$result = self::execute($connection, $query, null, null);
			if ($result===false) return false;
		}
		// Check if settings table exists
		$exist  = self::prepare($connection, 'SELECT * FROM ? LIMIT 0', array(LYCHEE_TABLE_SETTINGS));
		$result = self::execute($connection, $exist, __METHOD__, __LINE__);
		if ($result===false) {
			// Read file
			$file  = __DIR__ . '/../database/settings_table.sql';
			$query = @file_get_contents($file);
			if ($query===false) {
				Log::error($connection, __METHOD__, __LINE__, 'Could not load query for lychee_settings');
				return false;
			}
			// Create table
			$query  = self::prepare($connection, $query, array(LYCHEE_TABLE_SETTINGS));
			$result = self::execute($connection, $query, __METHOD__, __LINE__);
			if ($result===false) return false;
			// Read file
			$file  = __DIR__ . '/../database/settings_content.sql';
			$query = @file_get_contents($file);
			if ($query===false) {
				Log::error($connection, __METHOD__, __LINE__, 'Could not load content-query for lychee_settings');
				return false;
			}
			// Add content
			$query  = self::prepare($connection, $query, array(LYCHEE_TABLE_SETTINGS));
			$result = self::execute($connection, $query, __METHOD__, __LINE__);
			if ($result===false) return false;
			// Generate identifier
			$identifier = md5(microtime(true));
			$query      = self::prepare($connection, "UPDATE `?` SET `value` = '?' WHERE `key` = 'identifier' LIMIT 1", array(LYCHEE_TABLE_SETTINGS, $identifier));
			$result     = self::execute($connection, $query, __METHOD__, __LINE__);
			if ($result===false) return false;
		}
		// Check if albums table exists
		$exist  = self::prepare($connection, 'SELECT * FROM ? LIMIT 0', array(LYCHEE_TABLE_ALBUMS));
		$result = self::execute($connection, $exist, __METHOD__, __LINE__);
		if ($result===false) {
			// Read file
			$file  = __DIR__ . '/../database/albums_table.sql';
			$query = @file_get_contents($file);
			if ($query===false) {
				Log::error($connection, __METHOD__, __LINE__, 'Could not load query for lychee_albums');
				return false;
			}
			// Create table
			$query  = self::prepare($connection, $query, array(LYCHEE_TABLE_ALBUMS));
			$result = self::execute($connection, $query, __METHOD__, __LINE__);
			if ($result===false) return false;
		}
		// Check if photos table exists
		$exist  = self::prepare($connection, 'SELECT * FROM ? LIMIT 0', array(LYCHEE_TABLE_PHOTOS));
		$result = self::execute($connection, $exist, __METHOD__, __LINE__);
		if ($result===false) {
			// Read file
			$file  = __DIR__ . '/../database/photos_table.sql';
			$query = @file_get_contents($file);
			if ($query===false) {
				Log::error($connection, __METHOD__, __LINE__, 'Could not load query for lychee_photos');
				return false;
			}
			// Create table
			$query  = self::prepare($connection, $query, array(LYCHEE_TABLE_PHOTOS));
			$result = self::execute($connection, $query, __METHOD__, __LINE__);
			if ($result===false) return false;
		}
        // TODO: create these .sql files
		// Check if sessions table exists
		$exist  = self::prepare($connection, 'SELECT * FROM ? LIMIT 0', array(LYCHEE_TABLE_SESSIONS));
		$result = self::execute($connection, $exist, __METHOD__, __LINE__);
		if ($result===false) {
			// Read file
			$file  = __DIR__ . '/../database/sessions_table.sql';
			$query = @file_get_contents($file);
			if ($query===false) {
				Log::error($connection, __METHOD__, __LINE__, 'Could not load query for lychee_sessions');
				return false;
			}
			// Create table
			$query  = self::prepare($connection, $query, array(LYCHEE_TABLE_SESSIONS));
			$result = self::execute($connection, $query, __METHOD__, __LINE__);
			if ($result===false) return false;
		}

        // Check if users table exists
		$exist  = self::prepare($connection, 'SELECT * FROM ? LIMIT 0', array(LYCHEE_TABLE_USERS));
		$result = self::execute($connection, $exist, __METHOD__, __LINE__);
		if ($result===false) {
			// Read file
			$file  = __DIR__ . '/../database/users_table.sql';
			$query = @file_get_contents($file);
			if ($query===false) {
				Log::error($connection, __METHOD__, __LINE__, 'Could not load query for lychee_users');
				return false;
			}
			// Create table
			$query  = self::prepare($connection, $query, array(LYCHEE_TABLE_USERS));
			$result = self::execute($connection, $query, __METHOD__, __LINE__);
			if ($result===false) return false;
		}

        // Check if privileges table exists
		$exist  = self::prepare($connection, 'SELECT * FROM ? LIMIT 0', array(LYCHEE_TABLE_PRIVILEGES));
		$result = self::execute($connection, $exist, __METHOD__, __LINE__);
		if ($result===false) {
			// Read file
			$file  = __DIR__ . '/../database/privileges_table.sql';
			$query = @file_get_contents($file);
			if ($query===false) {
				Log::error($connection, __METHOD__, __LINE__, 'Could not load query for lychee_privileges');
				return false;
			}
			// Create table
			$query  = self::prepare($connection, $query, array(LYCHEE_TABLE_PRIVILEGES));
			$result = self::execute($connection, $query, __METHOD__, __LINE__);
			if ($result===false) return false;
		}

        // Add the privileges table connections
        $file  = __DIR__ . '/../database/privileges_table_connect.sql';

        $query = @file_get_contents($file);
        if ($query===false) {
        	Log::error($connection, __METHOD__, __LINE__, 'Could not load query for lychee_privileges_table_connect');
			return false;
        }
        // Create connections
        $query  = self::prepare($connection, $query, array(LYCHEE_TABLE_PRIVILEGES, LYCHEE_TABLE_ALBUMS, LYCHEE_TABLE_USERS));
        $result = self::execute($connection, $query, __METHOD__, __LINE__);
        if ($result===false) return false;

        // Check if photos_users table exists
		$exist  = self::prepare($connection, 'SELECT * FROM ? LIMIT 0', array(LYCHEE_TABLE_PHOTOS_USERS));
		$result = self::execute($connection, $exist, __METHOD__, __LINE__);
		if ($result===false) {
			// Read file
			$file  = __DIR__ . '/../database/photos_users_table.sql';
			$query = @file_get_contents($file);
			if ($query===false) {
				Log::error($connection, __METHOD__, __LINE__, 'Could not load query for lychee_photos_users');
				return false;
			}
			// Create table
			$query  = self::prepare($connection, $query, array(LYCHEE_TABLE_PHOTOS_USERS));
			$result = self::execute($connection, $query, __METHOD__, __LINE__);
			if ($result===false) return false;
		}
        
        // Add the photos_users table connections
        $file  = __DIR__ . '/../database/photos_users_table_connect.sql';
        $query = @file_get_contents($file);
        if ($query===false) {
        	Log::error($connection, __METHOD__, __LINE__, 'Could not load query for lychee_photos_users_table_connect');
			return false;
        }
        // Create connections
        $query  = self::prepare($connection, $query, array(LYCHEE_TABLE_PHOTOS_USERS, LYCHEE_TABLE_PHOTOS, LYCHEE_TABLE_USERS));
        $result = self::execute($connection, $query, __METHOD__, __LINE__);
        if ($result===false) return false;

        // Check if photos_users table exists
		$exist  = self::prepare($connection, 'SELECT * FROM ? LIMIT 0', array(LYCHEE_TABLE_PHOTOS_ALBUMS));
		$result = self::execute($connection, $exist, __METHOD__, __LINE__);
		if ($result===false) {
			// Read file
			$file  = __DIR__ . '/../database/photos_albums_table.sql';
			$query = @file_get_contents($file);
			if ($query===false) {
				Log::error($connection, __METHOD__, __LINE__, 'Could not load query for lychee_photos_albums');
				return false;
			}
			// Create table
			$query  = self::prepare($connection, $query, array(LYCHEE_TABLE_PHOTOS_ALBUMS));
			$result = self::execute($connection, $query, __METHOD__, __LINE__);
			if ($result===false) return false;
		}

        // Add the photos_albums table connections
        $file  = __DIR__ . '/../database/photos_albums_table_connect.sql';
        $query = @file_get_contents($file);
        if ($query===false) {
        	Log::error($connection, __METHOD__, __LINE__, 'Could not load query for lychee_photos_albums_table_connect');
			return false;
        }
        // Create connections
        $query  = self::prepare($connection, $query, array(LYCHEE_TABLE_PHOTOS_ALBUMS, LYCHEE_TABLE_PHOTOS, LYCHEE_TABLE_ALBUMS));
        $result = self::execute($connection, $query, __METHOD__, __LINE__);
        if ($result===false) return false;

		return true;
	}
	/**
	 * Exits when an update fails.
	 * @return boolean Returns true when successful.
	 */
	private static function update($connection, $dbName) {
		// Check dependencies
		Validator::required(isset($connection, $dbName), __METHOD__);
		// Get current version
		$query  = self::prepare($connection, "SELECT * FROM ? WHERE `key` = 'version'", array(LYCHEE_TABLE_SETTINGS));
		$result = self::execute($connection, $query, __METHOD__, __LINE__);
		if ($result===false) return false;
		// Extract current version
		$current = $result->fetch_object()->value;
		// For each update
		foreach (self::$versions as $version) {
			// Only update when newer version available
			if ($version<=$current) continue;
			// Load update
			include(__DIR__ . '/../database/update_' . $version . '.php');
		}
		return true;
	}
	/**
	 * @return boolean Returns true when successful.
	 */
	public static function setVersion($connection, $version) {
		// Check dependencies
		Validator::required(isset($connection), __METHOD__);
		$query  = self::prepare($connection, "UPDATE ? SET value = '?' WHERE `key` = 'version'", array(LYCHEE_TABLE_SETTINGS, $version));
		$result = self::execute($connection, $query, __METHOD__, __LINE__);
		if ($result===false) return false;
		return true;
	}
	/**
	 * @return string Returns a escaped query.
	 */
	public static function prepare($connection, $query, array $data) {
		// Check dependencies
		Validator::required(isset($connection, $query), __METHOD__);
		// Count the number of placeholders and compare it with the number of arguments
		// If it doesn't match, calculate the difference and skip this number of placeholders before starting the replacement
		// This avoids problems with placeholders in user-input
		// $skip = Number of placeholders which need to be skipped
		$skip = 0;
		$temp = '';
		$num  = array(
			'placeholder' => substr_count($query, '?'),
			'data'        => count($data)
		);
		if (($num['data']-$num['placeholder'])<0) Log::notice($connection, __METHOD__, __LINE__, 'Could not completely prepare query. Query has more placeholders than values.');

		foreach ($data as $value)  {
			// Escape
			$value = mysqli_real_escape_string($connection, $value);
			// Recalculate number of placeholders
			$num['placeholder'] = substr_count($query, '?');
			// Calculate number of skips
			if ($num['placeholder']>$num['data']) $skip = $num['placeholder'] - $num['data'];
			if ($skip>0) {
				// Need to skip $skip placeholders, because the user input contained placeholders
				// Calculate a substring which does not contain the user placeholders
				// 1 or -1 is the length of the placeholder (placeholder = ?)
				$pos = -1;
				for ($i=$skip; $i>0; $i--) $pos = strpos($query, '?', $pos + 1);
				$pos++;
				$temp  = substr($query, 0, $pos); // First part of $query
				$query = substr($query, $pos); // Last part of $query
			}
			// Put a backslash in front of every character that is part of the regular
			// expression syntax. Avoids a backreference when using preg_replace.
			$value = preg_quote($value);
			// Replace
			$query = preg_replace('/\?/', $value, $query, 1);
			if ($skip>0) {
				// Reassemble the parts of $query
				$query = $temp . $query;
			}
			// Reset skip
			$skip = 0;
			// Decrease number of data elements
			$num['data']--;
		}

		return $query;
	}
	/**
	 * @return object|false Returns the results on success.
	 */
	public static function execute($connection, $query, $function, $line) {
		// Check dependencies
		Validator::required(isset($connection, $query), __METHOD__);
		// Only activate logging when $function and $line is set
		$logging = ($function===null||$line===null ? false : true);
		// Execute query
		$result = $connection->query($query);
		// Check if execution failed
		if ($result===false) {
			if ($logging===true) Log::error($connection, $function, $line, $connection->error);
			return false;
		}
		return $result;
	}
	static function createConfig($host = 'localhost', $user, $password, $name = 'lychee', $prefix = '') {

		# Check dependencies
		Module::dependencies(isset($host, $user, $password, $name));

		$database = new mysqli($host, $user, $password);

		if ($database->connect_errno) return 'Warning: Connection failed!';

		# Check if database exists
		if (!$database->select_db($name)) {

			# Database doesn't exist
			# Check if user can create the database
			$result = Database::createDatabase($database, $name);
			if ($result===false) return 'Warning: Creation failed!';

		}

		# Escape data
		$host		= mysqli_real_escape_string($database, $host);
		$user		= mysqli_real_escape_string($database, $user);
		$password	= mysqli_real_escape_string($database, $password);
		$name		= mysqli_real_escape_string($database, $name);
		$prefix		= mysqli_real_escape_string($database, $prefix);

		# Save config.php
$config = "<?php

###
# @name			Configuration
# @author		Tobias Reich
# @copyright	2015 Tobias Reich
###

if(!defined('LYCHEE')) exit('Error: Direct access is not allowed!');

# Database configuration
\$dbHost = '$host'; # Host of the database
\$dbUser = '$user'; # Username of the database
\$dbPassword = '$password'; # Password of the database
\$dbName = '$name'; # Database name
\$dbTablePrefix = '$prefix'; # Table prefix

?>";

		# Save file
		if (file_put_contents(LYCHEE_CONFIG_FILE, $config)===false) return 'Warning: Could not create file!';

		return true;

	}
}

?>
