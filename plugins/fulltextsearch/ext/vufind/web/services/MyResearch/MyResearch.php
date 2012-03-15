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

require_once 'Action.php';

require_once 'CatalogConnection.php';

require_once 'services/MyResearch/lib/User.php';
require_once 'services/MyResearch/lib/Resource.php';

class MyResearch extends Action
{
    protected $db;
    protected $catalog;

    function __construct()
    {
        global $interface;
        global $configArray;
        global $user;
        
        if (!UserAccount::isLoggedIn()) {
            require_once 'Login.php';
            Login::launch();
            exit();
        }
        
        // Setup Search Engine Connection
        $class = $configArray['Index']['engine'];
        $this->db = new $class($configArray['Index']['url']);
        if ($configArray['System']['debug']) {
            $this->db->debug = true;
        }

        // Connect to Database
        $this->catalog = new CatalogConnection($configArray['Catalog']['driver']);
        
        // Register Library Catalog Account
        if (isset($_POST['submit']) && !empty($_POST['submit'])) {
            if ($this->catalog && isset($_POST['cat_username']) && isset($_POST['cat_password'])) {
                $result = $this->catalog->patronLogin($_POST['cat_username'], $_POST['cat_password']);
                if ($result && !PEAR::isError($result)) {
                    $user->cat_username = $_POST['cat_username'];
                    $user->cat_password = $_POST['cat_password'];
                    $user->update();
                    UserAccount::updateSession($user);
                    $interface->assign('user', $user);
                } else {
                    $interface->assign('loginError', 'Invalid Patron Login');
                }
            }
        }
    }

    /**
     * Log the current user into the catalog using stored credentials; if this 
     * fails, clear the user's stored credentials so they can enter new, corrected
     * ones.
     *
     * @access  protected
     * @return  mixed               $user array (on success) or false (on failure)
     */
    protected function catalogLogin()
    {
        global $user;

        if ($this->catalog->status) {
            if ($user->cat_username) {
                $patron = $this->catalog->patronLogin($user->cat_username,
                    $user->cat_password);
                if (empty($patron) || PEAR::isError($patron)) {
                    // Problem logging in -- clear user credentials so they can be
                    // prompted again; perhaps their password has changed in the
                    // system!
                    unset($user->cat_username);
                    unset($user->cat_password);
                } else {
                    return $patron;
                }
            }
        }
        
        return false;
    }
}

?>
