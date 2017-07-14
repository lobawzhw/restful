<?php

/**
* 用户操作类
*/
require 'ErrorCode.php';
class Users
{
	private $_db;

	function __construct($_db)
	{
		$this->_db = $_db;
	}

	public function login($username, $password) {
		$this->_checkUsernameAndPasswordIsEmpty($username, $password);
		$sql = 'SELECT * FROM `user` WHERE `username`=:username AND `password`=:password';
		$stmt = $this->_db->prepare($sql);
		$password = $this->_md5($password);
		$stmt->bindParam(':username', $username);
		$stmt->bindParam(':password', $password);
		$stmt->execute();
		$user = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$user) {
			throw new Exception("账号不存在", ErrorCode::USER_NOT_EXIST);
		}
		return [
			'username' => $user['username'],
			'createdAt' => $user['createdAt'],
		];
	}

	public function register($username, $password) {
		if ($this->_checkUsernameExist($username, $password)) {
			throw new Exception("用户名已存在", ErrorCode::USERNAME_IS_EXIST);			
		}
		$sql = 'INSERT INTO `user`(username, password, createdAt) VALUES(:username, :password, :createdAt)';
		$stmt = $this->_db->prepare($sql);
		$password = $this->_md5($password);
		$createdAt = time();
		$stmt->bindParam(':username', $username);
		$stmt->bindParam(':password', $password);
		$stmt->bindParam(':createdAt', $createdAt);
		if (!$stmt->execute()) {
			throw new Exception("用户注册失败", ErrorCode::REGISTER_FAILED);	
		}
		return [
			'username' => $username,
			'createdAt' => $createdAt,
		];
	}

	private function _checkUsernameExist($username, $password) {
		$this->_checkUsernameAndPasswordIsEmpty($username, $password);
		$sql = 'SELECT * FROM `user` WHERE `username`=:username';
		$stmt = $this->_db->prepare($sql);
		$stmt->bindParam(':username', $username);
		$stmt->execute();
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		return !empty($result);
	}

	private function _checkUsernameAndPasswordIsEmpty($username, $password) {
		if (empty($username)) {
			throw new Exception("用户名不能为空", ErrorCode::USERNAME_CANNOT_EMPTY);			
		}
		if (empty($password)) {
			throw new Exception("密码不能为空", ErrorCode::PASSWORD_CANNOT_EMPTY);			
		}
	}

	private function _md5($str, $key='loba') {
		return md5($str.$key);
	}

	public function getQueryInfoByPdo($username) {
		$sql = 'SELECT * FROM `user` WHERE `username`=:username';
		
		$stmt = $this->_db->prepare($sql);
		$stmt->bindParam(':username', $username);
		$stmt->execute();
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		echo $sql;
		echo '<hr>';
		return $result;
	}

	public function getQueryInfoByMysql($db, $username) {
		$sql = "SELECT * FROM `user` WHERE `username`={$username}";
		$res = mysql_query($sql, $db);
		return   mysql_fetch_array($res);
	}
}