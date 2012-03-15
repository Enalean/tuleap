<?php
require_once '../../prepend.inc.php';
require_once 'PHPUnit/Framework.php';
require_once 'sys/authn/ShibbolethConfigurationParameter.php';

class ShibbolethConfigurationParameterTest extends PHPUnit_Framework_TestCase {

    private $pathToTestConfigurationFiles;
    
    public function setUp(){
        $this->pathToTestConfigurationFiles = $_SESSION['pathToTestConfigurationFiles'];
    }
    
    public function test_without_attributes(){
        try {
            $shibbolethConfigurationParameter = new shibbolethConfigurationParameter($this->pathToTestConfigurationFiles . "/authn/shib/no-user-attributes-config.ini");
            $userAttributes = $shibbolethConfigurationParameter->getUserAttributes();
        } catch (UnexpectedValueException $expected) {
            return;
        }
        $this->fail('An expected UnexpectedValueException has not been raised');
    }
    
    public function test_with_missing_attribute_value(){
       try {
            $shibbolethConfigurationParameter = new shibbolethConfigurationParameter($this->pathToTestConfigurationFiles . "/authn/shib/attribute-value-is-missing-config.ini");
            $userAttributes = $shibbolethConfigurationParameter->getUserAttributes();
        } catch (UnexpectedValueException $expected) {
            return;
        }
        $this->fail('An expected UnexpectedValueException has not been raised');
    }
   
    public function test_without_username(){
        try {
            $shibbolethConfigurationParameter = new shibbolethConfigurationParameter($this->pathToTestConfigurationFiles . "/authn/shib/attribute-but-missing-username-config.ini");
            $userAttributes = $shibbolethConfigurationParameter->getUserAttributes();
        } catch (UnexpectedValueException $expected) {
            return;
        }
        $this->fail('An expected UnexpectedValueException has not been raised');
    }
   
    public function test_with_correct_attribute_list_and_username(){
        try{
            $shibbolethConfigurationParameter = new shibbolethConfigurationParameter();
            $userAttributes = $shibbolethConfigurationParameter->getUserAttributes();
            $this->assertTrue(is_array($userAttributes));
            $this->assertTrue(count($userAttributes) > 0);
            foreach($userAttributes as $key => $value){
                echo "key = {$key}, value = {$value}\n";
            }
            
        } catch (Exception $unexpected){
            $this->fail('Unexpected Exception has been raised!');
        }
    }
}
?>