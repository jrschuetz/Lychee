<?php
namespace Lychee\Modules;

final class Users {

  public function get($username){
      
      if($username !== ''){
        $query = Database::prepare(Database::get(), "SELECT name, role, id FROM ? WHERE name = '?'", array(LYCHEE_TABLE_USERS, $username));
      }
      else{
        $query = Database::prepare(Database::get(), "SELECT name, role, id FROM ? ORDER BY role ", array(LYCHEE_TABLE_USERS));
      }
      $result = Database::execute(Database::get(), $query, __METHOD__, __LINE__);

      $data = array();
      while($row = $result->fetch_assoc()){
        $data[] = $row;
      }
      return json_encode($data);
  }
  public function addUser($username, $password, $role){
      if ($_SESSION['role'] === 'admin') {
          # Check if user already exists
          $query = Database::prepare(Database::get(), "SELECT * FROM ? WHERE name = '?'", array(LYCHEE_TABLE_USERS, $username));
          $result = Database::execute(Database::get(), $query, __METHOD__, __LINE__);
          if($result->num_rows > 0){
              Log::warning(Database::get(), __METHOD__, __LINE__, "User: ". $username . " already exists");
              exit("User already exists");
          }
          
          # Hash password
          $pwhash = password_hash($password, PASSWORD_BCRYPT);
          
          # Insert in database
          # Do not prepare pwhash, since the escaping would
          # destroy it
          $query = Database::prepare(Database::get(), "INSERT INTO ? (name, pwhash, role) VALUES ('?', '". $pwhash ."', '?')", array(LYCHEE_TABLE_USERS, $username, $role));
          $result = Database::execute(Database::get(), $query, __METHOD__, __LINE__);
          if(!$result){
              Log::error(Database::get(), __METHOD__, __LINE__, "Failed to create user: " . $database->error);
              return false;
          }
          return true;
      } else {
          return false;
      }
  }
  
  public function deleteUser($username){
      if ($_SESSION['role'] === 'admin') {
          # Check if user exists
          $query = Database::prepare(Database::get(), "SELECT * FROM ? WHERE name = '?'", array(LYCHEE_TABLE_USERS, $username));
          $result = Database::execute(Database::get(), $query, __METHOD__, __LINE__);
          if($result->num_rows === 0){
              Log::warning(Database::get(), __METHOD__, __LINE__, "User: ". $username . " does not exists");
              exit("User does not exists");
          }
          
          # Delete from database
          $query = Database::prepare(Database::get(), "DELETE FROM ? WHERE name = '?'", array(LYCHEE_TABLE_USERS, $username));
          $result = Database::execute(Database::get(), $query, __METHOD__, __LINE__);
          if(!$result){
              Log::error(Database::get(), __METHOD__, __LINE__, "Failed to delete user: " . $database->error);
              return false;
          }
          return true;
      } else {
          return false;
      }
  }

  public function checkLogin($username, $password){

      # Check credentials
      $query = Database::prepare(Database::get(), "SELECT * FROM ? WHERE name = '?'", array(LYCHEE_TABLE_USERS, $username));
      $result = Database::execute(Database::get(), $query, __METHOD__, __LINE__);
      if($result->num_rows !== 1){
        Log::error(Database::get(), __METHOD__, __LINE__, "Unknown username: ". $username ." tried to login");
        return false;
      }
      $user = $result->fetch_object();

      # Check the password
      if (password_verify($password, $user->pwhash)){
        return array('role' => $user->role, 'userid' => $user->id);
      }
      return false; 
  }

  public function changePassword($username, $oldPassword, $newpassword, $newPwRepeat){

      if($newpassword !== $newPwRepeat){
         return false;
      }

      # Check credentials
      $query = Database::prepare(Database::get(), "SELECT * FROM ? WHERE name = '?'", array(LYCHEE_TABLE_USERS, $username));
      $result = Database::execute(Database::get(), $query, __METHOD__, __LINE__);
      if($result->num_rows !== 1){
        Log::error(Database::get(), __METHOD__, __LINE__, "Multiple users with the same name exists...");
        return false;
      }
      $user = $result->fetch_object();

      # Check the password
      if (password_verify($oldPassword, $user->pwhash)){

          $pwhash = password_hash($newpassword, PASSWORD_BCRYPT);

          # Contruct the update query 
          $query = Database::prepare(Database::get(), "UPDATE ? SET pwhash = '" . $pwhash . "' WHERE name = '?'", array(LYCHEE_TABLE_USERS, $username));
          $result = Database::execute(Database::get(), $query, __METHOD__, __LINE__);
          if(!$query){
            Log::error(Database::get(), __METHOD__, __LINE__, "Failed to change password");
            return false;
          }

          return true;
      }
      return false; 
  }

  public function getPrivileges($userid){
      if ($_SESSION['role'] === 'admin') {
          $query = Database::prepare(Database::get(), "SELECT a.id, a.title, p.view, p.upload, p.erase FROM ? a LEFT JOIN ? p ON a.id = p.album_id AND p.user_id = ?", array(LYCHEE_TABLE_ALBUMS, LYCHEE_TABLE_PRIVILEGES, $userid));
          
          Log::error(Database::get(), __METHOD__, __LINE__, "test" . $query);
          
          $result = Database::execute(Database::get(), $query, __METHOD__, __LINE__);
          if(!$result){
              Log::error(Database::get(), __METHOD__, __LINE__, "Failed to get privileges");
              return false;
          }
          
          $data = array();
          while($row = $result->fetch_assoc()){
            $data[] = $row;
          }
          
          return $data;
      } else {
          return false;
      }
  }

  // TODO: allow users with 'user' role to change permissions of shared albums
  public function changePrivileges($userid, $albumid, $privilege , $state){
      if ($_SESSION['role'] === 'admin') {
          # This cleans read an write input
          $field = 0;
          switch($privilege){
            case '0': $field = 'view'; break;
            case '1': $field = 'upload'; break;
            case '2': $field = 'erase'; break;
          }
          $state = $state ? 1 : 0;
          
          $query = Database::prepare(Database::get(), "INSERT INTO ? (`user_id`, `album_id`, `?`) VALUES ('?', '?','?') ON DUPLICATE KEY UPDATE `?`='?';", array(LYCHEE_TABLE_PRIVILEGES, $field, $userid, $albumid, $state, $field, $state));
          
          Log::error(Database::get(), __METHOD__, __LINE__, "test" . $query);
          
          $result = Database::execute(Database::get(), $query, __METHOD__, __LINE__);
          if(!$result){
              Log::error(Database::get(), __METHOD__, __LINE__, "Failed to insert privilege");
              return false;
          }
          return true;
      } else {
          return false;
      }
  }

}

?>
