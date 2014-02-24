<?php

class Database {
	private $name = 'database_name';
	private $user = 'database_user';
	private $pass = '1234567890';

	public $db;

	public function __construct(){
		$format = 'mysql:host=localhost;dbname=%s;charset=utf8';
		$this->db = new PDO(sprintf($format, $this->name), $this->user, $this->pass);
	}
}
