<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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

require_once 'common/widget/Widget.class.php';

/**
 * Display links from and to a project on the summary page.
 */
class AdminDelegation_ShowProjectWidget extends Widget {
    protected $_plugin;

    /**
     * Constructor
     * 
     * @param Plugin $plugin The plugin
     */
    function __construct(Plugin $plugin) {
        parent::__construct('admindelegation_projects');
        $this->_plugin = $plugin;
    }

    /**
     * Widget title
     *
     * @see src/common/widget/Widget#getTitle()
     * @return String
     */
    function getTitle() {
        return $GLOBALS['Language']->getText('plugin_admindelegation', 'widget_projects_title');
    }

    /**
     * Widget description
     *
     * @see src/common/widget/Widget#getDescription()
     *
     * @return String
     */
    function getDescription() {
        return $GLOBALS['Language']->getText('plugin_admindelegation','widget_projects_description');
    }

    /**
     * Tell if a widget can by used by a project
     *
     * @param Project $project
     */
    function canBeUsedByProject(Project $project) {
        return false;
    }

    function getCategory() {
        return 'plugin_admindelegation';
    }

    function getAllProject($offset, $limit, $condition, $pattern) {
        $projects = array();
        if (count($condition)> 0){
            $statements   = '(';
            $i            = 0;
            $nbConditions = count($condition) - 1;
            for($i; $i < $nbConditions; $i++){
                $statements .= db_es($condition[$i]).' LIKE "%'.db_es($pattern).'%" OR ';
            }
            $statements .= db_es($condition[$i]).' LIKE "%'.db_es($pattern).'%") AND ';
        }
        
        $sql = 'SELECT SQL_CALC_FOUND_ROWS group_name, group_id, unix_group_name, is_public FROM groups WHERE '.$statements.' status = "A" ORDER BY register_time DESC LIMIT '.db_ei($offset).', '.db_ei($limit);
        $res = db_query($sql);

        $sql = 'SELECT FOUND_ROWS() as nb';
        $res_numrows = db_query($sql);
        $row = db_fetch_array($res_numrows);

        return array('projects' => $res, 'numrows' => $row['nb']);
    }

    protected function _showAllProject() {
        $request = HTTPRequest::instance();
        
        $urlParam = '';
        
        $vFunc = new Valid_WhiteList('plugin_admindelegation_func', array('show_projects'));
        $vFunc->required();
        if ($request->valid($vFunc)) {
            $func = $request->get('plugin_admindelegation_func');
        } else {
            $func = '';
        }

        $condition    = array();
        $allCriterias = array('group_name', 'unix_group_name', 'short_description');
        foreach ($allCriterias as $val) {
            $selectedCriteria[$val] = '';
        }
        $vCriteria    = new Valid_WhiteList('criteria', $allCriterias);
        $vCriteria->required();
        if ($request->validArray($vCriteria)) {
            $criteria = $request->get('criteria');
        } else {
            $criteria = $allCriterias;
        }
        foreach ($criteria as $val) {
            $condition[] = $val;
            $urlParam   .= '&criteria[]='.$val;
            $selectedCriteria[$val] = 'checked="checked"';
        }

        $vPattern = new Valid_String('plugin_admindelegation_pattern');
        $vPattern->required();
        if ($request->valid($vPattern)) {
            $pattern = $request->get('plugin_admindelegation_pattern');
        } else {
            $pattern = '';
        }

        $offset = $request->getValidated('offset', 'uint', 0);
        if ( !$offset || $offset < 0 ) {
            $offset = 0;
        }
        $limit  = 10;

        $html = '';
        $html .= '<form method="post" action="?">';
        
        $html .= '<label>'.$GLOBALS['Language']->getText('plugin_admindelegation', 'widget_projects_label').'</label>';
        $html .= '<input type="hidden" name="plugin_admindelegation_func" value="show_projects" />';
        $html .= '<input type="text" name="plugin_admindelegation_pattern" value="'.$pattern.'" size ="40" id="plugin_admindelegation_pattern" />';
        $html .= '<br />';

        $html .= '<input type="checkbox" name="criteria[]" value="group_name" id="plugin_admindelegation_group_name" '.$selectedCriteria['group_name'].' />';
        $html .= '<label for="plugin_admindelegation_group_name">'.$GLOBALS['Language']->getText('plugin_admindelegation', 'widget_projects_crit_group_name').'</label>&nbsp;';
        $html .= '<input type="checkbox" name="criteria[]" value="unix_group_name" id="plugin_admindelegation_unix_group_name" '.$selectedCriteria['unix_group_name'].' />';
        $html .= '<label for="plugin_admindelegation_unix_group_name">'.$GLOBALS['Language']->getText('plugin_admindelegation', 'widget_projects_crit_unix_group_name').'</label>&nbsp;';
        $html .= '<input type="checkbox" name="criteria[]" value="short_description" id="plugin_admindelegation_short_description" '.$selectedCriteria['short_description'].' />';
        $html .= '<label for="plugin_admindelegation_short_description">'.$GLOBALS['Language']->getText('plugin_admindelegation', 'widget_projects_crit_short_description').'</label>&nbsp;';
        
        $html .= '<input type="submit" value="'.$GLOBALS['Language']->getText('plugin_admindelegation', 'widget_btn_search').'"/>';
        
        $html .= '</form>';

       if ($func == 'show_projects'){
           $res = $this->getAllProject($offset, $limit, $condition, $pattern);

            if ($res['numrows'] > 0) {
                $html .= '<table width="100%">';
                $html .= '<theader>';
                $html .= '<tr>';
                $html .= '<th>'.$GLOBALS['Language']->getText('plugin_admindelegation', 'widget_projects_col_project_name').'</th>';
                $html .= '<th>'.$GLOBALS['Language']->getText('plugin_admindelegation', 'widget_projects_col_project_id').'</th>';
                $html .= '</tr>';
                $html .= '</theader>';

                $html .= '<tbody>';
                $i = 1;
                while ($row = db_fetch_array($res['projects'])) {
                    $html .= '<tr class="'. util_get_alt_row_color($i++) .'">';
                    $html .= '<td>';
                    $html .= '<a href="/projects/'.$row['unix_group_name'].'">'.$row['group_name'].'</a>';
                    if (!$row['is_public']) {
                        $html .= '&nbsp;(*)';
                    }
                    $html .= '</td>';
                    $html .= '<td>'.$row['group_id'].'</td>';
                    $html .= '</tr>';
                }
                $html .= '</tbody>';

                $html .= '</table>';
           
                $html .= '<div style="text-align:center" class="'. util_get_alt_row_color($i++) .'">';
                if ($offset > 0) {
                    $html .= '<a href="?plugin_admindelegation_func=show_projects&offset='.($offset-$limit).$urlParam.'&plugin_admindelegation_pattern='.$pattern.'">[ '.$GLOBALS['Language']->getText('plugin_admindelegation', 'widget_projects_previous').' ]</a>';
                    $html .= '&nbsp;';
                }
                if (($offset + $limit) < $res['numrows']) {
                    $html .= '&nbsp;';
                    $html .= '<a href="?plugin_admindelegation_func=show_projects&offset='.($offset+$limit).$urlParam.'&plugin_admindelegation_pattern='.$pattern.'">[ '.$GLOBALS['Language']->getText('plugin_admindelegation', 'widget_projects_next').' ]</a>';
                }
                $html .= '</div>';
                $html .= '<div style="text-align:left" class="'. util_get_alt_row_color($i++) .'">';
                $html .= '(*)&nbsp;'.$GLOBALS['Language']->getText('my_index', 'priv_proj');
                $html .= '&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;'.$GLOBALS['Language']->getText('plugin_admindelegation', 'widget_projects_nb_projects_found', array($res['numrows']));
                $html .= '</div>';
            }
        }
        return $html;
    }
    
    /**
     * Widget content
     * 
     * @see src/common/widget/Widget#getContent()
     * @return String
     */
    public function getContent() {
        $html = '';
        $usm = new AdminDelegation_UserServiceManager();
        
        if ($usm->isUserGrantedForService(UserManager::instance()->getCurrentUser(), AdminDelegation_Service::SHOW_PROJECTS)) {
            $html .= $this->_showAllProject();
        }
        return $html;
    }
}

?>