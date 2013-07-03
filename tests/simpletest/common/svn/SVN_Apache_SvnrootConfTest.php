<?php

require_once 'common/include/Config.class.php';
require_once 'common/svn/SVN_Apache_SvnrootConf.class.php';

mock::generate('EventManager');

class SVN_Apache_SvnrootConfTest extends TuleapTestCase {
    
    function setUp() {
        Config::store();
        $GLOBALS['sys_name']   = 'Platform';
        $GLOBALS['sys_dbhost'] = 'db_server';
        $GLOBALS['sys_dbname'] = 'db';
        $GLOBALS['svn_prefix'] = '/bla';
        $GLOBALS['sys_dbauth_user']   = 'dbauth_user';
        $GLOBALS['sys_dbauth_passwd'] = 'dbauth_passwd';
    }
    
    function tearDown() {
        Config::restore();
        unset($GLOBALS['sys_name']);
        unset($GLOBALS['sys_dbname']);
        unset($GLOBALS['sys_dbhost']);
        unset($GLOBALS['svn_prefix']);
        unset($GLOBALS['sys_dbauth_user']);
        unset($GLOBALS['sys_dbauth_passwd']);
    }
    
    /**
     * @return SVN_Apache_SvnrootConf 
     */
    private function GivenSvnrootForTwoGroups() {
        $projects = array(array('unix_group_name' => 'gpig',
                                'group_name'      => 'Guinea Pig',
                                'group_id'        => 101),
                          array('unix_group_name' => 'garden',
                                'group_name'      => 'The Garden Project',
                                'group_id'        => 102));

        $factory = TestHelper::getPartialMock('SVN_Apache_Auth_Factory', array('getEventManager'));
        $factory->setReturnValue('getEventManager', new MockEventManager());

        return new SVN_Apache_SvnrootConf($factory, $projects);
    }
    
    private function GivenAFullApacheConfWithModMysql() {
        $backend = $this->GivenSvnrootForTwoGroups();
        return $backend->getFullConf();
    }
    
    function testFullConfShouldWrapEveryThing() {
        $conf = $this->GivenAFullApacheConfWithModMysql();
        //echo '<pre>'.htmlentities($conf).'</pre>';
        
        $this->assertNoPattern('/PerlLoadModule Apache::Tuleap/', $conf);
        $this->assertPattern('/AuthMYSQLEnable/', $conf);
        $this->ThenThereAreTwoLocationDefinedGpigAndGarden($conf);
        $this->ThenThereAreOnlyOneCustomLogStatement($conf);
    }
    
    private function ThenThereAreTwoLocationDefinedGpigAndGarden($conf) {
        $matches = array();
        preg_match_all('%<Location /svnroot/([^>]*)>%', $conf, $matches);
        $this->assertEqual($matches[1][0], 'gpig');
        $this->assertEqual($matches[1][1], 'garden');
    }
    
    private function ThenThereAreOnlyOneCustomLogStatement($conf) {
        preg_match_all('/CustomLog/', $conf, $matches);
        $this->assertEqual(1, count($matches[0]));
    }
    
    function GivenAFullApacheConfWithModPerl() {
        Config::set(SVN_Apache_SvnrootConf::CONFIG_SVN_AUTH_KEY, SVN_Apache_SvnrootConf::CONFIG_SVN_AUTH_PERL);
        $svnroot = $this->GivenSvnrootForTwoGroups();
        return $svnroot->getFullConf();
    }
    
    function testFullApacheConfWithModPerl() {
        $conf = $this->GivenAFullApacheConfWithModPerl();
        //echo '<pre>'.htmlentities($conf).'</pre>';
        
        $this->assertPattern('/PerlLoadModule Apache::Tuleap/', $conf);
        $this->assertNoPattern('/AuthMYSQLEnable/', $conf);
        $this->ThenThereAreTwoLocationDefinedGpigAndGarden($conf);
        $this->ThenThereAreOnlyOneCustomLogStatement($conf);
    }

    public function itHasALogFileFromConfiguration() {
        Config::store();
        Config::set(SVN_Apache_SvnrootConf::CONFIG_SVN_LOG_PATH, '${APACHE_LOG_DIR}/tuleap_svn.log');

        $conf = $this->GivenAFullApacheConfWithModPerl();
        $this->assertPattern('%\${APACHE_LOG_DIR}/tuleap_svn\.log%', $conf);

        Config::restore();
    }
}

?>
