<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once 'XML/Unserializer.php';
require_once 'XML/Serializer.php';

require_once 'sys/authn/AuthenticationFactory.php';

// This is necessary for unserialize
require_once 'services/MyResearch/lib/User.php';

class UserAccount
{
    // Checks whether the user is logged in.
    public static function isLoggedIn()
    {
        if (isset($_SESSION['userinfo'])) {
            return unserialize($_SESSION['userinfo']);
        }
        return false;
    }

    // Updates the user information in the session.
    public static function updateSession($user)
    {
        $_SESSION['userinfo'] = serialize($user);
    }

    // Try to log in the user using current query parameters; return User object 
    // on success, PEAR error on failure.
    public static function login()
    {
        global $configArray;
        
        // Perform authentication:
        $authN = AuthenticationFactory::initAuthentication($configArray['Authentication']['method']);
        $user = $authN->authenticate();
        
        // If we authenticated, store the user in the session:
        if (!PEAR::isError($user)) {
            self::updateSession($user);
        }
        
        // Send back the user object (which may be a PEAR error):
        return $user;
    }
}

?>
