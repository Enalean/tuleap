<?php
require_once '../../prepend.inc.php';
require_once 'PEAR.php';
require_once 'PHPUnit/Framework.php';
require_once 'sys/authn/ILSAuthentication.php';
require_once 'sys/authn/IOException.php';
require_once 'sys/authn/ConfigurationReader.php';

class ILSAuthenticationTest extends PHPUnit_Framework_TestCase {

    private $username = 'testuser';       // a valid username
    private $password = 'testpass';       // a valid password
    
    public function setUp(){
        // Set up the global config array required by the ILS driver:
        global $configArray;
        $configArray = parse_ini_file('conf/config.ini', true);
        
	    // Setup Local Database Connection
        define('DB_DATAOBJECT_NO_OVERLOAD', 0);
        $options =& PEAR::getStaticProperty('DB_DataObject', 'options');
        $configurationReader = new ConfigurationReader();
        $options = $configurationReader->readConfiguration('Database');
    }

    public function test_with_empty_username(){
        try {
            $_POST['username'] = '';
            $_POST['password'] = $this->password;
            $authN = new ILSAuthentication();
            $this->assertTrue(PEAR::isError($authN->authenticate()));
        } catch (InvalidArgumentException $unexpected) {
            $this->fail('An unexpected InvalidArgumentException has been raised');
        }
    }

    public function test_with_empty_password(){
        try {
            $_POST['username'] = $this->username;
            $_POST['password'] = '';
            $authN = new ILSAuthentication();
            $this->assertTrue(PEAR::isError($authN->authenticate()));
        } catch (InvalidArgumentException $unexpected) {
            $this->fail('An unexpected InvalidArgumentException has been raised');
        }
    }

    public function test_with_wrong_credentials(){
        try {
            $_POST['username'] = $this->username;
            $_POST['password'] = 'test';
            $authN = new ILSAuthentication();
            $this->assertTrue(PEAR::isError($authN->authenticate()));
        } catch (IOException $unexpected) {
            $this->fail('Unexpected Exception with: ' . $unexpected->getMessage());
        }
    }

    public function test_with_working_credentials(){
        $_POST['username'] = $this->username;
        $_POST['password'] = $this->password;
        $authN = new ILSAuthentication();
        $this->assertTrue($authN->authenticate() instanceof User);
    }
}
?>
