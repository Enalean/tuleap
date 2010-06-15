<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */


require_once('Error_PermissionDenied.class.php');

class Error_PermissionDenied_PrivateProject extends Error_PermissionDenied {
    /**
     * Constructor of the class
     *
     * @return void
     */
    function __construct() {
        parent::__construct();
    }

    /**
     * Returns the mail subject according to language given on parameters 
     * 
     * @param BaseLanguage $language
     * @param Project $project
     * 
     * @return String
     */
    function formatSubject($language, $project) {
        return $language->getText('include_exit', 'mail_subject_private_project', array($project->getPublicName()));
    }

    /**
     * Dispaly interface to ask for membership
     */
    function displayInterface() {
        $url= new URL();
        $groupId =  (isset($GLOBALS['group_id'])) ? $GLOBALS['group_id'] : $url->getGroupIdFromUrl($_SERVER['REQUEST_URI']);
        $userId = $this->getUserManager()->getCurrentUser()->getId();
        echo "<b>".$GLOBALS['Language']->getText('include_exit','perm_denied')."</b>";
        echo '<br></br>';
        echo "<br>".$GLOBALS['Language']->getText('include_exit','private_project_no_perm');
        echo $GLOBALS['Language']->getText('include_exit','request_to_admin');
                
        echo '<br></br>';
        echo $this->buildInterface('msg_private_project', 'private_project_request', $groupId, $userId);
    }

}
?>
