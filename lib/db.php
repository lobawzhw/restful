<?php

$pdo = new PDO('mysql:host=localhost;dbname=restful', 'root', '');
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
// $pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
return $pdo;