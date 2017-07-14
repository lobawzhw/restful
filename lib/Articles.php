<?php

/**
* 文章处理类
*/
class Articles
{
	private $_db;

	function __construct($_db)
	{
		$this->_db = $db;
	}

	public function createArticle($title, $content, $user_id) {
		if (empty($title)) {
			throw new Exception("文章标题不能为空", ErrorCode::TITLE_CANNOT_EMPTY);			
		}
		if (empty($content)) {
			throw new Exception("文章内容不能为空", ErrorCode::CONTENT_CANNOT_EMPTY);			
		}

		$sql = 'INSERT INTO `article`(title, content, createdAt, userId) VALUES(:title, :content, :createdAt, :userId)';
		$stmt = $this->_db->prepare($sql);
		$createdAt = time();
		$stmt->bindParam(':title', $title);
		$stmt->bindParam(':content', $content);
		$stmt->bindParam(':createdAt', $createdAt);
		$stmt->bindParam(':userId', $userId);
		
		if (!$stmt->execute()) {
			throw new Exception("文章保存失败", ErrorCode::ARTICLE_CREATE_FAILED);
		}

		return [
			'id' => $stmt->lastInsertId(),			
		];
	}

	public function deleteArticle($artical_id, $user_id) {
		$this->_checkArticleExist($artical_id);
		$this->_checkArticleAuth($artical_id, $user_id);

		$sql = 'DELETE FROM `article` WHERE `id`=:artical_id AND `userId`=:user_id';
		$stmt = $this->_db->prepare($sql);
		$stmt->bindParam(':artical_id', $artical_id);
		$stmt->bindParam(':user_id', $user_id);
		if (!$stmt->execute()) {
			throw new Exception("文章刪除失败", ErrorCode::ARTICLE_DELETE_FAILED);
		}

		return true;
	}

	public function editArticle($artical_id, $title, $content, $user_id) {
		$article = $this->_checkArticleExist($artical_id);
		$this->_checkArticleAuth($artical_id, $user_id);

		$sql = 'UPDATE `article` SET `title`=:title, `content`=:content WHERE `id`=:artical_id';
		$stmt = $this->_db->prepare($sql);
		$title = empty($title) ? $article['title'] : $title;
		$content = empty($content) ? $article['content'] : $content;
		$stmt->bindParam(':title', $title);
		$stmt->bindParam(':content', $content);
		$stmt->bindParam(':artical_id', $artical_id);
		if (empty($article)) {
			throw new Exception("文章更新失败", ErrorCode::ARTICLE_EDIT_FAILED);
		}
		return true;
	}

	public function getArticleList($artical_id=0) {
		if ($artical_id==0) {
			$sql = 'SELECT * FROM `article` WHERE `id`=:artical_id';
		}
	}

	private function _checkArticleExist($artical_id) {
		if (empty($artical_id)) {
			throw new Exception("文章ID不能为空", ErrorCode::ARTICLE_ID_CANNOT_EMPTY);			
		}
		$sql = 'SELECT * FROM `article` WHERE `id`=:artical_id';
		$stmt = $this->_db->prepare($sql);
		$stmt->bindParam(':artical_id', $artical_id);
		$stmt->execute();
		$article = $stmt->fetch(PDO::FETCH_ASSOC);
		if (empty($article)) {
			throw new Exception("文章不存在", ErrorCode::ARTICLE_NOT_FOUND);
		}
		return $article;
	}

	private function _checkArticleAuth($artical_id, $user_id) {
		$sql = 'SELECT * FROM `article` WHERE `id`=:artical_id AND `userId`=:user_id';
		$stmt = $this->_db->prepare($sql);
		$stmt->bindParam(':artical_id', $artical_id);
		$stmt->bindParam(':user_id', $user_id);
		$stmt->execute();
		$article = $stmt->fetch(PDO::FETCH_ASSOC);
		if (empty($article)) {
			throw new Exception("您无权操作该文章", ErrorCode::AUTH_DENIED);
		}
	}
}