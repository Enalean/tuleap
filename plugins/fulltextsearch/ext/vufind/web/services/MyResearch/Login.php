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

class Login extends Action
{
    function __construct()
    {
    }

    function launch($msg = null)
    {
        global $interface;
        global $configArray;
        global $module;
        global $action;

        // We should never access this module directly -- this is called by other
        // actions as a support function.  If accessed directly, just redirect to
        // the MyResearch home page.
        if ($module == 'MyResearch' && $action == 'Login') {
            header('Location: Home');
            die();
        }

        // Assign the followup task to come back to after they login -- note that
        //     we need to check for a pre-existing followup task in case we've
        //     looped back here due to an error (bad username/password, etc.).
        $followup = isset($_REQUEST['followup']) ? $_REQUEST['followup'] : $action;

        // Don't go to the trouble if we're just logging in to the Home action
        if ($followup != 'Home') {
            $interface->assign('followup', $followup);
            $interface->assign('followupModule', isset($_REQUEST['followupModule']) ? 
                $_REQUEST['followupModule'] : $module);

            // Special case -- if user is trying to view a private list, we need to
            // attach the list ID to the action:
            $finalAction = $action;
            if ($finalAction == 'MyList') {
                $finalAction .= '/' . $_GET['id'];
            }
            $interface->assign('followupAction', isset($_REQUEST['followupAction']) ? 
                $_REQUEST['followupAction'] : $finalAction);

            // If we have a save or delete action, create the appropriate recordId
            //     parameter.  If we've looped back due to user error and already have
            //     a recordId parameter, remember it for future reference.
            if (isset($_REQUEST['delete'])) {
                $mode = !isset($_REQUEST['mode']) ? 
                    '' : '&mode=' . urlencode($_REQUEST['mode']);
                $interface->assign('recordId', 'delete=' . 
                    urlencode($_REQUEST['delete']) . $mode);
            } else if (isset($_REQUEST['save'])) {
                $mode = !isset($_REQUEST['mode']) ? 
                    '' : '&mode=' . urlencode($_REQUEST['mode']);
                $interface->assign('recordId', 'save=' . 
                    urlencode($_REQUEST['save']) . $mode);
            } else if (isset($_REQUEST['recordId'])) {
                $interface->assign('recordId', $_REQUEST['recordId']);
            }

            // comments and tags also need to be preserved if present
            if (isset($_REQUEST['comment'])) {
                $interface->assign('comment', $_REQUEST['comment']);
            }
        }
        $interface->assign('message', $msg);
        if (isset($_REQUEST['username'])) {
            $interface->assign('username', $_REQUEST['username']);
        }
        $interface->setTemplate('login.tpl');
        $interface->display('layout.tpl');
    }
}

?>
