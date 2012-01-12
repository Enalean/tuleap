<?php

require_once 'common/backend/BackendSVNModPerl.class.php';

require_once('common/dao/ServiceDao.class.php');
Mock::generate('ServiceDao');

class BackendSVNModPerlTest extends UnitTestCase {
    
    private function GivenAnApacheAuthenticationConfForGuineaPigProject() {
        $backend        = TestHelper::getPartialMock('BackendSVNModPerl', array());
        $project_db_row = array('unix_group_name' => 'gpig',
                                'group_name'      => 'Guinea Pig',
                                'group_id'        => 101);
        
        return $backend->getProjectSVNApacheConfAuth($project_db_row);        
    }
    
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
    
    private function GivenAFullApacheConf() {
        $backend  = TestHelper::getPartialMock('BackendSVNModPerl', array('_getServiceDao'));
        $dar      = TestHelper::arrayToDar(array('unix_group_name' => 'gpig',
                                                 'group_name'      => 'Guinea Pig',
                                                 'group_id'        => 101),
                                           array('unix_group_name' => 'garden',
                                                 'group_name'      => 'The Garden Project',
                                                 'group_id'        => 102));
        
        $dao = new MockServiceDao();
        $dao->setReturnValue('searchActiveUnixGroupByUsedService', $dar);
        $backend->setReturnValue('_getServiceDao', $dao);
        
        return $backend->getApacheConf();
    }
    
    function testFullConfShouldWrapEveryThing() {
        $conf = $this->GivenAFullApacheConf();
        //echo '<pre>'.htmlentities($conf).'</pre>';
        
        $this->assertPattern('/PerlLoadModule Apache::Codendi/', $conf);
        $this->assertNoPattern('/AuthMYSQLEnable/', $conf);
        $this->ThenThereAreTwoLocationDefinedGpigAndGarden($conf);
    }
    
    private function ThenThereAreTwoLocationDefinedGpigAndGarden($conf) {
        $matches = array();
        preg_match_all('%<Location /svnroot/([^>]*)>%', $conf, $matches);
        $this->assertEqual($matches[1][0], 'gpig');
        $this->assertEqual($matches[1][1], 'garden');
    }
}

?>
