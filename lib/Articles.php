<?php

/**
* 文章处理类
*/
class Articles
{
	/**
	 * 数据库连接句柄 - PDO
	 * @var 
	 */
	private $_db;

	/**
	 * 单次文章获取数量的上限
	 * @var integer
	 */
	private $_limit_count = 100;

	/**
	 * 文章默认单页获取数量
	 * @var integer
	 */
	private $_default_page_size = 20;

	function __construct($_db)
	{
		$this->_db = $_db;
	}

	/**
	 * 创建文章
	 * @param  string $title   标题
	 * @param  string $content 内容
	 * @param  int $user_id 作者ID
	 * @return array          
	 */
	public function createArticle($title, $content, $user_id) {
		if (empty($title)) {
			throw new Exception("文章标题不能为空", ErrorCode::TITLE_CANNOT_EMPTY);			
		}
		if (empty($content)) {
			throw new Exception("文章内容不能为空", ErrorCode::CONTENT_CANNOT_EMPTY);			
		}
		if (empty($user_id)) {
			throw new Exception("作者ID不能空", ErrorCode::USERID_CANNOT_EMPTY);			
		}

		$sql = 'INSERT INTO `article`(title, content, createdAt, userId) VALUES(:title, :content, :createdAt, :user_id)';
		$stmt = $this->_db->prepare($sql);
		$createdAt = time();
		$stmt->bindParam(':title', $title);
		$stmt->bindParam(':content', $content);
		$stmt->bindParam(':createdAt', $createdAt);
		$stmt->bindParam(':user_id', $user_id);
		
		if (!$stmt->execute()) {
			throw new Exception("文章保存失败", ErrorCode::ARTICLE_CREATE_FAILED);
		}

		return [
			'id' => $this->_db->lastInsertId(),			
		];
	}

	/**
	 * 删除文章
	 * @param  int $article_id 文章ID
	 * @param  int $user_id    作者ID
	 * @return bool             
	 */
	public function deleteArticle($article_id, $user_id) {
		$this->_checkArticleAuth($article_id, $user_id);

		$sql = 'DELETE FROM `article` WHERE `id`=:article_id AND `userId`=:user_id';
		$stmt = $this->_db->prepare($sql);
		$stmt->bindParam(':article_id', $article_id);
		$stmt->bindParam(':user_id', $user_id);
		if (!$stmt->execute()) {
			throw new Exception("文章刪除失败", ErrorCode::ARTICLE_DELETE_FAILED);
		}
		return true;
	}

	/**
	 * 编辑文章
	 * @param  int $article_id 文章ID
	 * @param  string $title   标题
	 * @param  string $content 内容
	 * @param  int $user_id    作者ID
	 * @return bool       
	 */
	public function editArticle($article_id, $title, $content, $user_id) {
		$article = $this->_checkArticleAuth($article_id, $user_id);

		$sql = 'UPDATE `article` SET `title`=:title, `content`=:content WHERE `id`=:article_id';
		$stmt = $this->_db->prepare($sql);
		$title = empty($title) ? $article['title'] : $title;
		$content = empty($content) ? $article['content'] : $content;
		// var_dump([$article_id, $title, $content, $sql]);exit;
		$stmt->bindParam(':title', $title);
		$stmt->bindParam(':content', $content);
		$stmt->bindParam(':article_id', $article_id);
		if (!$stmt->execute()) {
			throw new Exception("文章更新失败", ErrorCode::ARTICLE_EDIT_FAILED);
		}
		return [
			'id' => $article_id,
			'title' => $title,
			'content' => $content,
			'createdAt' => $article['createdAt'],
			'userId' => $article['userId'],
		];
	}

	/**
	 * 获取一篇文章详情
	 * @param  int $article_id 文章ID
	 * @return array             
	 */
	public function getOneArticle($article_id) {
		$article = $this->_getOneArticle($article_id);
		return $article;
	}

	/**
	 * 获取文章列表
	 * @param  int $page     页码
	 * @param  int $pagesize 单页文章数量
	 * @return array           
	 */
	public function getArticleList($page, $pagesize=0) {
		$pagesize = $pagesize===0 ? $this->_default_page_size : $pagesize;
		if ($pagesize > $this->_limit_count) {
			throw new Exception("获取文章数量超出上限{$this->_limit_count}", ErrorCode::REQUEST_TOO_MUCH);	
		}
		$sql = 'SELECT * FROM `article` LIMIT :limit, :pagesize';
		$stmt = $this->_db->prepare($sql);
		$limit = ($page - 1) * $pagesize;
		$limit = $limit < 0 ? 0 : $limit;
		$stmt->bindParam(':limit', $limit);
		$stmt->bindParam(':pagesize', $pagesize);
		if (!$stmt->execute()) {
			throw new Exception("获取文章列表失败", ErrorCode::GET_ARTICLE_LIST_FAILED);			
		}
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $result;
	}

	/**
	 * 检查文章是否存在
	 * 若存在则返回文章详情
	 * @param  int $article_id 文章ID
	 * @return array             
	 */
	private function _getOneArticle($article_id) {
		if (!isset($article_id)) {
			throw new Exception("文章ID不能为空", ErrorCode::ARTICLE_ID_CANNOT_EMPTY);			
		}
		$sql = 'SELECT * FROM `article` WHERE `id`=:article_id';
		$stmt = $this->_db->prepare($sql);
		$stmt->bindParam(':article_id', $article_id);
		if (!$stmt->execute()) {
			throw new Exception("获取文章失败", ErrorCode::GET_ARTICLE_FAILED);			
		}
		$article = $stmt->fetch(PDO::FETCH_ASSOC);
		if (empty($article)) {
			throw new Exception("文章不存在", ErrorCode::ARTICLE_NOT_FOUND);
		}
		return $article;
	}

	/**
	 * 检查文章与作者是否对应
	 * @param  int $article_id 文章ID
	 * @param  int $user_id    作者ID
	 * @return array             
	 */
	private function _checkArticleAuth($article_id, $user_id) {
		if (empty($article_id)) {
			throw new Exception("文章ID不能为空", ErrorCode::ARTICLE_ID_CANNOT_EMPTY);			
		}
		if (empty($user_id)) {
			throw new Exception("作者ID不能空", ErrorCode::USERID_CANNOT_EMPTY);			
		}
		$sql = 'SELECT * FROM `article` WHERE `id`=:article_id AND `userId`=:user_id';
		$stmt = $this->_db->prepare($sql);
		$stmt->bindParam(':article_id', $article_id);
		$stmt->bindParam(':user_id', $user_id);
		if (!$stmt->execute()) {
			throw new Exception("检查文章归属失败", ErrorCode::CHECK_AUTH_FAILED);			
		}
		$article = $stmt->fetch(PDO::FETCH_ASSOC);
		if (empty($article)) {
			throw new Exception("您无权操作该文章", ErrorCode::AUTH_DENIED);
		}
		return $article;
	}
}