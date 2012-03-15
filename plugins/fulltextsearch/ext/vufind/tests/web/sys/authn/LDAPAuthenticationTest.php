<?php
require_once '../../prepend.inc.php';
require_once 'PEAR.php';
require_once 'PHPUnit/Framework.php';
require_once 'sys/authn/LDAPAuthentication.php';
require_once 'sys/authn/IOException.php';

class LDAPAuthenticationTest extends PHPUnit_Framework_TestCase {

    private $username = "testuser";     // valid LDAP username
    private $password = "testpass";     // valid LDAP password
    
    public function setUp(){
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
            $authN = new LDAPAuthentication();
            $this->assertTrue(PEAR::isError($authN->authenticate()));
        } catch (InvalidArgumentException $unexpected) {
            $this->fail('An unexpected InvalidArgumentException has been raised');
        }
    }

    public function test_with_empty_password(){
        try {
            $_POST['username'] = $this->username;
            $_POST['password'] = '';
            $authN = new LDAPAuthentication();
            $this->assertTrue(PEAR::isError($authN->authenticate()));
        } catch (InvalidArgumentException $unexpected) {
            $this->fail('An unexpected InvalidArgumentException has been raised');
        }
    }

    public function test_with_wrong_credentials(){
        try {
            $_POST['username'] = $this->username;
            $_POST['password'] = 'badpass';
            $authN = new LDAPAuthentication();
            $this->assertTrue(PEAR::isError($authN->authenticate()));
        } catch (IOException $unexpected) {
            $this->fail('Unexpected Exception with: ' . $unexpected->getMessage());
        }
    }

    public function test_with_working_credentials(){
        $_POST['username'] = $this->username;
        $_POST['password'] = $this->password;
        $authN = new LDAPAuthentication();
        $this->assertTrue($authN->authenticate() instanceof User);
    }
}
?>
