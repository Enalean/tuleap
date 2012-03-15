<?php
require_once '../../prepend.inc.php';
require_once 'PHPUnit/Framework.php';
require_once 'sys/authn/AuthenticationFactory.php';
require_once 'sys/authn/UnknownAuthenticationMethodException.php';

class AuthenticationFactoryTest extends PHPUnit_Framework_TestCase {
	
	public function test_authN_handler_is_not_a_string(){
		try {
    		$authN = AuthenticationFactory::initAuthentication();
        } catch (Exception $expected) {
			return;
        }
        $this->fail('An expected UnknownAuthenticationMethodException has not been raised.');
	}
	
	public function test_authN_method_does_not_exist(){
		try {
    		$authN = AuthenticationFactory::initAuthentication('AuthenticationHandlerDoesNotExist');
        } catch (UnknownAuthenticationMethodException $expected) {
			return;
        }
        $this->fail('An expected Exception has not been raised.');
	}
	
	public function test_invoke_shibboleth_authN_handler(){
	    try {
		  $authN = AuthenticationFactory::initAuthentication('Shibboleth');
		  $this->assertNotNull($authN);
	    } catch (Exception $unexpected){
	        $this->fail('An unexpected exception has been raised:' . $unexpected);
	    }
	    return;
	}

    public function test_invoke_ldap_authN_handler(){
        try {
		  $authN = AuthenticationFactory::initAuthentication('LDAP');
		  $this->assertNotNull($authN);
	    } catch (Exception $unexpected){
	        $this->fail('An unexpected exception has been raised:' . $unexpected);
	    }
	    return;
    }

    public function test_invoke_database_authN_handler(){
        try {
		  $authN = AuthenticationFactory::initAuthentication('DB');
		  $this->assertNotNull($authN);
	    } catch (Exception $unexpected){
	        $this->fail('An unexpected exception has been raised:' . $unexpected);
	    }
	    return;
    }

    public function test_invoke_SIP2_authN_handler(){
        try {
		  $authN = AuthenticationFactory::initAuthentication('SIP2');
		  $this->assertNotNull($authN);
	    } catch (Exception $unexpected){
	        $this->fail('An unexpected exception has been raised:' . $unexpected);
	    }
	    return;
    }

    public function test_invoke_ILS_authN_handler(){
        try {
		  $authN = AuthenticationFactory::initAuthentication('ILS');
		  $this->assertNotNull($authN);
	    } catch (Exception $unexpected){
	        $this->fail('An unexpected exception has been raised:' . $unexpected);
	    }
	    return;
    }
}
?>