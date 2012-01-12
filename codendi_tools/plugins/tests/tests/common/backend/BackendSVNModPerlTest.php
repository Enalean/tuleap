<?php

require_once 'common/backend/BackendSVNModPerl.class.php';

class BackendSVNTest extends UnitTestCase {
    
    function testGetSVNApacheConfHeadersShouldInsertModPerl() {
        $backend = TestHelper::getPartialMock('BackendSVNModPerl', array());
        
        $this->assertPattern('/PerlLoadModule Apache::Codendi/', $backend->getApacheConfHeaders());
    }
    
    function testGetApacheAuthShouldContainsDefaultValues() {
        $conf = $this->GivenAnApacheAuthenticationConfForGuineaPigProject();
        
        $this->assertPattern('/Require valid-user/', $conf);
        $this->assertPattern('/AuthType Basic/', $conf);
        $this->assertPattern('/AuthName "Subversion Authorization \(Guinea Pig\)"/', $conf);
    }
    
    function testGetApacheAuthShouldSetupPerlAccess() {
        $conf = $this->GivenAnApacheAuthenticationConfForGuineaPigProject();
        
        $this->assertPattern('/PerlAccessHandler/', $conf);
        $this->assertPattern('/CodendiDSN/', $conf);
    }
    
    function testGetApacheAuthShouldNotReferenceAuthMysql() {
        $conf = $this->GivenAnApacheAuthenticationConfForGuineaPigProject();
        
        $this->assertNoPattern('/AuthMYSQLEnable/', $conf);
    }
    
    private function GivenAnApacheAuthenticationConfForGuineaPigProject() {
        $backend        = TestHelper::getPartialMock('BackendSVNModPerl', array());
        $project_db_row = array('unix_group_name' => 'gpig',
                                'group_name'      => 'Guinea Pig',
                                'group_id'        => 101);
        
        return $backend->getProjectSVNApacheConfAuth($project_db_row);        
    }
}

?>
