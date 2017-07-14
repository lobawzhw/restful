<?php

$db = mysql_connect('localhost', 'root', '');
mysql_select_db('restful', $db);
return $db;