<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
*
* 
*/
require_once('common/project/Service.class.php');
Mock::generatePartial('Service', 'ServiceTestVersion', array('_getServerFactory', '_sessionIsSecure'));
require_once('common/server/Server.class.php');
Mock::generate('Server');
require_once('common/server/ServerFactory.class.php');
Mock::generate('ServerFactory');
require_once('common/project/Project.class.php');
Mock::generate('Project');

class ServiceTest extends UnitTestCase {
    function ServiceTest($name = 'Service test') {
        $this->UnitTestCase($name);
    }
    
    function testGetUrl() {
        $url_server_1 = 'url_server_1';
        $url_server_2 = 'url_server_2';
        $link = 'link';
        $p = new MockProject($this);
        
        $server_1 =& new MockServer();
        $server_1->setReturnValue('getUrl', $url_server_1);
        
        $server_2 =& new MockServer();
        $server_2->setReturnValue('getUrl', $url_server_2);
        
        $sf =& new MockServerFactory();
        $sf->setReturnReference('getServerById', $server_1, array(1));
        $sf->setReturnReference('getServerById', $server_2, array(2));
        
        $service =& new ServiceTestVersion();
        $service->setReturnReference('_getServerFactory', $sf);
        $service->setReturnValue('_sessionIsSecure', false);
        $service->__construct($p, array(
            'link' => $link,
            'location' => 'satellite',
            'server_id' => 1
        ));
        $this->assertEqual($service->getUrl(), $url_server_1 . $link);
        
        $service =& new ServiceTestVersion();
        $service->setReturnReference('_getServerFactory', $sf);
        $service->setReturnValue('_sessionIsSecure', false);
        $service->__construct($p, array(
            'link' => $link,
            'location' => 'satellite',
            'server_id' => 2
        ));
        $this->assertEqual($service->getUrl(), $url_server_2 . $link);
        
        $service =& new ServiceTestVersion();
        $service->setReturnReference('_getServerFactory', $sf);
        $service->setReturnValue('_sessionIsSecure', false);
        $service->__construct($p, array(
            'link' => $link,
            'location' => 'same',
            'server_id' => 1
        ));
        $this->assertEqual($service->getUrl(), $link);
    }
    
    function testAbsoluteUrl() {
        $url_server = 'url_server';
        $link = 'http://abs.olu.te';
        $p = new MockProject($this);
        
        $server =& new MockServer();
        $server->setReturnValue('getUrl', $url_server);
        
        $sf =& new MockServerFactory();
        $sf->setReturnReference('getServerById', $server);
        
        $service =& new ServiceTestVersion();
        $service->setReturnReference('_getServerFactory', $sf);
        $service->setReturnValue('_sessionIsSecure', false);
        $service->__construct($p, array(
            'link' => $link,
            'location' => 'satellite',
            'server_id' => 1
        ));
        $this->assertEqual($service->getUrl(), $link);
        
        $service =& new ServiceTestVersion();
        $service->setReturnReference('_getServerFactory', $sf);
        $service->setReturnValue('_sessionIsSecure', false);
        $service->__construct($p, array(
            'link' => $link,
            'location' => 'same',
            'server_id' => 1
        ));
        $this->assertEqual($service->getUrl(), $link);
    }
}
?>
