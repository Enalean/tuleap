<?php
require_once '../../prepend.inc.php';
require_once 'PEAR.php';
require_once 'PHPUnit/Framework.php';
require_once 'sys/authn/DatabaseAuthentication.php';
require_once 'sys/authn/ConfigurationReader.php';

class DatabaseAuthenticationTest extends PHPUnit_Framework_TestCase {

    private $databaseAuthN;
    
    public function setUp(){
        // Setup Local Database Connection
        define('DB_DATAOBJECT_NO_OVERLOAD', 0);
        $options =& PEAR::getStaticProperty('DB_DataObject', 'options');
        $configurationReader = new ConfigurationReader();
        $options = $configurationReader->readConfiguration('Database');
        $this->databaseAuthN = new DatabaseAuthentication();
    }

    public function testWithWrongCredentials(){
        $_POST['username'] = 'This';
        $_POST['password'] = 'secret';
        $this->assertTrue(PEAR::isError($this->databaseAuthN->authenticate()));
    }

    public function testWithEmptyCredentials(){
        $_POST['username'] = '';
        $_POST['password'] = '';
        $this->assertTrue(PEAR::isError($this->databaseAuthN->authenticate()));
    }

    public function testWithWorkingCredentials(){
        $_POST['username'] = 'franck';
        $_POST['password'] = 'secret';
        $this->assertFalse(PEAR::isError($this->databaseAuthN->authenticate()));
    }

    public function testWithSQLInjection(){
        $credentials = array('username' => "' OR 1=1 ", 'password' => "' OR 1=1 LIMIT 1");
        $this->assertTrue(PEAR::isError($this->databaseAuthN->authenticate()));
    }
}
?>
