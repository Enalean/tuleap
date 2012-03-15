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

require_once 'Home.php';

class Edit extends Action
{
    function __construct()
    {
    }

    private function getTags($user, $listId)
    {
        $tagStr = '';
        $myTagList = $user->getTags($_GET['id'], $listId);
        if (is_array($myTagList) && count($myTagList) > 0) {
            foreach($myTagList as $myTag) {
                if (strstr($myTag->tag, ' ')) {
                    $tagStr .= "\"$myTag->tag\" ";
                } else {
                    $tagStr .= "$myTag->tag ";
                }
            }
        }
        return $tagStr;
    }
    
    private function saveChanges($user)
    {
        $resource = Resource::staticGet('record_id', $_GET['id']);
        
        // Loop through the list of lists on the edit screen:
        foreach($_POST['lists'] as $listId) {
            // Create a list object for the current list:
            $list = new User_list();
            if ($listId != '') {
                $list->id = $listId;
            } else {
                PEAR::raiseError(new PEAR_Error('List ID Missing'));
            }
            
            // Extract tags from the user input:
            preg_match_all('/"[^"]*"|[^ ]+/', $_POST['tags' . $listId], $tagArray);
            
            // Save extracted tags and notes:
            $user->addResource($resource, $list, $tagArray[0], $_POST['notes' . $listId]);
        }
    }
    
    function launch($msg = null)
    {
        global $interface;
        global $configArray;

        if (!($user = UserAccount::isLoggedIn())) {
            require_once 'Login.php';
            Login::launch();
            exit();
        }
        
        // Save Data
        if (isset($_POST['submit'])) {
            $this->saveChanges($user);
            
            // After changes are saved, send the user back to an appropriate page;
            // either the list they were viewing when they started editing, or the
            // overall favorites list.
            if (isset($_GET['list_id'])) {
                $nextAction = 'MyList/' . $_GET['list_id'];
            } else {
                $nextAction = 'Home';
            }
            header('Location: ' . $configArray['Site']['url'] . '/MyResearch/' . $nextAction);
            exit();
        }

        // Setup Search Engine Connection
        $class = $configArray['Index']['engine'];
        $db = new $class($configArray['Index']['url']);
        if ($configArray['System']['debug']) {
            $db->debug = true;
        }

        // Get Record Information
        $details = $db->getRecord($_GET['id']);
        $interface->assign('record', $details);
        
        // Record ID
        $interface->assign('recordId', $_GET['id']);

        // Retrieve saved information about record
        $saved = $user->getSavedData($_GET['id']);
        
        // Add tag information
        $savedData = array();
        foreach($saved as $current) {
            // If we're filtering to a specific list, skip any other lists:
            if (isset($_GET['list_id']) && $current->list_id != $_GET['list_id']) {
                continue;
            }
            $savedData[] = array(
                'listId' => $current->list_id,
                'listTitle' => $current->list_title,
                'notes' => $current->notes,
                'tags' => $this->getTags($user, $current->list_id));
        }

        $interface->assign('savedData', $savedData);
        $interface->assign('listFilter', isset($_GET['list_id']) ? $_GET['list_id'] : null);

        $interface->setTemplate('edit.tpl');
        $interface->display('layout.tpl');
    }
}

?>
