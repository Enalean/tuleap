<?php

require_once('squal_pre.php');

$res=db_query("SELECT * FROM db_images WHERE id='$id'");

$filename=db_result($res,0,'filename');
$type=db_result($res,0,'filetype');
$data=db_result($res,0,'bin_data');

Header ( "Content-disposition: filename=".$filename);
Header ( "Content-type: $type");
echo $data;

?>
