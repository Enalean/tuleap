<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

require_once('common/mvc/Views.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('common/user/UserManager.class.php');
require_once('common/user/UserHelper.class.php');
require_once('common/project/ProjectManager.class.php');

class fulltextsearchViews extends Views {
    
    function fulltextsearchViews(&$controler, $view=null) {
        $this->View($controler, $view);
    }
    
    function header() {
        $request =& HTTPRequest::instance();
        $GLOBALS['HTML']->header(array('title'=>$this->_getTitle(),'group' => $request->get('group_id'), 'toptab' => 'fulltextsearch'));
        echo $this->_getHelp();
        echo '<h2>'.$this->_getTitle().'</h2>';
    }
    
    function _getTitle() {
        return $GLOBALS['Language']->getText('plugin_fulltextsearch','title');
    }
    function _getHelp($section = '', $questionmark = false) {
        if (trim($section) !== '' && $section{0} !== '#') {
            $section = '#'.$section;
        }
        if ($questionmark) {
            $help_label = '[?]';
        } else {
            $help_label = $GLOBALS['Language']->getText('global', 'help');
        }
        return '<b><a href="javascript:help_window(\''.get_server_url().'/documentation/user_guide/html/'.UserManager::instance()->getCurrentUser()->getLocale().'/ContinuousIntegrationWithHudson.html'.$section.'\');">'.$help_label.'</a></b>';
    }
    function footer() {
        $GLOBALS['HTML']->footer(array());
    }
    
    // {{{ Views
    function projectGlobalSearch() {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $user = UserManager::instance()->getCurrentUser();
        
        echo '<form name="fulltextsearch_search_form" id="fulltextsearch_search_form" method ="post" action="/plugins/fulltextsearch/?group_id='.$group_id.'&action=projectsearch">';
        echo '<span class="fulltextsearch_logo">Full Text Search</span><br />';
        echo '<input type="text" name="solr_query" id="solr_query" size="60" /><br />';
        echo '<input type="submit" value="Search" />';
        echo '</form>';
        
    }
    
    function projectSearch() {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $user = UserManager::instance()->getCurrentUser();
        $user_helper = UserHelper::instance();
        
        $plugin      = $this->getControler()->getPlugin();
        $solr_server = $plugin->getPluginInfo()->getPropVal('solr_server');
        $solr_port   = $plugin->getPluginInfo()->getPropVal('solr_port');
        $solr_path   = $plugin->getPluginInfo()->getPropVal('solr_path');
        
        require_once( '../etc/solr/SolrPhpClient/Apache/Solr/CeliService.php' );
        $solr = new Celi_Apache_Solr_Service($solr_server, $solr_port, $solr_path);

        if ( ! $solr->ping() ) {
            echo "<p>Test do not work Solr with php client.</p>";    
        }

        // TODO: implement pagination
        $offset = 0;
        $limit = 100;
        
        $solr_query = stripslashes($request->get('solr_query'));
        
        $permissions_query = $this->_getPermissionQuery($user, $group_id);
        
        $queries = array(
            'forge_project_id: '.$group_id.' AND ' .
            '(' .
                '((external_file: '.$solr_query.' OR doc_title: '.$solr_query.' OR doc_description: '.$solr_query.') AND ' . $permissions_query . ') OR ' .
                '(forum_message_subject: '.$solr_query.' OR forum_message_body: '.$solr_query.')' .
                //'doc_language: english'
            ')'
        );

        foreach ( $queries as $query ) {
            $result = $solr->search( $query, $offset, $limit);
            if ( $result->getHttpStatus() == 200 ) {
                $response = $result->response;
                $nb_results = $response->numFound;
                if ($nb_results > 0) {
                    echo '<h3>Search result: there are '.$nb_results.' results</h3>';
                    echo '<ol>';
                    foreach ($response->docs as $solr_doc) {
                        
                        $df = CodendiSolrDocumentFactory::instance();
                        $doc_viewer = $df->getCodendiSolrDocumentViewer($solr_doc);
                        if ($doc_viewer != null) {
                            echo $doc_viewer->getHTMLResult();
                        } else {
                            echo '<li>Unknown document</li>';
                        }
                        
                    }
                    echo '</ol>';
                } else {
                    echo '<h3>Search result: there are no results.</h3>';
                }
            } else {
                echo $response->getHttpStatusMessage();
            }
        }
    }
    
    /**
     * Returns the query string for permission for user $user and group_id $group_id
     * If the user is member of ugroups 2, 3 and 42, the query will be:
     * (forge_permission_group: 2 OR forge_permission_group: 3 ORforge_permission_group: 42)
     *
     * @param User $user     the user that do the solr query
     * @param int  $group_id the id of the project
     *
     * @return string the query string for permission
     */
    private function _getPermissionQuery($user, $group_id) {
        $permissions_query = '';
        
        // get the ugroups the user is member of for project group_id
        $ugroups_user = $user->getUgroups($group_id, array());
        
        $permissions_counter = 0;
        $permissions_number = count($ugroups_user);
        foreach ($ugroups_user as $ugroup_user) {
            $permissions_counter++;
            $permissions_query .= 'forge_permission_group: ' . $ugroup_user;
            if ($permissions_counter < $permissions_number) {
                $permissions_query .= ' OR ';
            }
        }
        if ($permissions_query != '') {
            $permissions_query = '('.$permissions_query.')';
        }
        return $permissions_query;
    }
    
}

?>
