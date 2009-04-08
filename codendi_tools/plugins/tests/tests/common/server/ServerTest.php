<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* 
*/
require_once('common/server/Server.class.php');

class ServerTest extends UnitTestCase {
    function ServerTest($name = 'Server test') {
        $this->UnitTestCase($name);
    }
    
    function testGetUrl() {
        $http = 'http url';
        $https = 'https url';
        
        $s1 =& new Server(array('http' => $http, 'https' => ''));
        $this->assertEqual($s1->getUrl($secure = false), $http);
        $this->assertEqual($s1->getUrl($secure = true),  $http);
        
        $s2 =& new Server(array('http' => '', 'https' => $https));
        $this->assertEqual($s2->getUrl($secure = false), $https);
        $this->assertEqual($s2->getUrl($secure = true),  $https);
        
        $s3 =& new Server(array('http' => $http, 'https' => $https));
        $this->assertEqual($s3->getUrl($secure = false), $http);
        $this->assertEqual($s3->getUrl($secure = true),  $https);
    }
}
?>
