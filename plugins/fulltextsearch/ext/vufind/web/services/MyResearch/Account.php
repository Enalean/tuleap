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

require_once "Action.php";

require_once 'sys/User.php';

require_once 'Mail/RFC822.php';


class Account extends Action
{
    function __construct()
    {
    }

    function launch()
    {
        global $interface;
        global $configArray;
        
        // Don't allow account creation if a non-DB authentication method
        // is being used!!
        if ($configArray['Authentication']['method'] !== 'DB') {
            header('Location: Home');
            die();
        }

        if (isset($_POST['submit'])) {
            $result = $this->processInput();
            if (PEAR::isError($result)) {
                $interface->assign('message', $result->getMessage());
                $interface->assign('formVars', $_POST);
                $interface->setTemplate('account.tpl');
                $interface->display('layout.tpl');
            } else {
                // Now that the account is created, log the user in:
                UserAccount::login();
                header('Location: Home');
                die();
            }
        } else {
            $interface->setTemplate('account.tpl');
            $interface->display('layout.tpl');
        }
    }
    
    function processInput()
    {
        // Validate Input
        if (trim($_POST['username']) == '') {
            return new PEAR_Error('Username cannot be blank');
        }
        if (trim($_POST['password']) == '') {
            return new PEAR_Error('Password cannot be blank');
        }
        if ($_POST['password'] != $_POST['password2']) {
            return new PEAR_Error('Passwords do not match');
        }
        if (!Mail_RFC822::isValidInetAddress($_POST['email'])) {
            return new PEAR_Error('Email address is invalid');
        }

        // Create Account
        $user = new User();
        $user->username = $_POST['username'];
        if (!$user->find()) {
            // No username match found -- check for duplicate email:
            $user = new User();
            $user->email = $_POST['email'];
            if (!$user->find()) {
                // We need to reassign the username since we cleared it out when
                // we did the search for duplicate email addresses:
                $user->username = $_POST['username'];
                $user->password = $_POST['password'];
                $user->firstname = $_POST['firstname'];
                $user->lastname = $_POST['lastname'];
                $user->created = date('Y-m-d h:i:s');
                $user->insert();
            } else {
                return new PEAR_Error('That email address is already used');
            }
        } else {
            return new PEAR_Error('That username is already taken');
        }
        
        return true;
    }
}

?>