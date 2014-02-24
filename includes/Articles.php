<?php

class Articles extends Database {
	private function validify($title, $content){
		if(empty($title)){
			return 'Missing title';
		} elseif(empty($content)){
			return 'Missing content';
		} elseif(strlen($title) > 30){
			return 'Title is too long';
		}
	}

	public function get($id){
		$statement = $this->db->prepare('SELECT * FROM posts WHERE id=?');
		$statement->bindValue(1, $id);
		$statement->execute();

		if((bool) $statement->rowCount()){
			return array('post' => $statement->fetch(PDO::FETCH_ASSOC));
		}
	}

	public function getAll($page){
		$sql = 'SELECT * FROM posts ORDER BY id DESC LIMIT ';
		$sql .= ($page > 1) ? '?, 10' : '10';

		$statement = $this->db->prepare($sql);

		if($page > 1) $statement->bindValue(1, 10 * max($page - 1, 0));
		$statement->execute();

		if((bool) $statement->rowCount()){
			return array('posts' => $statement->fetchAll(PDO::FETCH_ASSOC));
		}
	}

	public function getFeed(){
		$statement = $this->db->prepare('SELECT * FROM posts ORDER BY id DESC LIMIT 20');
		$statement->execute();

		return array('posts' => $statement->fetchAll(PDO::FETCH_ASSOC));	
	}

	public function getPages(){
		$statement = $this->db->prepare('SELECT COUNT(*) FROM posts');
		$statement->execute();

		return ceil($statement->rowCount() / 10);
	}

	public function create($title, $content){
		$errors = $this->validify($title, $content);
		if(isset($errors)){
			return $errors;
		} else {
			$statement = $this->db->prepare('INSERT INTO posts SET title=?, content=?');
			$statement->bindValue(1, $title);
			$statement->bindValue(2, $content);
			$statement->execute();

			if((bool) $statement->rowCount()){
				return (int) $this->db->lastInsertId();
			} else {
				return 'Something went wrong';
			}
		}
	}

	public function update($id, $title, $content){
		$errors = $this->validify($title, $content);
		if(isset($errors)){
			return $errors;
		} else {
			$statement = $this->db->prepare('UPDATE posts SET title=?, content=? WHERE id=?');
			$statement->bindValue(1, $title);
			$statement->bindValue(2, $content);
			$statement->bindValue(3, $id);
			$statement->execute();

			if((bool) $statement->rowCount()){
				return true;
			} else {
				return 'Something went wrong';
			}
		}
	}

	public function delete($id){
		if(isset($id)){
			$statement = $this->db->prepare('DELETE FROM posts WHERE id=?');
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
}
