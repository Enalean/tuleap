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

class OpenSearch extends Action {

    function launch()
    {
        header('Content-type: text/xml');
        
        if (isset($_GET['method'])) {
            if (is_callable(array($this, $_GET['method']))) {
                $this->$_GET['method']();
            } else {
                //echo '<Error>Invalid Method. Use either "describe" or "search"</Error>';
                echo '<Error>Invalid Method. Only "describe" is supported</Error>';
            }
        } else {
            $this->describe();
        }
    }
    
    function describe()
    {
        global $interface;
        global $configArray;
        
        $interface->assign('site', $configArray['Site']);

        $interface->display('Search/opensearch-describe.tpl');
    }
    
    /* Unused, incomplete method -- commented out 10/9/09 to prevent confusion:
    function search()
    {
        global $configArray;

        // Setup Search Engine Connection
        $class = $configArray['Index']['engine'];
        $db = new $class($configArray['Index']['url']);
        if ($configArray['System']['debug']) {
            $db->debug = true;
        }

        $search = array();
        $search[] = array('lookfor' => $_GET['lookfor'],
                          'type' => $_GET['type']);
        $query = $db->buildQuery($search);
        $results = $db->search($query['query']);
        $interface->assign('results', $results);
        
        $interface->display('Search/opensearch-search.tpl');
    }
     */
}
?>