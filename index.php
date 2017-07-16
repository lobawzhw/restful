<?php
header('content-type:text/html;charset=utf8');
// echo __DIR__;exit;
require __DIR__.'/lib/Users.php';
require __DIR__.'/lib/Articles.php';
require __DIR__.'/lib/ErrorCode.php';
$pdo = require __DIR__.'/lib/db.php';
$users = new Users($pdo);
// $result = $users->register('u7', 'u7');
// $result = $users->login('u1','p1');

$articles = new Articles($pdo);
// $result = $articles->createArticle('t7', 'c7', '8');
// $result = $articles->deleteArticle(1, 5);
// $result = $articles->editArticle(3, '', '', 5);
// $result = $articles->getOneArticle(3);
// $result = $articles->getArticleList(2,3);


// eturn 333{}
function test() {
	try {
		return 222;
	} catch (Exception $e) {
		return 111;
	}
}

echo test();
exit;

		
var_dump($result);
