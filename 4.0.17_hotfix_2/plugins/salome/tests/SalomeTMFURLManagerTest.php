<?php

require_once(dirname(__FILE__).'/../include/SalomeTMFURLManager.class.php');

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * Test the class SalomeTMFURLManager
 */
class SalomeTMFURLMAnagerTest extends UnitTestCase {
	
	/**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function SalomeTMFURLManagerTest($name = 'SalomeTMFURLManager test') {
        $this->UnitTestCase($name);
    }
    
    function testURLWithPort() {
        $url_with_port = 'myserver.mycompany.org:8080';
        $um = new SalomeTMFURLManager($url_with_port);
        $this->assertNull($um->getScheme());
        $this->assertEqual($um->getHost(), 'myserver.mycompany.org');
        $this->assertEqual($um->getPort(), '8080');
        $this->assertEqual($um->getJDBCUrl(), 'jdbc:mysql://myserver.mycompany.org/salome');
    }
    function testURLHttps() {
        $url_with_port = 'mysecureserver.mycompany.org:443';
        $um = new SalomeTMFURLManager($url_with_port);
        $this->assertNull($um->getScheme());
        $this->assertEqual($um->getHost(), 'mysecureserver.mycompany.org');
        $this->assertEqual($um->getPort(), '443');
        $this->assertEqual($um->getJDBCUrl(), 'jdbc:mysql://mysecureserver.mycompany.org/salome');
    }
    function testURLSecurePortAndScheme() {
        $url_with_port = 'https://mysecureserver.mycompany.org:443';
        $um = new SalomeTMFURLManager($url_with_port);
        $this->assertEqual($um->getScheme(), 'https');
        $this->assertEqual($um->getHost(), 'mysecureserver.mycompany.org');
        $this->assertEqual($um->getPort(), '443');
        $this->assertEqual($um->getJDBCUrl(), 'jdbc:mysql://mysecureserver.mycompany.org/salome');
    }
    function testURLWithoutPort() {
        $url_without_port = 'myserver.mycompany.org';
        $um = new SalomeTMFURLManager($url_without_port);
        $this->assertNull($um->getScheme());
        $this->assertEqual($um->getHost(), 'myserver.mycompany.org');
        $this->assertNull($um->getPort());
        $this->assertEqual($um->getJDBCUrl(), 'jdbc:mysql://myserver.mycompany.org/salome');
    }
    function testURLWithScheme() {
        $url_with_scheme = 'http://myserver.mycompany.org';
        $um = new SalomeTMFURLManager($url_with_scheme);
        $this->assertEqual($um->getScheme(), 'http');
        $this->assertEqual($um->getHost(), 'myserver.mycompany.org');
        $this->assertNull($um->getPort());
        $this->assertEqual($um->getJDBCUrl(), 'jdbc:mysql://myserver.mycompany.org/salome');
    }
    function testURLWithSchemeAndPort() {
        $url_with_scheme = 'http://myserver.mycompany.org:9090';
        $um = new SalomeTMFURLManager($url_with_scheme);
        $this->assertEqual($um->getScheme(), 'http');
        $this->assertEqual($um->getHost(), 'myserver.mycompany.org');
        $this->assertEqual($um->getPort(), '9090');
        $this->assertEqual($um->getJDBCUrl(), 'jdbc:mysql://myserver.mycompany.org/salome');
    }
    function testLocalhost() {
        $url = 'localhost';
        $um = new SalomeTMFURLManager($url);
        $this->assertNull($um->getScheme());
        $this->assertEqual($um->getHost(), 'localhost');
        $this->assertNull($um->getPort());
        $this->assertEqual($um->getJDBCUrl(), 'jdbc:mysql://localhost/salome');
    }
    function testURLWithIPAddress() {
        $url = '192.168.0.123';
        $um = new SalomeTMFURLManager($url);
        $this->assertNull($um->getScheme());
        $this->assertEqual($um->getHost(), '192.168.0.123');
        $this->assertNull($um->getPort());
        $this->assertEqual($um->getJDBCUrl(), 'jdbc:mysql://192.168.0.123/salome');
    }
    function testURLWithIPAddressAndPort() {
        $url = '192.168.0.123:8080';
        $um = new SalomeTMFURLManager($url);
        $this->assertNull($um->getScheme());
        $this->assertEqual($um->getHost(), '192.168.0.123');
        $this->assertEqual($um->getPort(), '8080');
        $this->assertEqual($um->getJDBCUrl(), 'jdbc:mysql://192.168.0.123/salome');
    }

}

?>