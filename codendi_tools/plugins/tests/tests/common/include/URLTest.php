<?php
/* 
 * Copyright (c) The Codendi Team, Xerox, 2008. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */

require_once('common/include/URL.class.php');
require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');
Mock::generatePartial('URL', 'URLTestVersion', array('getProjectDao','getForumDao','getNewsBytesDao','getArtifactDao', 'getEventManager'));
Mock::generatepartial('URL', 'URLTestVersion2', array('isValidHost', 'getRedirectionURL', 'header'));
require_once('common/dao/ProjectDao.class.php');
Mock::generate('ProjectDao');
require_once('common/dao/ForumDao.class.php');
Mock::generate('ForumDao');
require_once('common/dao/NewsBytesDao.class.php');
Mock::generate('NewsBytesDao');
require_once('common/dao/ArtifactDao.class.php');
Mock::generate('ArtifactDao');
require_once('common/event/EventManager.class.php');
Mock::generate('EventManager');

class MockEM4Url extends MockEventManager {
   function processEvent($event, $params) {
       foreach(parent::processEvent($event, $params) as $key => $value) {
           $params[$key] = $value;
       }
   }
} 

class URLTest extends UnitTestCase {
    private $sys_https_host;
    private $sys_force_ssl;
    private $sys_default_domain;

    function setUp() {
        $this->sys_https_host      = $GLOBALS['sys_https_host'];
        $this->sys_force_ssl       = $GLOBALS['sys_force_ssl'];
        $this->sys_default_domain  = $GLOBALS['sys_default_domain'];
        $GLOBALS['sys_news_group'] = 46;
    }
    
    function tearDown() {
        $GLOBALS['sys_https_host']     = $this->sys_https_host;
        $GLOBALS['sys_force_ssl']      = $this->sys_force_ssl;
        $GLOBALS['sys_default_domain'] = $this->sys_default_domain;
        unset($GLOBALS['group_id']);
        unset($GLOBALS['sys_news_group']);
        unset($_REQUEST['forum_id']);
        unset($GLOBALS['PATH_INFO']);
    }
    
    function testProjectsDontExist() {
        $url = new URLTestVersion($this);
        $dao = new MockProjectDao($this);
        $exists = new MockDataAccessResult($this);
        $exists->setReturnValue('rowCount', 0);
        $exists->setReturnValue('getRow', false);
        $dao->setReturnReference('searchByUnixGroupName', $exists);
        
        $url->setReturnReference('getProjectDao', $dao);
        $this->assertFalse($url->getGroupIdFromURL('/projects/dontexist/'));
    }
    
    function testProjectsExist() {
        $url = new URLTestVersion($this);
        $dao = new MockProjectDao($this);
        $exists = new MockDataAccessResult($this);
        $exists->setReturnValue('rowCount', 1);
        $exists->setReturnValue('getRow', false);
        $exists->setReturnValueAt(0, 'getRow', array('group_id' => '1'));

        $exists1 = new MockDataAccessResult($this);
        $exists1->setReturnValue('rowCount', 1);
        $exists1->setReturnValue('getRow', false);
        $exists1->setReturnValueAt(0, 'getRow', array('group_id' => '1'));

        $dao->setReturnReferenceAt(0, 'searchByUnixGroupName', $exists);
        $dao->setReturnReferenceAt(1, 'searchByUnixGroupName', $exists1);
        
        $url->setReturnReference('getProjectDao', $dao);
        $this->assertEqual($url->getGroupIdFromURL('/projects/exist/'), 1);
        $this->assertNotEqual($url->getGroupIdFromURL('/toto/projects/exist/'), 1);
    }
    
    function testViewVcDontExist() {
        $url = new URLTestVersion($this);
        $dao = new MockProjectDao($this);
        $exists = new MockDataAccessResult($this);
        $exists->setReturnValue('rowCount', 0);
        $exists->setReturnValue('getRow', false);
        $dao->setReturnReference('searchByUnixGroupName', $exists);
        
        $url->setReturnReference('getProjectDao', $dao);
        $this->assertFalse($url->getGroupIdFromURL('/viewvc.php/?roottype=svn&root=dontexist'));
    }
    
    function testViewVcExist() {
        $url = new URLTestVersion($this);
        $dao = new MockProjectDao($this);
        $exists = new MockDataAccessResult($this);
        $exists->setReturnValue('rowCount', 1);
        $exists->setReturnValue('getRow', false);
        $exists->setReturnValueAt(0, 'getRow', array('group_id' => '1'));
        $dao->setReturnReference('searchByUnixGroupName', $exists);
        
        $url->setReturnReference('getProjectDao', $dao);
        $this->assertEqual($url->getGroupIdFromURL('/viewvc.php/?roottype=svn&root=exist'), 1);
    }
    
    function testForumDontExist() {
        $url = new URLTestVersion($this);
        $dao = new MockForumDao($this);
        $exists = new MockDataAccessResult($this);
        $exists->setReturnValue('getRow', false);
        $dao->setReturnReference('searchByGroupForumId', $exists);
        
        $url->setReturnReference('getForumDao', $dao);
        $this->assertFalse($url->getGroupIdFromURL('/forum/forum.php?forum_id=dontexist'));
    }
    
    function testForumExist() {
        $url = new URLTestVersion($this);
        $dao = new MockForumDao($this);
        $exists = new MockDataAccessResult($this);
        $exists->setReturnValue('getRow', false);
        $exists->setReturnValueAt(0, 'getRow', array('group_id' => '1'));
        $exists1 = new MockDataAccessResult($this);
        $exists1->setReturnValue('getRow', false);
        $exists1->setReturnValueAt(0, 'getRow', array('group_id' => '1'));
        $dao->setReturnReferenceAt(0, 'searchByGroupForumId', $exists);
        $dao->setReturnReferenceAt(1, 'searchByGroupForumId', $exists1);
        $_REQUEST['forum_id']=1;
        $url->setReturnReference('getForumDao', $dao);
        $this->assertEqual($url->getGroupIdFromURL('/forum/forum.php?forum_id=exist'), 1);
        $this->assertNotEqual($url->getGroupIdFromURL('/toto/forum/forum.php?forum_id=exist'), 1);
    }
    
   function testNewsBytesDontExist() {
        $url = new URLTestVersion($this);
        $dao = new MockForumDao($this);
        $exists = new MockDataAccessResult($this);
        
        $exists->setReturnValue('getRow', false);
        $exists->setReturnValueAt(0, 'getRow', array('group_id' => '42'));
        $dao->setReturnReference('searchByGroupForumId', $exists);
        $_REQUEST['forum_id']=1;
        $group_id = $url->setReturnReference('getForumDao', $dao);
        $this->assertNotEqual($url->getGroupIdFromURL('/forum/forum.php?forum_id=exist'), $GLOBALS['sys_news_group']);
               
    }
    
   function testNewsBytesExist() {
        $url = new URLTestVersion($this);
        $dao = new MockForumDao($this);
        $exists = new MockDataAccessResult($this);
        
        $exists->setReturnValue('getRow', false);
        $exists->setReturnValueAt(0, 'getRow', array('group_id' => $GLOBALS['sys_news_group']));
        $dao->setReturnReference('searchByGroupForumId', $exists);
        $_REQUEST['forum_id']=1;
        $group_id = $url->setReturnReference('getForumDao', $dao);
        
        $dao2 = new MockNewsBytesDao($this);
        $exists2 = new MockDataAccessResult($this);
        $exists2->setReturnValue('getRow', false);
        $exists2->setReturnValueAt(0, 'getRow', array('group_id' =>$GLOBALS['sys_news_group']));
        $dao2->setReturnReference('searchByForumId', $exists2);
        $url->setReturnReference('getNewsBytesDao', $dao2);
        $this->assertEqual($url->getGroupIdFromURL('/forum/forum.php?forum_id=exist'), $GLOBALS['sys_news_group']);
               
    }

    
    function testArtifactDontExist(){
        $url = new URLTestVersion($this);
        $dao = new MockArtifactDao($this);
        $exists = new MockDataAccessResult($this);
        $exists->setReturnValue('getRow', false);
        $dao->setReturnReference('searchArtifactId', $exists);
        
        $url->setReturnReference('getArtifactDao', $dao);
        $this->assertFalse($url->getGroupIdFromURL('/tracker/download.php?artifact_id=dontexist'));
    }
    
    function testArtifactExist() {
        $url = new URLTestVersion($this);
        $dao = new MockArtifactDao($this);
        $exists = new MockDataAccessResult($this);
        $exists->setReturnValue('getRow', false);
        $exists->setReturnValueAt(0, 'getRow', array('group_id' => '1'));

        $exists1 = new MockDataAccessResult($this);
        $exists1->setReturnValue('getRow', false);
        $exists1->setReturnValueAt(0, 'getRow', array('group_id' => '1'));

        $dao->setReturnReferenceAt(0, 'searchArtifactId', $exists);
        $dao->setReturnReferenceAt(1, 'searchArtifactId', $exists1);
        $_REQUEST['artifact_id']=1;
        $url->setReturnReference('getArtifactDao', $dao);
        $this->assertEqual($url->getGroupIdFromURL('/tracker/download.php?artifact_id=exist'), 1);
        $this->assertNotEqual($url->getGroupIdFromURL('/toto/tracker/download.php?artifact_id=exist'), 1);
    }
    
    function testFileDownload() {
        $url = new URL();
        $GLOBALS['PATH_INFO'] = '/101/1/p9_r4/toto.csv';
        $this->assertEqual($url->getGroupIdFromURL('/file/download.php/101/1/p9_r4/toto.csv'), 101);
        $this->assertNotEqual($url->getGroupIdFromURL('/toto/file/download.php/101/1/p9_r4/toto.csv'), 101);
    }

    function testIsException() {
        $url = new URL();
        $this->assertTrue($url->isException(array('SERVER_NAME' => 'localhost',    'SCRIPT_NAME' => '/projects/foobar')));
        $this->assertFalse($url->isException(array('SERVER_NAME' => 'codendi.org', 'SCRIPT_NAME' => '/projects/foobar')));
        
        $this->assertTrue($url->isException(array('SERVER_NAME' => 'codendi.org', 'SCRIPT_NAME' => '/api/reference/extractCross')));
        $this->assertTrue($url->isException(array('SERVER_NAME' => 'codendi.org', 'SCRIPT_NAME' => '/soap/index.php')));
        $this->assertFalse($url->isException(array('SERVER_NAME' => 'codendi.org', 'SCRIPT_NAME' => '/projects/foobar')));
    }

    function testValidHostHttp() {
        $GLOBALS['sys_https_host']     = 'secure.codendi.org';
        $GLOBALS['sys_force_ssl']      = 0;
        $GLOBALS['sys_default_domain'] = 'codendi.org';
        $server = array('HTTP_HOST'   => 'codendi.org',
                        'SERVER_NAME' => 'codendi.org',
                        'SCRIPT_NAME' => '');
        $url = new URLTestVersion($this);

        $em = new MockEventManager($this);
        $url->setReturnValue('getEventManager', $em);

        $this->assertTrue($url->isValidHost($server));
    }

    function testValidHostLocalhost() {
        $GLOBALS['sys_https_host']     = 'secure.codendi.org';
        $GLOBALS['sys_force_ssl']      = 0;
        $GLOBALS['sys_default_domain'] = 'codendi.org';
        $server = array('HTTP_HOST'   => 'localhost',
                        'SERVER_NAME' => 'localhost',
                        'SCRIPT_NAME' => '');
        $url = new URLTestVersion($this);

        $em = new MockEventManager($this);
        $url->setReturnValue('getEventManager', $em);

        $this->assertTrue($url->isValidHost($server));
    }

    function testValidHostLocalhostSecure() {
        $GLOBALS['sys_https_host']     = 'secure.codendi.org';
        $GLOBALS['sys_force_ssl']      = 1;
        $GLOBALS['sys_default_domain'] = 'codendi.org';
        $server = array('HTTP_HOST'   => 'localhost',
                        'SERVER_NAME' => 'localhost',
                        'HTTPS'       => 'on',
                        'SCRIPT_NAME' => '');
        $url = new URLTestVersion($this);

        $em = new MockEventManager($this);
        $url->setReturnValue('getEventManager', $em);

        $this->assertTrue($url->isValidHost($server));
    }

    function testValidHostInvalidDomain() {
        $GLOBALS['sys_https_host']     = 'secure.codendi.org';
        $GLOBALS['sys_force_ssl']      = 0;
        $GLOBALS['sys_default_domain'] = 'codendi.org';
        $server = array('HTTP_HOST'   => 'test.codendi.org',
                        'SERVER_NAME' => 'test.codendi.org',
                        'SCRIPT_NAME' => '');
        $url = new URLTestVersion($this);

        $em = new MockEventManager($this);
        $url->setReturnValue('getEventManager', $em);

        $this->assertFalse($url->isValidHost($server));
    }

    function testValidHostHttps() {
        $GLOBALS['sys_https_host']     = 'secure.codendi.org';
        $GLOBALS['sys_force_ssl']      = 1;
        $GLOBALS['sys_default_domain'] = 'codendi.org';
        $server = array('HTTP_HOST'   => 'secure.codendi.org',
                        'SERVER_NAME' => 'secure.codendi.org',
                        'HTTPS'       => 'on',
                        'SCRIPT_NAME' => '');
        $url = new URLTestVersion($this);

        $em = new MockEventManager($this);
        $url->setReturnValue('getEventManager', $em);

        $this->assertTrue($url->isValidHost($server));
    }
    
    function testValidHostInvalidHttps() {
        $GLOBALS['sys_https_host']     = 'secure.codendi.org';
        $GLOBALS['sys_force_ssl']      = 1;
        $GLOBALS['sys_default_domain'] = 'codendi.org';
        $server = array('HTTP_HOST'   => 'codendi.org',
                        'SERVER_NAME' => 'codendi.org',
                        'HTTPS'       => 'on',
                        'SCRIPT_NAME' => '');
        $url = new URLTestVersion($this);

        $em = new MockEventManager($this);
        $url->setReturnValue('getEventManager', $em);

        $this->assertFalse($url->isValidHost($server));
    }
    
    function testValidHostByPlugin() {
        $GLOBALS['sys_https_host']     = 'secure.codendi.org';
        $GLOBALS['sys_force_ssl']      = 0;
        $GLOBALS['sys_default_domain'] = 'codendi.org';
        
        $server = array('HTTP_HOST'   => 'webdav.codendi.org',
                        'SERVER_NAME' => 'webdav.codendi.org',
                        'SCRIPT_NAME' => '');
        
        $em = new MockEM4Url($this);
        $em->setReturnValue('processEvent', array('server_name' => array('webdav.codendi.org' => true)));

        $url = new URLTestVersion($this);
        $url->setReturnValue('getEventManager', $em);
        
        $this->assertTrue($url->isValidHost($server));
    }

    function testValidHostByPluginSecure() {
        $GLOBALS['sys_https_host']     = 'secure.codendi.org';
        $GLOBALS['sys_force_ssl']      = 1;
        $GLOBALS['sys_default_domain'] = 'codendi.org';
        
        $server = array('HTTP_HOST'   => 'webdav.codendi.org',
                        'SERVER_NAME' => 'webdav.codendi.org',
                        'HTTPS'       => 'on',
                        'SCRIPT_NAME' => '');
        
        $em = new MockEM4Url($this);
        $em->setReturnValue('processEvent', array('server_name' => array('webdav.codendi.org' => true)));
        
        $url = new URLTestVersion($this);
        $url->setReturnValue('getEventManager', $em);
        
        $this->assertTrue($url->isValidHost($server));
    }
    
    function testRedirectionUrlWithForcedSSL() {
        $GLOBALS['sys_https_host']     = 'secure.codendi.org';
        $GLOBALS['sys_force_ssl']      = 1;
        $GLOBALS['sys_default_domain'] = 'codendi.org';
        
        $server = array('HTTP_HOST'   => 'codendi.org',
                        'SERVER_NAME' => 'codendi.org',
                        'HTTPS'       => 'on',
                        'SCRIPT_NAME' => '/test/foo.php',
                        'REQUEST_URI' => '/test/foo.php&group_id=bar');
        
        $url = new URLTestVersion($this);

        $em = new MockEventManager($this);
        $url->setReturnValue('getEventManager', $em);

        $this->assertEqual($url->getRedirectionURL($server), 'https://secure.codendi.org/test/foo.php&group_id=bar');
    }

    function testRedirectionUrlWithoutForcedSSL() {
        $GLOBALS['sys_https_host']     = 'secure.codendi.org';
        $GLOBALS['sys_force_ssl']      = 0;
        $GLOBALS['sys_default_domain'] = 'codendi.org';
        
        $server = array('HTTP_HOST'   => 'codendi.org',
                        'SERVER_NAME' => 'codendi.org',
                        'SCRIPT_NAME' => '/test/foo.php',
                        'REQUEST_URI' => '/test/foo.php&group_id=bar');
        
        $url = new URLTestVersion($this);

        $em = new MockEventManager($this);
        $url->setReturnValue('getEventManager', $em);

        $this->assertEqual($url->getRedirectionURL($server), 'http://codendi.org/test/foo.php&group_id=bar');
    }
    
    function testRedirectionUrlSSLAccessWithoutForcedSSL() {
        $GLOBALS['sys_https_host']     = 'secure.codendi.org';
        $GLOBALS['sys_force_ssl']      = 0;
        $GLOBALS['sys_default_domain'] = 'codendi.org';
        
        $server = array('HTTP_HOST'   => 'secure.codendi.org',
                        'SERVER_NAME' => 'secure.codendi.org',
                        'HTTPS'       => 'on',
                        'SCRIPT_NAME' => '/test/foo.php',
                        'REQUEST_URI' => '/test/foo.php&group_id=bar');
        
        $url = new URLTestVersion($this);

        $em = new MockEventManager($this);
        $url->setReturnValue('getEventManager', $em);

        $this->assertEqual($url->getRedirectionURL($server), 'https://secure.codendi.org/test/foo.php&group_id=bar');
    }

    function testAssertValidUrlWithInvalidHost() {

        $url = new URLTestVersion2($this);
        $url->setReturnValue('isValidHost', false);
        $url->expectOnce('header');
        $server = array();
        $url->assertValidUrl($server);

    }

    function testAssertValidUrlWithvalidHost() {

        $url = new URLTestVersion2($this);
        $url->setReturnValue('isValidHost', true);
        $url->setReturnValue('getRedirectionURL', 'http://codendi.org');
        $url->expectNever('header');
        $server = array();
        $url->assertValidUrl($server);

    }

}
?>
