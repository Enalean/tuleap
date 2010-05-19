<?php
/**
 * This class contains methods used in WebDAV plugin
 *
 * @author ounish
 *
 */
class WebDAVUtils {

    protected static $instance;

    /**
     * We don't permit an explicit call of the constructor! (like $utils = new WebDAVUtils())
     *
     * @return void
     */
    private function __construct() {
    }

    /**
     * We don't permit cloning the singleton (like $webdavutils = clone $utils)
     *
     * @return void
     */
    private function __clone() {
    }

    /**
     * Returns the instance of WebDAVUtils
     *
     * @return WebDAVUtils
     */
    public static function getInstance() {

        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;

    }

    /**
     * Extracts the Id from the name
     * For example : 38-Package_v_1.0 should return 38
     *
     * @param String $name
     *
     * @return Integer
     */
    function extractId($name) {

        $tmp = explode('-', $name);
        return (int)$tmp[0];

    }

    /**
     * Tests if the user is Superuser, project admin or File release admin
     *
     * @param User $user
     * @param Integer $groupId
     *
     * @return Boolean
     */
    function userIsAdmin($user, $groupId) {

        // A refers to admin
        // R2 refers to File release admin
        return ($user->isSuperUser() || $user->isMember($groupId, 'A') || $user->isMember($groupId, 'R2'));

    }

}

?>