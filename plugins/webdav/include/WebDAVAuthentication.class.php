<?php
/**
 * Class of authentication
 *
 * @author ounish
 *
 */
class WebDAVAuthentication {

    /**
     * Authentication method
     *
     * Returns the authenticated user
     *
     * @return User
     */
    function authenticate() {

        // test if username field is empty
        if (!$this->issetUsername()) {
            $this->setHeader();
        } else {
            $username = $this->getUsername();
            $password = $this->getPassword();
            $user = $this->getUser($username, $password);
            // if the user entered a wrong username or password
            // if blank the user is considered as anonymous
            if ($user->isAnonymous() && ($username || $password)) {
                $this->setHeader();
            } else {
                return $user;
            }
        }

    }

    /**
     * Returns whether the username field is empty or not
     *
     * @return Boolean
     */
    function issetUsername() {

        return isset($_SERVER['PHP_AUTH_USER']);

    }

    /**
     * Sets the authentication header
     *
     * @return void
     */
    function setHeader() {

        header('WWW-Authenticate: Basic realm="My Realm"');
        header('HTTP/1.0 401 Unauthorized');

        // text returned when user hit cancel
        echo $GLOBALS['Language']->getText('plugin_webdav_common', 'authentication_required');

        // The HTTP_BasicAuth (and digest) will return a 401 statuscode.
        // If there is no die() after that, the server will just do it's thing as usual
        // and override it with it's own statuscode (200, 404, 207, 201, or whatever was appropriate).
        // So the die() actually makes sure that the php script doesn't continue if the client
        // has an incorrect or no username and password.
        die();

    }

    /**
     * Returns the content of username field
     *
     * @return String
     */
    function getUsername() {

        return $_SERVER['PHP_AUTH_USER'];

    }

    /**
     * Returns the content of password field
     *
     * @return String
     */
    function getPassword() {

        return $_SERVER['PHP_AUTH_PW'];

    }

    /**
     * Returns the authenticated user or anonymous user
     *
     * @param String $username
     *
     * @param String $password
     *
     * @return User
     */
    function getUser($username, $password) {

        return UserManager::instance()->login($username, $password);

    }

}

?>