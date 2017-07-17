<?php

header('content-type:text/html;charset=utf8');


$pdo = require __DIR__.'/../lib/db.php';
require __DIR__.'/../lib/Users.php';
require __DIR__.'/../lib/Articles.php';
require __DIR__.'/../lib/ErrorCode.php';
// echo $_SERVER['PATH_INFO'];
// echo $_SERVER['REQUEST_METHOD'];
// echo 111111;exit;
/**
* restful入口处理类
*/
class Restful
{	
	/**
	 * Users类对象	
	 * @var 
	 */
	private $_users;

	/**
	 * Articles类对象	
	 * @var 
	 */
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
	private $_status_codes = [
		200 => 'Ok',
		204 => 'On Content',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		500 => 'Server Internal Error',
	];
	
	/**
	 * 构造方法
	 * @param obj $_users    Users对象
	 * @param obj $_articles Articles对象
	 */
	function __construct($_users, $_articles)
	{
		$this->_users = $_users;
		$this->_articles = $_articles;		
	}

	/**
	 * restful执行入口方法
	 * @return json 
	 */
	public function run() {		
		try {
			$this->_setupRequestMethod();
			$this->_setupRequestResource();
			if ($this->_request_resource=='users') {
				$this->_json($this->_handleUser());
			} elseif ($this->_request_resource=='articles') {
				$this->_json($this->_handleArticle());
			}
		} catch (Exception $e) {
			$this->_json(['error'=>$e->getMessage()], $e->getCode());	
		}
	}

	/**
	 * 初始化请求方法
	 * @return  void
	 */
	private function _setupRequestMethod() {
		if (!in_array($_SERVER['REQUEST_METHOD'], $this->_allowed_methods)) {
			throw new Exception("请求方法不被允许", 405);			
		}
		$this->_request_method = $_SERVER['REQUEST_METHOD'];
	}

	/**
	 * 初始化请求资源
	 * @return  void
	 */
	private function _setupRequestResource() {
		if (!isset($_SERVER['PATH_INFO'])) {
			throw new Exception("资源不存在", 404);			
		}
		$path_info = explode('/', $_SERVER['PATH_INFO']); 
		$_request_resource = $path_info[1];
		
		if (!in_array($_request_resource, $this->_allowed_resources)) {
			throw new Exception("资源不存在2", 404);			
		}
		$this->_request_resource = $_request_resource;

		if (isset($path_info[2])) {
			$_request_id = $path_info[2];	
			$this->_request_id = $_request_id;
		}
	}

	/**
	 * 输出数据
	 * @param  string  $message 输出数据
	 * @param  integer $code    状态码
	 * @return mix           
	 */
	private function _json($message, $code=0) {
		header('content-type:application/json;charset=utf8');
		$code = $code<=204 ? 200 : $code;
		$code = $code==ErrorCode::USERNAME_IS_EXIST ? 400 : $code;
		if ($code>200) {
			header("HTTP/1.1 {$code} ".$this->_status_codes[$code]);
		}

		echo json_encode($message, JSON_UNESCAPED_UNICODE);
		exit;
	}

	/**
	 * 获取传入参数信息
	 * @return array 
	 */
	private function _getBodyParam() {
		$raw = file_get_contents('php://input');
		if (empty($raw)) {
			throw new Exception('请求参数错误', 400);
		}
		return json_decode($raw, true);
	}

	/**
	 * Users资源处理方法 - 只处理注册请求
	 * @return mix bool/array
	 */
	private function _handleUser() {
		if ($this->_request_method != 'POST') {
			throw new Exception("请求方法不被允许", 405);
		}

		$body = $this->_getBodyParam();
		if (empty($body['username'])) {
			throw new Exception("用户名不能为空", 400);	
		}
		if (empty($body['password'])) {
			throw new Exception("密码不能为空", 400);	
		}	

		try {
			return $this->_users->register($body['username'], $body['password']);	
		} catch (Exception $e) {
			if (in_array($e->getCode(), [
					ErrorCode::USERNAME_CANNOT_EMPTY, 
					ErrorCode::PASSWORD_CANNOT_EMPTY, 
					ErrorCode::USERNAME_IS_EXIST
				]	
			)) {
				throw new Exception($e->getMessage(), 400);	
			} 
			throw new Exception($e->getMessage(), 500);		
		}	
	}

	/**
	 * 用户登录
	 * @param  string $username 用户名
	 * @param  string $password 密码
	 * @return mix           
	 */
	private function _userLogin($username, $password) {
		try {
			return $this->_users->login($username, $password);
		} catch (Exception $e) {
			if (in_array($e->getCode(), [
					ErrorCode::USERNAME_CANNOT_EMPTY, 
					ErrorCode::PASSWORD_CANNOT_EMPTY, 
					ErrorCode::USER_OR_PASSWORD_INVALID
				]	
			)) {
				throw new Exception($e->getMessage(), 400);	
			} 
			throw new Exception($e->getMessage(), 500);			
		}		
	}

	/**
	 * Articles资源处理方法
	 * @return void 
	 */
	private function _handleArticle() {
		switch ($this->_request_method) {							
			case 'POST':
				return $this->_handleArticleCreate();				
			case 'DELETE':
				return $this->_handleArticleDelete();				
			case 'PUT':
				return $this->_handleArticleEdit();
			case 'GET':
				if (empty($this->_request_id)) {
					return $this->_handleArticleList();
				} else {
					return $this->_handleArticleView();
				}		
			default:
				throw new Exception("请求方法不被允许", 405);
		}
	}

	/**
	 * 创建文章
	 * @return 
	 */
	private function _handleArticleCreate() {
		$body = $this->_getBodyParam();
		if (empty($body['title'])) {
			throw new Exception("标题不能为空", 400);	
		}
		if (empty($body['content'])) {
			throw new Exception("内容不能为空", 400);	
		}

		$user = $this->_userLogin($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);

		try {
			return $this->_articles->createArticle($body['title'], $body['content'], $user['id']);
		} catch (Exception $e) {
			if (in_array($e->getCode(), [
					ErrorCode::TITLE_CANNOT_EMPTY, 
					ErrorCode::CONTENT_CANNOT_EMPTY, 
					ErrorCode::USERID_CANNOT_EMPTY
				]	
			)) {
				throw new Exception($e->getMessage(), 400);	
			} 
			throw new Exception($e->getMessage(), 500);		
		}
		
	}

	private function _handleArticleDelete() {
		
	}

	private function _handleArticleEdit() {
		
	}

	private function _handleArticleList() {
		
	}

	private function _handleArticleView() {
		
	}
}

$users = new Users($pdo);
$articles = new Articles($pdo);
$restful = new Restful($users, $articles);
$restful->run();