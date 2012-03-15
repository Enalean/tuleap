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

require_once 'services/MyResearch/lib/User.php';
require_once 'services/MyResearch/lib/Tags.php';
require_once 'services/MyResearch/lib/Resource.php';

class AddTag extends Action {

    private $user;

    function __construct()
    {
        $this->user = UserAccount::isLoggedIn();
    }
    
    function launch()
    {
        global $interface;
        global $configArray;

        $interface->assign('id', $_GET['id']);
        
        // Check if user is logged in
        if (!$this->user) {
            $interface->assign('recordId', $_GET['id']);
            $interface->assign('followupModule', 'Record');
            $interface->assign('followupAction', 'AddTag');
            if (isset($_GET['lightbox'])) {
                $interface->assign('title', $_GET['message']);
                $interface->assign('message', 'You must be logged in first');
                return $interface->fetch('AJAX/login.tpl');
            } else {
                $interface->assign('followup', true);
                $interface->setPageTitle('You must be logged in first');
                $interface->assign('subTemplate', '../MyResearch/login.tpl');
                $interface->setTemplate('view-alt.tpl');
                $interface->display('layout.tpl', 'AddTag' . $_GET['id']);
            }
            exit();
        }

        if (isset($_POST['submit'])) {
            $result = $this->save();
            header("Location: " . $configArray['Site']['url'] . '/Record/' . 
                urlencode($_GET['id']) . '/Home');
        } else {
            return $this->display();
        }
    }
    
    function display()
    {
        global $interface;

        // Display Page
        if (isset($_GET['lightbox'])) {
            $interface->assign('title', $_GET['message']);
            return $interface->fetch('Record/addtag.tpl');
        } else {
            $interface->setPageTitle('Add Tag');
            $interface->assign('subTemplate', 'addtag.tpl');
            $interface->setTemplate('view-alt.tpl');
            $interface->display('layout.tpl', 'AddTag' . $_GET['id']);
        }
    }
    
    function save()
    {
        // Fail if we don't know what record we're working with:
        if (!isset($_GET['id'])) {
            return false;
        }
        
        // Create a resource entry for the current ID if necessary (or find the 
        // existing one):
        $resource = new Resource();
        $resource->record_id = $_GET['id'];
        if (!$resource->find(true)) {
            $resource->insert();
        }
        
        // Parse apart the tags and save them in association with the resource:
        preg_match_all('/"[^"]*"|[^ ]+/', $_REQUEST['tag'], $words);
        foreach ($words[0] as $tag) {
            $tag = str_replace('"', '', $tag);
            $resource->addTag($tag, $user);
        }

        // Done -- report success:        
        return true;
    }
}

?>
