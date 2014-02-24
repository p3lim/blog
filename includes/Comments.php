<?php

class Comments extends Database {
	public function get($id){
		$statement = $this->db->prepare('SELECT * FROM comments WHERE post=? ORDER BY id ASC');
		$statement->bindValue(1, $id);
		$statement->execute();

		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	public function create($id, $name, $email, $content){
		$email = strtolower(trim($email));
		$ip = $_SERVER['REMOTE_ADDR'];

		if(empty($content)){
			return 'No comment';
		} elseif(strlen($content) > 255){
			return 'Comment exceeded max length';
		} elseif(empty($name)){
			return 'Name is missing';
		} elseif(strlen($name) > 25){
			return 'Name exceeded max length';
		} elseif(empty($email)){
			return 'Email is missing';
		} elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
			return 'Invalid email';
		} elseif(strlen($email) > 50){
			return 'Email exceeded max length';
		} elseif(!isset($ip)){
			return 'Something went wrong';
		}

		if($this->isBanned($email) || $this->isBanned($ip)){
			return 'You\'re banned from commenting';
		}

		$statement = $this->db->prepare('INSERT INTO comments SET post=?, name=?, content=?, email=?, ip=?');
		$statement->bindValue(1, $id);
		$statement->bindValue(2, $name);
		$statement->bindValue(3, $content);
		$statement->bindValue(4, md5($email));
		$statement->bindValue(5, md5($ip));
		$statement->execute();

		if((bool) $statement->rowCount()){
			return true;
		} else {
			return 'Failed to add comment, try again later';
		}
	}

	public function delete($id){
		if(isset($id)){
			$statement = $this->db->prepare('DELETE FROM comments WHERE id=?');
			$statement->bindValue(1, $id);
			$statement->execute();

			if((bool) $statement->rowCount()){
				return true;
			} else {
				return 'Something went wrong';
			}
		} else {
			return 'Missing id';
		}
	}

	public function deleteAll($id){
		if(isset($id)){
			$statement = $this->db->prepare('DELETE FROM comments WHERE post=?');
			$statement->bindValue(1, $id);
			$statement->execute();

			if((bool) $statement->rowCount()){
				return true;
			} else {
				return 'Something went wrong';
			}
		} else {
			return 'Missing id';
		}
	}

	public function isBanned($ident){
		$statement = $this->db->prepare('SELECT ident FROM banned WHERE ident=?');
		$statement->bindValue(1, md5($ident));
		$statement->execute();

		if($statement->rowCount()){
			return true;
		}
	}

	public function ban($id){
		if(isset($id)){
			$statement = $this->db->prepare('SELECT email, ip FROM comments WHERE id=?');
			$statement->bindValue(1, $id);
			$statement->execute();

			if((bool) $statement->rowCount()){
				$result = $statement->fetch(PDO::FETCH_ASSOC);

				$process1 = $this->db->prepare('INSERT INTO banned SET ident=?');
				$process1->bindValue(1, $result['email']);
				$process1->execute();

				if((bool) $process1->rowCount()){
					$process2 = $this->db->prepare('INSERT INTO banned SET ident=?');
					$process2->bindValue(1, $result['ip']);
					$process2->execute();

					if((bool) $process2->rowCount()){
						return true;
					}
				}
			} else {
				return 'Comment doesn\'t exist';
			}
		} else {
			return 'Missing id';
		}

		return 'Something went wrong';
	}
}
