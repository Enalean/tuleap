<?php
require_once '../../prepend.inc.php';
require_once 'PHPUnit/Framework.php';
require_once 'sys/authn/AuthenticationFactory.php';
require_once 'sys/authn/UnknownAuthenticationMethodException.php';

class AuthenticationTest extends PHPUnit_Framework_TestCase {
    
    public function testAuthentication(){
        try {
            $_SERVER['entitlement'] = 'urn:mace:dir:entitlement:common-lib-terms';
            $_SERVER['persistent_id']    = '1234_1234';
            $_SERVER['unscoped_affiliation'] = 'member';
            $authN = AuthenticationFactory::initAuthentication('Shibboleth');
            $user = $authN->authenticate();
            print_r($user);
        } catch (Exception $expected) {
            $this->fail('An expected UnknownAuthenticationMethodException has not been raised.');
        }
    }    
}
?>