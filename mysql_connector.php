<?php

	/* 
	 * MYSQL Connector
	 * created Dmitry Kholostov
	 * https://github.com/dkh-gh/php-lib.mysql_connector
	 */

	include 'config-mysql_connector.php';
	include '../db/db.php';

	// connecting to mysql base
	$connection = connect();
	if(!$connection) 
		ansver('mysql_connect_error');
	else {
		// trying to authorise user
		$user = auth_user();
		if(!$user)
			ansver('user_auth_error');
		else {
			// looking for ask type
			if(!isset($_POST['type']))
				ansver('ask_type_not_set');
			else {
				// executing user query
				$ansv = user_query($_POST['type'], $_POST['data']);
				if(!$ansv)
					ansver('ask_type_not_found');
				else {
					// answering to user with JSON
					ansver($_POST['type']);
				}
			}
		}
	}

	function ansver($ansv) {
		global $conf, $user;
		if(!isset($_POST['timestamp'])) $_POST['timestamp'] = 'NOT_SET';
		if(!isset($GLOBALS['ansv'])) $GLOBALS['ansv'] = 'NOT_SET';
		if(!isset($user['skey'])) $user['skey'] = 'NOT_SET';
		if(!isset($user['id'])) $user['id'] = 'NOT_SET';
		echo str_replace([
			'{{timestamp}}',
			'{{data}}',
			'{{skey}}',
			'{{user_id}}',
		], [
			$_POST['timestamp'],
			json_encode($GLOBALS['ansv']),
			$user['skey'],
			$user['id'],
		], $conf['ansvers'][$ansv]);
	}

	function connect() {
		try {
			$connection = $GLOBALS['_db']['connect']();
		}
		catch(Exception $e) {
			$connection = false;
		}
		return $connection;
	}

	function auth_user() {
		if(isset($_POST['skey'])) {
			$user = $GLOBALS['_db']['get_line'](
				'users',
				'skey',
				$_POST['skey']
			);
			if($user)
				return $user;
			else
				return false;
		}
		else if(isset($_POST['user']) && isset($_POST['passw'])) {
			$user = $GLOBALS['_db']['filter'](
				'users',
				[
					'name' => $_POST['user'],
					'passw' => md5($_POST['passw'])
				]
			);
			if($user)
				return $user[0];
			else
				return false;
		}
		else {
			return false;
		}
	}

	function user_query($ask_type, $data) {
		global $user;
		if(false){}
		elseif($ask_type == 'get_user_data') {
			$ansv = $user;
		}
		elseif($ask_type == 'update_user_dataset') {
			$q = $GLOBALS['_db']['query']("SELECT * FROM `data` WHERE `user_id` = "
				.$user['id']." ORDER BY `timestamp` DESC");
	 		$ret = mysqli_fetch_assoc($q);
	 		$data = json_decode($data, true);
			$q = "UPDATE `data` SET ";
			for($i = 0; $i < count($data['key']); $i++) {
				$q .= "`".$data['key'][$i]."` = '".$data['value'][$i]."', ";
			}
			$q .= "`timestamp` = '".$_POST['timestamp']."' "
				." WHERE `user_id` = '".$user['id']."'"
				." AND `timestamp` = '".$ret['timestamp']."'";
			$q = $GLOBALS['_db']['query']($q);
	 		$ansv = $q;
		}
		elseif($ask_type == 'get_users_list') {
			$ansv = $GLOBALS['_db']['get_lines']('users', '*', '*');
			for ($i=0; $i < count($ansv); $i++) { 
				unset($ansv[$i]['passw']);
				unset($ansv[$i]['skey']);
			}
		}
		elseif($ask_type == 'get_user_dataset') {
			$ansv = $GLOBALS['_db']['get_lines']('data', 'user_id', $_POST['data']);
		}
		elseif($ask_type == 'get_user_dataset_last') {
			$q = $GLOBALS['_db']['query']("SELECT * FROM `data` WHERE `user_id` = "
				.$_POST['data']." ORDER BY `timestamp` DESC");
	 		$ansv = mysqli_fetch_assoc($q);
		}
		else
			$ansv = false;
		return $ansv;
	}

?>