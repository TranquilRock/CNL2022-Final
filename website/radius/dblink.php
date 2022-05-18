<?php
	$dsn='mysql:host=localhost;dbname=radius;charset=utf8';

	try{
        $db = new PDO($dsn, "radius", "cnlab2018");
        $db->setAttribute(PDO::FETCH_ASSOC);
	} catch(PDOException $e){  //if can't connect
		// show error
		$error = 'connection_unsuccessful: '.$e->getMessage();
		print_r($error);
		// close sql pdo
		$db=null;
    }
    
?>