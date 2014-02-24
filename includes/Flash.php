<?php

function insertFlash($array){
	if(isset($_SESSION['flash'])){
		$array['flash'] = $_SESSION['flash'];
		unset($_SESSION['flash']);

		if(isset($_SESSION['flashData'])){
			$array['flash']['data'] = $_SESSION['flashData'];
			unset($_SESSION['flashData']);
		}
	} else {
		if(isset($_SESSION['flashData'])){
			$array['flash'] = array('data' => $_SESSION['flashData']);
			unset($_SESSION['flashData']);
		}
	}

	return $array;
}

function addFlash($type, $value){
	$_SESSION['flash'] = array($type => $value);
}

function addFlashData($data){
	$_SESSION['flashData'] = $data;
}
