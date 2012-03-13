<?php
/* 
 * Copyright (c) The Codendi Team, Xerox, 2009. All Rights Reserved.
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


require_once('common/backend/BackendAliases.class.php');

require_once('common/dao/UserDao.class.php');
Mock::generate('UserDao');
require_once('common/dao/MailingListDao.class.php');
Mock::generate('MailingListDao');

Mock::generatePartial('BackendAliases', 'BackendAliasesTestVersion', 
                      array('getUserDao', 
                            'getMailingListDao',
                            'system'
                            ));

class BackendAliasesTest extends UnitTestCase {
    
    function __construct($name = 'BackendAliases test') {
        parent::__construct($name);
    }

    function setUp() {
        $GLOBALS['alias_file'] = dirname(__FILE__) . '/_fixtures/etc/aliases.tuleap';   
    }
    
    function tearDown() {
        unset($GLOBALS['alias_file']);
        //clear the cache between each tests
        Backend::clearInstances();
    }
    
    function testUpdate() { 
        $udao = new MockUserDao();
        $active_users = array("0" =>
                              array (
                                     "user_name"=> "user1",
                                     "email"  => "user1@domain1.com"),
                              "1" =>
                              array (
                                     "user_name"=> "user2",
                                     "email"  => "user1@domain2.com"),
                              "2" =>
                              array (
                                     "user_name"=> "user3",
                                     "email"  => "user1@domain3.com"));
        $udao->setReturnValue('searchByStatus',$active_users);

        $listdao = new MockMailingListDao();
        $active_ml = array(
                           "0" => array ( "list_name"=> "list1"),
                           "1" => array ( "list_name"=> "list2"),
                           "2" => array ( "list_name"=> "list3"),
                           "3" => array ( "list_name"=> "list4"));
        $listdao->setReturnValue('searchAllActiveML',$active_ml);
        $MA = new BackendAliasesTestVersion($this);
        $MA->setReturnValue('getUserDao', $udao);
        $MA->setReturnValue('getMailingListDao', $listdao);
        $MA->expectOnce('system', array('/usr/bin/newaliases > /dev/null'));
        $MA->setReturnValue('system', true);

        $this->assertEqual($MA->update(),True);
        $aliases=file_get_contents($GLOBALS['alias_file']);
        $this->assertFalse($aliases === false);
        $this->assertPattern("/tuleap-contact/",$aliases,"Codendi-wide aliases not set");
        $this->assertPattern("/list1-bounces:/",$aliases,"ML aliases not set");
        $this->assertPattern("/user3:/",$aliases,"User aliases not set");

        // Cleanup
        unlink($GLOBALS['alias_file']);
    }

}
?>