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

require_once 'services/MyResearch/lib/User_list.php';
require_once 'services/MyResearch/lib/User.php';

class ListEdit extends Action
{
    private $user;

    function __construct()
    {
        $this->user = UserAccount::isLoggedIn();
    }

    function launch()
    {
        global $interface;
        global $configArray;

        // Depending on context, we may get the record ID that initiated the "add 
        // list" action in a couple of different places -- make sure we check all
        // necessary options before giving up!
        if (!isset($_GET['id']) && isset($_REQUEST['recordId'])) {
            $_GET['id'] = $_REQUEST['recordId'];
        }
        $interface->assign('recordId', isset($_GET['id']) ? $_GET['id'] : false);

        // Check if user is logged in
        if (!$this->user) {
            if (isset($_GET['lightbox'])) {
                $interface->assign('title', $_GET['message']);
                $interface->assign('message', 'You must be logged in first');
                return $interface->fetch('AJAX/login.tpl');
            } else {
                require_once 'Login.php';
                Login::launch();
            }
            exit();
        }

        // Display Page
        if (isset($_GET['lightbox'])) {
            $interface->assign('title', $_GET['message']);
            return $interface->fetch('MyResearch/list-form.tpl');
        } else {
            if (isset($_REQUEST['submit'])) {
                $result = $this->addList();
                if (PEAR::isError($result)) {
                    $interface->assign('listError', $result->getMessage());
                } else {
                    if (!empty($_REQUEST['recordId'])) {
                        $url = '../Record/' . urlencode($_REQUEST['recordId']) . '/Save';
                    } else {
                        $url = 'Home';
                    }
                    header('Location: ' . $url);
                    die();
                }
            }
            $interface->setPageTitle('Create a List');
            $interface->assign('subTemplate', 'list-form.tpl');
            $interface->setTemplate('view-alt.tpl');
            $interface->display('layout.tpl');
        }
    }

    function addList()
    {
        if ($this->user) {
            if (strlen(trim($_REQUEST['title'])) == 0) {
                return new PEAR_Error('list_edit_name_required');
            }
            $list = new User_list();
            $list->title = $_REQUEST['title'];
            $list->description = $_REQUEST['desc'];
            $list->public = $_REQUEST['public'];
            $list->user_id = $this->user->id;
            $list->insert();
            $list->find();
            return $list->id;
        }
    }

}
?>
