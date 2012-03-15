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
 
require_once 'services/MyResearch/MyResearch.php';
require_once 'services/MyResearch/lib/User.php';
require_once 'services/MyResearch/lib/Search.php';

class SaveSearch extends MyResearch
{
    function launch()
    {
        global $interface;
        global $configArray;
        global $user;

        if (isset($_REQUEST['delete']) && $_REQUEST['delete']) {
            $todo = 'deleteSearch';
            $searchId = $_REQUEST['delete'];
        }
        // If for some strange reason the user tries
        //    to do both, just focus on the save.
        if (isset($_REQUEST['save']) && $_REQUEST['save']) {
            $todo = 'addSearch';
            $searchId = $_REQUEST['save'];
        }

        $search = new SearchEntry();
        $search->id = $searchId;
        if ($search->find(true)) {
            // Found, make sure this is a search from this user
            if ($search->session_id == session_id() || $search->user_id == $user->id) {
                // Call whichever function is required below
                $this->$todo($search);
            }
        }
        
        // If we are in "edit history" mode, stay in Search History:
        if (isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'history') {
            header("Location: ".$configArray['Site']['url']."/Search/History");
        } else {
            // If the ID wasn't found, or some other error occurred, nothing will
            //   have processed be now, let the error handling on the display
            //   screen take care of it.
            header("Location: ".$configArray['Site']['url']."/Search/Results?saved=$searchId");
        }
    }

    private function addSearch($search)
    {
        if ($search->saved != 1) {
            global $user;
            $search->user_id = $user->id;
            $search->saved = 1;
            $search->update();
        }
    }

    private function deleteSearch($search)
    {
        if ($search->saved != 0) {
            $search->saved = 0;
            $search->update();
        }
    }
}

?>