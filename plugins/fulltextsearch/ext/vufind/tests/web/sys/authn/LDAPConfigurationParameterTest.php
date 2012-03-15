<?php
require_once '../../prepend.inc.php';
require_once 'PEAR.php';
require_once 'PHPUnit/Framework.php';
require_once 'sys/authn/LDAPConfigurationParameter.php';

class LDAPConfigurationParameterTest extends PHPUnit_Framework_TestCase {

    public function setUp(){
         $this->pathToTestConfigurationFiles = $_SESSION['pathToTestConfigurationFiles'];
    }

    public function test_with_missing_host(){
        try {
            $ldapConfigurationParameter = new LDAPConfigurationParameter(
            $this->pathToTestConfigurationFiles . "/authn/ldap/without-ldap-host-config.ini");
            $parameters = $ldapConfigurationParameter->getParameter();
        } catch (InvalidArgumentException $expected) {
            return;
        }
        $this->fail('An expected UnexpectedValueException has not been raised');
    }

    public function test_with_missing_port(){
        try {
            $ldapConfigurationParameter = new LDAPConfigurationParameter(
            $this->pathToTestConfigurationFiles . "/authn/ldap/without-ldap-port-config.ini");
            $parameters = $ldapConfigurationParameter->getParameter();
        } catch (InvalidArgumentException $expected) {
            return;
        }
        $this->fail('An expected UnexpectedValueException has not been raised');
    }

    public function test_with_missing_baseDN(){
        try {
            $ldapConfigurationParameter = new LDAPConfigurationParameter($this->pathToTestConfigurationFiles .
                                                                         "/authn/ldap/without-ldap-basedn-config.ini");
            $parameters = $ldapConfigurationParameter->getParameter();
        } catch (InvalidArgumentException $expected) {
            return;
        }
        $this->fail('An expected UnexpectedValueException has not been raised');
    }

    public function test_with_missing_uid(){
        try {
            $ldapConfigurationParameter = new LDAPConfigurationParameter($this->pathToTestConfigurationFiles .
                                                                         "/authn/ldap/without-ldap-uid-config.ini");
            $parameters = $ldapConfigurationParameter->getParameter();
        } catch (InvalidArgumentException $expected) {
            return;
        }
        $this->fail('An expected UnexpectedValueException has not been raised');
    }

    public function test_with_working_parameters(){
        try {
            $ldapConfigurationParameter = new LDAPConfigurationParameter();
            $parameters = $ldapConfigurationParameter->getParameter();
            $this->assertTrue(is_array($parameters));
        } catch (InvalidArgumentException $unexpected) {
             $this->fail("An unexpected UnexpectedValueException has not been raised: {$unexpected}");
        }
    }

    public function test_if_parameter_are_converted_to_lowercase(){
        try {
            $ldapConfigurationParameter = new LDAPConfigurationParameter($this->pathToTestConfigurationFiles .
                                                                         "/authn/ldap/unconverted-parameter-values-config.ini");
            $parameters = $ldapConfigurationParameter->getParameter();
            foreach($parameters as $index => $value){
                if($index == "username"){
                    $this->assertTrue($value == "uid");
                }

                if($index == "college"){
                    $this->assertTrue($value == "employeetype");
                }
            }
        } catch (InvalidArgumentException $unexpected) {
            $this->fail("An unexpected UnexpectedValueException has not been raised: {$unexpected}");
        }
    }

}
?>
