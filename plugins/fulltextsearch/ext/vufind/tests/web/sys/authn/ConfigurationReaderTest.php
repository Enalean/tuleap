<?php
require_once '../../prepend.inc.php';
require_once 'PHPUnit/Framework.php';
require_once 'sys/authn/ConfigurationReader.php';
require_once 'sys/authn/IOException.php';
require_once 'sys/authn/FileParseException.php';

class ConfigurationReaderTest extends PHPUnit_Framework_TestCase {

    private $pathToTestConfigurationFile;
    
    public function setUp(){
         $this->pathToTestConfigurationFile = $_SESSION['pathToTestConfigurationFiles'];
    }
    
    public function test_no_configuration_file_found() {
        try {
            $configurationReader = new ConfigurationReader($this->pathToTestConfigurationFile . "/authn/shib/this-file-do-not-exist.ini");
        } catch (IOException $expected) {
            return;
        }
        
        $this->fail('An expected IOException has not been raised');
    }
    
    public function test_unknown_section(){
        try {
            $configurationReader = new ConfigurationReader($this->pathToTestConfigurationFile . "/authn/shib/no-shibboleth-section-config.ini");
            $section = $configurationReader->readConfiguration("Shibboleth");
        } catch (UnexpectedValueException $expected) {
            return;
        }
        $this->fail('An expected UnexpectedValueException has not been raised');
    }
    
    public function test_empty_section(){
        try {
            $configurationReader = new ConfigurationReader($this->pathToTestConfigurationFile . "/authn/shib/empty-shibboleth-section-config.ini");
            $section = $configurationReader->readConfiguration("Shibboleth");
        } catch (UnexpectedValueException $expected) {
            return;
        }
        $this->fail('An expected UnexpectedValueException has not been raised');
    }
    
    public function test_with_attribute_value_but_missing_attributename(){
       try {
            $configurationReader = new ConfigurationReader($this->pathToTestConfigurationFile . "/authn/shib/attribute-value-but-missing-attributename-config.ini");
            $section = $configurationReader->readConfiguration("Shibboleth");
        } catch (FileParseException $expected) {
            return;
        }
        $this->fail('An expected FileParseException has not been raised');
    }
    
    
    public function test_with_correct_configuration_file(){
        try {
            $configurationReader = new ConfigurationReader($this->pathToTestConfigurationFile . "/config.ini");
            $section = $configurationReader->readConfiguration("Shibboleth");
            $this->assertEquals($section['username'], "persistent_id");
            $this->assertEquals($section['userattribute_1'], "entitlement");
            $this->assertEquals($section['userattribute_value_1'], "urn:mace:dir:entitlement:common-lib-terms");
            $this->assertEquals($section['userattribute_2'], "unscoped_affiliation");
            $this->assertEquals($section['userattribute_value_2'], "member");
        } catch (Exception $unexpected) {
            $this->fail($unexpected);
        }
    }
    
    public function test_without_commited_configuration_file(){
        try {
            $configurationReader = new ConfigurationReader();
            $section = $configurationReader->readConfiguration("Shibboleth");
            $this->assertEquals($section['username'], "persistent-id");
            $this->assertEquals($section['userattribute_1'], "entitlement");
            $this->assertEquals($section['userattribute_value_1'], "urn:mace:dir:entitlement:common-lib-terms");
            $this->assertEquals($section['userattribute_2'], "unscoped-affiliation");
            $this->assertEquals($section['userattribute_value_2'], "member");
        } catch (Exception $unexpected) {
            $this->fail($unexpected);
        }
    }
}
?>