<?php
//require_once('../include/IMPlugin.class.php'); 
class testIMPLugin extends UnitTestCase{
	

	function testInstance () {
		$im = new IMPlugin();
		$this->assertNotNull();
	}
	
}
?>