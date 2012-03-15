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
require_once 'services/MyResearch/lib/FavoriteHandler.php';

/**
 * This class does not use MyResearch base class (we don't need to connect to
 * the catalog, and we need to bypass the "redirect if not logged in" logic to
 * allow public lists to work properly).
 * @version  $Revision$
 */
class MyList extends Action
{
    function launch()
    {
        global $configArray;
        global $interface;
        global $user;

        // Fetch List object
        $list = User_list::staticGet($_GET['id']);

        // Ensure user have privs to view the list
        if (!$list->public && !UserAccount::isLoggedIn()) {
            require_once 'Login.php';
            Login::launch();
            exit();
        }
        if (!$list->public && $list->user_id != $user->id) {
            PEAR::raiseError(new PEAR_Error(translate('list_access_denied')));
        }

        // Delete Resource (but only if list owner is logged in!)
        if (isset($_GET['delete']) && $user->id == $list->user_id) {
            $resource = Resource::staticGet('record_id', $_GET['delete']);
            $list->removeResource($resource);
        }

        // Send list to template so title/description can be displayed:
        $interface->assign('list', $list);

        // Build Favorites List
        $favorites = $list->getResources(isset($_GET['tag']) ? $_GET['tag'] : null);

        // Load the User object for the owner of the list (if necessary):
        if ($user && $user->id == $list->user_id) {
            $listUser = $user;
        } else {
            $listUser = User::staticGet($list->user_id);
        }

        // Create a handler for displaying favorites and use it to assign
        // appropriate template variables:
        $allowEdit = ($user->id == $list->user_id);
        $favList = new FavoriteHandler($favorites, $listUser, $list->id, $allowEdit);
        $favList->assign();

        $interface->setTemplate('list.tpl');
        $interface->display('layout.tpl');
    }
}

?>
