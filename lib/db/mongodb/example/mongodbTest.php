<?php
	
	require_once('../mongodb.class.php');
	$mongoArray = array(
			'host'=>'localhost',
			'dbname' => 'log',
			'collection' => 'systemlog',
	
	);
	$mongoCustomerObj = new MongoCustomer();
	$insertArray = array('message'=>'[失败]数据库连接失败8888');
	$mongoCustomerObj -> insert($insertArray);
	echo '<pre>';
	$rsArray = $mongoCustomerObj -> select(array('test'=>'test1'));
	echo count($rsArray).'<br />';
	print_r($rsArray);