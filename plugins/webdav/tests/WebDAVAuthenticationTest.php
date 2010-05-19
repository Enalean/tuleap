<?php
/**
 * This is the unit test of WebDAVAuthentication
 */

require_once (dirname(__FILE__).'/../../../src/common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');
require_once (dirname(__FILE__).'/../../../src/common/user/User.class.php');
Mock::generate('User');
require_once (dirname(__FILE__).'/../include/WebDAVAuthentication.class.php');
Mock::generatePartial(
    'WebDAVAuthentication',
    'WebDAVAuthenticationTestVersion',
array('issetUsername', 'setHeader', 'getUsername', 'getPassword', 'getUser', 'errorWrongInput')
);

class WebDAVAuthenticationTest extends UnitTestCase {

    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function WebDAVAuthenticationTest($name = 'WebDAVAuthenticationTest') {
        $this->UnitTestCase($name);
    }

    function setUp() {

        $GLOBALS['Language'] = new MockBaseLanguage($this);

    }

    function tearDown() {

        unset($GLOBALS['Language']);

    }

    /**
     * Testing when user gives only the username
     */
    function testAuthenticateFailureWithOnlyUsername() {

        $webDAVAuthentication = new WebDAVAuthenticationTestVersion($this);
        $webDAVAuthentication->setReturnValue('issetUsername', true);
        $webDAVAuthentication->setReturnValue('getUsername', 'username');
        $webDAVAuthentication->setReturnValue('getPassword', null);
        $user = new MockUser();
        $user->setReturnValue('isAnonymous', true);
        $webDAVAuthentication->setReturnValue('getUser', $user);
        $this->assertEqual($webDAVAuthentication->authenticate(), null);

    }

    /**
     * Testing when the user gives only the password
     */
    function testAuthenticateFailureWithOnlyPassword() {

        $webDAVAuthentication = new WebDAVAuthenticationTestVersion($this);
        $webDAVAuthentication->setReturnValue('issetUsername', true);
        $webDAVAuthentication->setReturnValue('getUsername', null);
        $webDAVAuthentication->setReturnValue('getPassword', 'password');
        $user = new MockUser();
        $user->setReturnValue('isAnonymous', true);
        $webDAVAuthentication->setReturnValue('getUser', $user);
        $this->assertEqual($webDAVAuthentication->authenticate(), null);

    }

    /**
     * Testing when the user gives a wrong username or password
     */
    function testAuthenticateFailureWithWrongUsernameAndPassword() {

        $webDAVAuthentication = new WebDAVAuthenticationTestVersion($this);
        $webDAVAuthentication->setReturnValue('issetUsername', true);
        $webDAVAuthentication->setReturnValue('getUsername', 'username');
        $webDAVAuthentication->setReturnValue('getPassword', 'password');
        $user = new MockUser();
        $user->setReturnValue('isAnonymous', true);
        $webDAVAuthentication->setReturnValue('getUser', $user);
        $this->assertEqual($webDAVAuthentication->authenticate(), null);

    }

    /**
     * Testing when the user is authenticated as anonymous
     */
    function testAuthenticateSuccessWithAnonymousUser() {

        $webDAVAuthentication = new WebDAVAuthenticationTestVersion($this);
        $webDAVAuthentication->setReturnValue('issetUsername', true);
        $webDAVAuthentication->setReturnValue('getUsername', null);
        $webDAVAuthentication->setReturnValue('getPassword', null);
        $user = new MockUser();
        $user->setReturnValue('isAnonymous', true);
        $webDAVAuthentication->setReturnValue('getUser', $user);
        $this->assertEqual($webDAVAuthentication->authenticate(), $user);

    }

    /**
     * Testing when the user is authenticated as a registered user
     */
    function testAuthenticateSuccessWithNotAnonymousUser() {

        $webDAVAuthentication = new WebDAVAuthenticationTestVersion($this);
        $webDAVAuthentication->setReturnValue('issetUsername', true);
        $webDAVAuthentication->setReturnValue('getUsername', 'username');
        $webDAVAuthentication->setReturnValue('getPassword', 'password');
        $user = new MockUser();
        $user->setReturnValue('isAnonymous', false);
        $webDAVAuthentication->setReturnValue('getUser', $user);
        $this->assertEqual($webDAVAuthentication->authenticate(), $user);

    }

}

?>