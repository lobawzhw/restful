<?php
header('content-type:text/html;charset=utf8');
// print_r($_SERVER);exit;

$pdo = require __DIR__.'/../lib/db.php';
require __DIR__.'/../lib/Users.php';
require __DIR__.'/../lib/Articles.php';
require __DIR__.'/../lib/ErrorCode.php';
// echo $_SERVER['PATH_INFO'];
// echo $_SERVER['REQUEST_METHOD'];

/**
* restful入口处理类
*/
class Restful
{
	private $_users;
	private $_articles;

	/**
	 * 请求的方法
	 * @var 
	 */
	private $_request_method;

	/**
	 * 请求的资源
	 * @var 
	 */
	private $_request_resource;

	/**
	 * 请求的资源ID
	 * @var 
	 */
	private $_request_id;

	/**
	 * 允许被请求的方法
	 * @var 
	 */
	private $_allowed_methods = ['GET', 'POST', 'DELETE', 'PUT', 'OPTIONS'];

	/**
	 * 允许被请求的资源
	 * @var 
	 */
	private $_allowed_resources = ['users', 'articles'];

	/**
	 * api返回状态码
	 * @var 
	 */
	private $status_codes = [
		200 => 'Ok',
		204 => 'On Content',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		404 => 'Server Internal Error',
	];

	
	function __construct($_users, $_articles)
	{
		$this->_users = $_users;
		$this->_articles = $_articles;
	}

	public function run() {		
		$this->_setupRequestMethod();
		$this->_setupRequestResource();
	}

	private function _setupRequestMethod() {
		if (!in_array($_SERVER['REQUEST_METHOD'], $this->_allowed_methods)) {
			throw new Exception("请求方法不被允许", 405);			
		}
		$this->_request_method = $_SERVER['REQUEST_METHOD'];
	}

	private function _setupRequestResource() {
		if (!$_SERVER['PATH_INFO']) {
			throw new Exception("资源不存在", 404);			
		}
		$path_info = explode('/', $_SERVER['PATH_INFO']); 
		$_request_resource = $path_info[1];
		$_request_id = $path_info[2];
		if (!in_array($_request_resource, $this->_allowed_resources)) {
			throw new Exception("资源不存在2", 404);			
		}

		$this->_request_resource = $_request_resource;
		$this->_request_id = $_request_id;

	}

}

$users = new Users($pdo);
$articles = new Articles($pdo);
$restful = new Restful($users, $articles);
$restful->run();