<?php
header('content-type:text/html;charset=utf8');
// echo __DIR__;exit;
require __DIR__.'/lib/Users.php';
$pdo = require __DIR__.'/lib/db.php';
$users = new Users($pdo);
// $result = $users->register('u5', 'u5');

// $result = $users->login('u1','p1');
// var_dump($result);

