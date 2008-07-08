<?php
/*
 * Created on Jun 26, 2008
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */

 require_once(dirname(__FILE__).'/../include/IMPlugin.class.php');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * Test the class IMPlugin
 */
class IMPluginTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function IMPluginTest($name = 'IMPlugin test') {
        $this->UnitTestCase($name);
    }
    function testIm_process () {
		$grp_id=225;
		$params=array("group_id"=>$grp_id);
		//before muc and grp creation
		$im1=& new IMPlugin(10);
		$this->assertNull($im1->_get_last_muc_room_name());
        $this->assertNull($im1->_get_last_muc_grp_name());
        
        
        $im2=& new IMPlugin(10);
        $im2->im_process($params);
        $this->assertNull($im1->_get_last_muc_room_name());
        $this->assertNull($im1->_get_last_muc_grp_name());
	}
}
?>
