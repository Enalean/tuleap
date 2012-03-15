<?php
require_once '../../prepend.inc.php';
require_once 'PEAR.php';
require_once 'PHPUnit/Framework.php';
require_once 'sys/authn/ShibbolethAuthentication.php';
require_once 'services/MyResearch/lib/User.php';

class ShibbolethAuthenticationTest extends PHPUnit_Framework_TestCase {
	
    private $authN;

    public function setUp(){
        $this->authN = new ShibbolethAuthentication();
    }
    
	public function test_authentication_with_invalid_attribute_values() {
        $_SERVER['entitlement'] = 'wrong_Value';
        $_SERVER['unscoped-affiliation'] = 'wrong_Value';
        $this->assertTrue(PEAR::isError($this->authN->authenticate()));
	}
	
	public function test_authentication_with_working_attribute_values() {
        $_SERVER['persistent-id']    = '1234_1234';
	    $_SERVER['entitlement'] = 'urn:mace:dir:entitlement:common-lib-terms';
        $_SERVER['unscoped-affiliation'] = 'member';
        $this->assertTrue($this->authN->authenticate() instanceof User);
	}

	public function test_authentication_with_missing_username() {
	    unset($_SERVER['persistent-id']);
	    $_SERVER['entitlement'] = 'urn:mace:dir:entitlement:common-lib-terms';
        $_SERVER['unscoped-affiliation'] = 'member';
        $this->assertTrue(PEAR::isError($this->authN->authenticate()));
	}
}

?>