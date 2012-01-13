<?php

require_once 'common/backend/SVN_Apache_ModPerl.class.php';


class SVN_Apache_ModPerlTest extends UnitTestCase {
    
    function setUp() {
        $GLOBALS['svn_prefix'] = '/svnroot';
    }
    
    function tearDown() {
        unset($GLOBALS['svn_prefix']);
    }
    
    private function GivenAnApacheAuthenticationConfForGuineaPigProject() {
        $project_db_row = array('unix_group_name' => 'gpig',
                                'group_name'      => 'Guinea Pig',
                                'group_id'        => 101);
        $apacheConf = new SVN_Apache_ModPerl(array($project_db_row));
        return $apacheConf->getFullConf();
    }
    
    function testGetSVNApacheConfHeadersShouldInsertModPerl() {
        $conf = $this->GivenAnApacheAuthenticationConfForGuineaPigProject();
        
        $this->assertPattern('/PerlLoadModule Apache::Codendi/', $conf);
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
        $projects = array(array('unix_group_name' => 'gpig',
                                'group_name'      => 'Guinea Pig',
                                'group_id'        => 101),
                          array('unix_group_name' => 'garden',
                                'group_name'      => 'The Garden Project',
                                 'group_id'        => 102));
        $apacheConf = new SVN_Apache_ModPerl($projects);
        return $apacheConf->getFullConf();
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
