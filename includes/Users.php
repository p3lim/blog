<?php

class Users extends Database {
	private function blowfish($string){
		$salt = '$2y$10$' . strtr(base64_encode(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM)), '+', '.');
		return crypt($string, $salt);
	}

	public function exists($username, $password){
		$statement = $this->db->prepare('SELECT password FROM users WHERE name=?');
		$statement->bindValue(1, $username);
		$statement->execute();

		if($statement->rowCount()){
			$result = $statement->fetch();
			$hash = $result['password'];

			if(crypt($password, $hash) === $hash){
				return true;
			}
		}

		return false;
	}

	public function numUsers(){
		$statement = $this->db->prepare('SELECT COUNT(*) FROM users');
		$statement->execute();

		return $statement->rowCount();
	}

	public function register($username, $password){
		$statement = $this->db->prepare('INSERT INTO users SET username=?, password=?');
		$statement->bindValue(1, $username);
		$statement->bindValue(2, $this->blowfish($password));
		$statement->execute();

		return (bool) $statement->rowCount();
	}
}
