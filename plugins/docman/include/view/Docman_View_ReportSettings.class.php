<?php
/**
 * Copyright © Enalean, 2011 - 2018. All Rights Reserved.
 * Copyright(c) STMicroelectronics, 2007
 *
 * Originally written by Manuel VACELET, STMicroelectronics, 2007
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('Docman_View_Extra.class.php');

class Docman_View_ReportSettings extends Docman_View_Extra
{
    var $groupId;
    var $defaultUrl;
    var $controller;

    function _title($params)
    {
        $request = HTTPRequest::instance();
        $hp = Codendi_HTMLPurifier::instance();
        if ($request->exist('report_id')) {
            echo '<h2>'.$GLOBALS['Language']->getText('plugin_docman', 'report_settings_report_name').' "'. $hp->purify($params['filter']->getName(), CODENDI_PURIFIER_CONVERT_HTML) .'"</h2>';
        } else {
            echo '<h2>'. $GLOBALS['Language']->getText('plugin_docman', 'report_settings_title') .'</h2>';
        }
    }

    private function init($params)
    {
        $this->groupId     =  $params['group_id'];
        $this->defaultUrl  =  $params['default_url'];
        $this->controller  = $params['docman'];
    }

    function _getReportTableContent($reportIter, $isAdmin, &$altRowClass)
    {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        $reportIter->rewind();
        while ($reportIter->valid()) {
            $r = $reportIter->current();
            $trclass = html_get_alt_row_color($altRowClass++);
            $html .=  '<tr class="'.$trclass.'">';

            // Name
            $rUrl  = $this->defaultUrl.'&action=report_settings&report_id='.$r->getId();
            $rName = '<a href="'.$rUrl.'">'. $hp->purify($r->getName(), CODENDI_PURIFIER_CONVERT_HTML) .'</a>';
            $html .= '<td align="left">'.$rName.'</td>';

            // Scope
            $scopeName = $GLOBALS['Language']->getText('plugin_docman', 'report_scope_'.$r->getScope());
            $html .= '<td align="center">'.$scopeName.'</td>';

            // Delete
            $trashLink = $this->defaultUrl.'&action=report_del&report_id='.$r->getId();
            $trashWarn = $GLOBALS['Language']->getText('plugin_docman', 'report_settings_delete', $hp->purify(addslashes($r->getName()), CODENDI_PURIFIER_CONVERT_HTML));
            $trashAlt  = $GLOBALS['Language']->getText('plugin_docman', 'report_settings_delete_alt', $hp->purify($r->getName(), CODENDI_PURIFIER_CONVERT_HTML));
            $delUrl = $this->defaultUrl.'&action=report_del&report_id='.$r->getId();
            $delName = html_trash_link($trashLink, $trashWarn, $trashAlt);
            $html .= '<td align="center">'.$delName.'</td>';

            $html .= "</tr>\n";
            $reportIter->next();
        }
        return $html;
    }

    function _getReportTable()
    {
        $html = '';

        $um   = UserManager::instance();
        $user = $um->getCurrentUser();
        $dpm  = Docman_PermissionsManager::instance($this->groupId);
        $isAdmin = $dpm->userCanAdmin($user);

        $html .= html_build_list_table_top(array($GLOBALS['Language']->getText('plugin_docman', 'report_settings_report_name'),
                                                 $GLOBALS['Language']->getText('plugin_docman', 'report_settings_report_scope'),
                                                 $GLOBALS['Language']->getText('plugin_docman', 'report_settings_report_delete'),));

        $reportFactory = new Docman_ReportFactory($this->groupId);

        $altRowClass = 0;
        if ($isAdmin) {
            $reportIter = $reportFactory->getProjectReportsForGroup();
            $html .= $this->_getReportTableContent($reportIter, $isAdmin, $altRowClass);
        }

        $reportIter = $reportFactory->getPersonalReportsForUser($user);
        $html .= $this->_getReportTableContent($reportIter, $isAdmin, $altRowClass);

        $html .= "</table>\n";
        return $html;
    }

    function _getReportSettings($reportId)
    {
        $html = '';

        $um   = UserManager::instance();
        $user = $um->getCurrentUser();
        $dpm  = Docman_PermissionsManager::instance($this->groupId);
        $isAdmin = $dpm->userCanAdmin($user);

        $reportFactory = new Docman_ReportFactory($this->groupId);
        $r = $reportFactory->getReportById($reportId);

        if ($r != null
           && $r->getGroupId() == $this->groupId) {
            $txts = array($GLOBALS['Language']->getText('plugin_docman', 'report_scope_I'),
                          $GLOBALS['Language']->getText('plugin_docman', 'report_scope_P'));
            $vals = array('I', 'P');

            $html .= '<p>'.$GLOBALS['Language']->getText('plugin_docman', 'report_settings_report_info').'</p>';

            $html .= '<form name="docman_report_update" method="post" action="?" class="docman_form">';
            $html .= '<input type="hidden" name="group_id" value="'.$this->groupId.'">';
            $html .= '<input type="hidden" name="action" value="report_upd">';
            $html .= '<input type="hidden" name="report_id" value="'.$r->getId().'">';

            $html .= '<table>';

            // Scope
            if ($dpm->userCanAdmin($user)) {
                $html .= '<tr>';
                $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'report_settings_report_scope').'</td>';
                $html .= '<td>';
                $html .= html_build_select_box_from_arrays($vals, $txts, 'scope', $r->getScope(), false);
                $html .= '</td>';
                $html .= '</tr>';
            }

            // Description
            $html .= '<tr>';
            $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'report_settings_report_description').'</td>';
            $html .= '<td>';
            $html .= '<textarea name="description">'.$r->getDescription().'</textarea>';
            $html .= '</td>';
            $html .= '</tr>';

            // Title
            $title = "";
            if ($r->getTitle() !== null) {
                $title = $r->getTitle();
            }
            $html .= '<tr>';
            $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'report_settings_report_title').'</td>';
            $html .= '<td>';
            $html .= '<input type="text" name="title" value="'.$title.'" class="text_field" />';
            $html .= '</td>';
            $html .= '</tr>';

            // Image
            $image = "";
            if ($r->getImage() !== null) {
                $image = $r->getImage();
            }
            $html .= '<tr>';
            $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'report_settings_report_image').'</td>';
            $html .= '<td>';
            $html .= '<input type="text" name="image" value="'.$image.'" />';
            $html .= ' '.$GLOBALS['Language']->getText('plugin_docman', 'report_settings_report_image_help');
            $html .= '</td>';
            $html .= '</tr>';

            // Current image
            $html .= '<tr>';
            $html .= '<td>';
            $html .= $GLOBALS['Language']->getText('plugin_docman', 'report_settings_report_image_current');
            $html .= '</td>';
            $reportHtml = new Docman_ReportHtml($r, $this, $this->defaultUrl);
            $html .= '<td>';
            $html .= $reportHtml->getReportImage();
            $html .= '</td>';
            $html .= '</tr>';

            // Submit
            $html .= '<tr>';
            $html .= '<td colspan="2">';
            $html .= '<input type="submit" name="sub" value="'.$GLOBALS['Language']->getText('global', 'btn_update').'">';
            $html .= '</td>';
            $html .= '</tr>';

            $html .= '</table>';

            $html .= '</form>';
        }

        return $html;
    }

    function _getImportForm()
    {
        $GLOBALS['HTML']->includeFooterJavascriptSnippet("new ProjectAutoCompleter('import_search_report_from_group', '".util_get_dir_image_theme()."', false);");

        $html = '';

        $html .= '<form name="docman_report_import" method="post" action="?">';
        $html .= '<input type="hidden" name="group_id" value="'.$this->groupId.'">';
        $html .= '<input type="hidden" name="action" value="report_import">';

        $html .= '<table border="0">';

        // Select project
        $html .= '<tr>';
        $html .= '<td valign="top">'.$GLOBALS['Language']->getText('plugin_docman', 'report_settings_import_sel_prj').'</td>';
        // Group id selector
        $html .= '<td>';
        $html .= '<input type="text" id="import_search_report_from_group" name="import_search_report_from_group" size="60" value="';
        $html .= $GLOBALS['Language']->getText('plugin_docman', 'report_settings_import_sel_prj_hint');
        $html .= '" />';
        $html .= '</td>';
        $html .= '</tr>';

        // Select report
        $html .= '<tr>';
        $html .= '<td valign="top">'.$GLOBALS['Language']->getText('plugin_docman', 'report_settings_import_sel_rpt').'('.$GLOBALS['Language']->getText('plugin_docman', 'report_settings_import_sel_rpt_id').')'.'</td>';
        $html .= '<td>';
        $html .= '<input type="text" name="import_report_id" value="" />';
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '</table>';

        // Submit
        $html .= '<input type="submit" name="submit" value="'.$GLOBALS['Language']->getText('global', 'btn_create').'">';
        $html .= '</form>';

        return $html;
    }

    function _content($params)
    {
        $html = '';

        $this->init($params);

        $request = HTTPRequest::instance();
        if ($request->exist('report_id')) {
            $reportId = (int) $request->get('report_id');
            $html .= $this->_getReportSettings($reportId);
        } else {
            // Default screen
            // Personal and project report list
            $html .= '<h3>'.$GLOBALS['Language']->getText('plugin_docman', 'report_settings_table_title').'</h3>';
            $html .= '<p>'.$GLOBALS['Language']->getText('plugin_docman', 'report_settings_table_intro').'</p>';
            $html .= $this->_getReportTable();

            // Import from another project
            $html .= '<h3>'.$GLOBALS['Language']->getText('plugin_docman', 'report_settings_import_title').'</h3>';
            $html .= '<p>'.$GLOBALS['Language']->getText('plugin_docman', 'report_settings_import_intro').'</p>';
            $html .= $this->_getImportForm();
        }

        echo $html;
    }
}
