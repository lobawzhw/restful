<?php
// require 'ErrorCode.php';

/**
* 用户操作类
*/
class Users
{
	/**
	 * 数据库连接句柄 - PDO
	 * @var 
	 */
	private $_db;

	function __construct($_db)
	{
		$this->_db = $_db;
	}

	/**
	 * 登录
	 * @param  string $username 用户名
	 * @param  string $password 密码
	 * @return array           
	 */
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
			'id' => $user['id'],
			'username' => $user['username'],
			'createdAt' => $user['createdAt'],
		];
	}

	/**
	 * 注册
	 * @param  string $username 用户名
	 * @param  string $password 密码
	 * @return array
	 */
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
			'id' => $this->_db->lastInsertId(),
			'username' => $username,
			'createdAt' => $createdAt,
		];
	}

	/**
	 * 检查用户名是否存在
	 * @param  string $username 用户名
	 * @param  string $password 密码
	 * @return boolean
	 */
	private function _checkUsernameExist($username, $password) {
		$this->_checkUsernameAndPasswordIsEmpty($username, $password);
		$sql = 'SELECT * FROM `user` WHERE `username`=:username';
		$stmt = $this->_db->prepare($sql);
		$stmt->bindParam(':username', $username);
		$stmt->execute();
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		return !empty($result);
	}

	/**
	 * 检查用户名和密码是否为空
	 * @param  string $username 用户名
	 * @param  string $password 密码
	 * @return boolean
	 */
	private function _checkUsernameAndPasswordIsEmpty($username, $password) {
		if (empty($username)) {
			throw new Exception("用户名不能为空", ErrorCode::USERNAME_CANNOT_EMPTY);			
		}
		if (empty($password)) {
			throw new Exception("密码不能为空", ErrorCode::PASSWORD_CANNOT_EMPTY);			
		}
	}

	/**
	 * md5加密密码
	 * @param  string $str 待加密字符串
	 * @param  string $key 密钥
	 * @return string      
	 */
	private function _md5($str, $key='loba') {
		return md5($str.$key);
	}
}