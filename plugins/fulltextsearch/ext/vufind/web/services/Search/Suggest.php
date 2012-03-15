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

class Suggest extends Action {

    function launch()
    {
        global $configArray;

        //header('Content-type: application/x-suggestions+json');
        header('Content-type: application/json');

        // Setup Search Engine Connection
        $class = $configArray['Index']['engine'];
        $db = new $class($configArray['Index']['url']);
        if ($configArray['System']['debug']) {
            $db->debug = true;
        }
        
        $results = $db->getSuggestion(strtolower($_GET['lookfor']), 'title_sort', 10);
        echo json_encode($results);
    }
}
?>