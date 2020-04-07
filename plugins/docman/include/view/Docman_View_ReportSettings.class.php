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
    public $groupId;
    public $defaultUrl;
    public $controller;

    public function _title($params)
    {
        $request = HTTPRequest::instance();
        $hp = Codendi_HTMLPurifier::instance();
        if ($request->exist('report_id')) {
            echo '<h2>' . dgettext('tuleap-docman', 'Report') . ' "' . $hp->purify($params['filter']->getName(), CODENDI_PURIFIER_CONVERT_HTML) . '"</h2>';
        } else {
            echo '<h2>' . dgettext('tuleap-docman', 'Search report administration') . '</h2>';
        }
    }

    private function init($params)
    {
        $this->groupId     =  $params['group_id'];
        $this->defaultUrl  =  $params['default_url'];
        $this->controller  = $params['docman'];
    }

    public function _getReportTableContent($reportIter, $isAdmin, &$altRowClass)
    {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        $reportIter->rewind();
        while ($reportIter->valid()) {
            $r = $reportIter->current();
            $trclass = html_get_alt_row_color($altRowClass++);
            $html .=  '<tr class="' . $trclass . '">';

            // Name
            $rUrl  = $this->defaultUrl . '&action=report_settings&report_id=' . $r->getId();
            $rName = '<a href="' . $rUrl . '">' . $hp->purify($r->getName(), CODENDI_PURIFIER_CONVERT_HTML) . '</a>';
            $html .= '<td align="left">' . $rName . '</td>';

            // Scope
            $html .= '<td align="center">';
            switch ($r->getScope()) {
                case 'I':
                    $html .= dgettext('tuleap-docman', 'Personal');
                    break;
                case 'P':
                    $html .= dgettext('tuleap-docman', 'Project');
                    break;
            }
            $html .= '</td>';

            // Delete
            $trashLink = $this->defaultUrl . '&action=report_del&report_id=' . $r->getId();
            $trashWarn = sprintf(dgettext('tuleap-docman', 'Are your sure you want to delete report \'%1$s\'?'), $hp->purify(addslashes($r->getName()), CODENDI_PURIFIER_CONVERT_HTML));
            $trashAlt  = sprintf(dgettext('tuleap-docman', 'Delete report \'%1$s\''), $hp->purify($r->getName(), CODENDI_PURIFIER_CONVERT_HTML));
            $delUrl = $this->defaultUrl . '&action=report_del&report_id=' . $r->getId();
            $delName = html_trash_link($trashLink, $trashWarn, $trashAlt);
            $html .= '<td align="center">' . $delName . '</td>';

            $html .= "</tr>\n";
            $reportIter->next();
        }
        return $html;
    }

    public function _getReportTable()
    {
        $html = '';

        $um   = UserManager::instance();
        $user = $um->getCurrentUser();
        $dpm  = Docman_PermissionsManager::instance($this->groupId);
        $isAdmin = $dpm->userCanAdmin($user);

        $html .= html_build_list_table_top(array(dgettext('tuleap-docman', 'Report'),
                                                 dgettext('tuleap-docman', 'Scope:'),
                                                 dgettext('tuleap-docman', 'Delete'),));

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

    public function _getReportSettings($reportId)
    {
        $html = '';

        $um   = UserManager::instance();
        $user = $um->getCurrentUser();
        $dpm  = Docman_PermissionsManager::instance($this->groupId);
        $isAdmin = $dpm->userCanAdmin($user);

        $reportFactory = new Docman_ReportFactory($this->groupId);
        $r = $reportFactory->getReportById($reportId);

        if (
            $r != null
            && $r->getGroupId() == $this->groupId
        ) {
            $txts = array(dgettext('tuleap-docman', 'Personal'),
                          dgettext('tuleap-docman', 'Project'));
            $vals = array('I', 'P');

            $html .= '<p>' . dgettext('tuleap-docman', 'You can customize your report by adding text and an image. Note: an image is just a reference on any valid image available in documentation manager (across projects).') . '</p>';

            $html .= '<form name="docman_report_update" method="post" action="?" class="docman_form">';
            $html .= '<input type="hidden" name="group_id" value="' . $this->groupId . '">';
            $html .= '<input type="hidden" name="action" value="report_upd">';
            $html .= '<input type="hidden" name="report_id" value="' . $r->getId() . '">';

            $html .= '<table>';

            // Scope
            if ($dpm->userCanAdmin($user)) {
                $html .= '<tr>';
                $html .= '<td>' . dgettext('tuleap-docman', 'Scope:') . '</td>';
                $html .= '<td>';
                $html .= html_build_select_box_from_arrays($vals, $txts, 'scope', $r->getScope(), false);
                $html .= '</td>';
                $html .= '</tr>';
            }

            // Description
            $html .= '<tr>';
            $html .= '<td>' . dgettext('tuleap-docman', 'Description:') . '</td>';
            $html .= '<td>';
            $html .= '<textarea name="description">' . $r->getDescription() . '</textarea>';
            $html .= '</td>';
            $html .= '</tr>';

            // Title
            $title = "";
            if ($r->getTitle() !== null) {
                $title = $r->getTitle();
            }
            $html .= '<tr>';
            $html .= '<td>' . dgettext('tuleap-docman', 'Title:') . '</td>';
            $html .= '<td>';
            $html .= '<input type="text" name="title" value="' . $title . '" class="text_field" />';
            $html .= '</td>';
            $html .= '</tr>';

            // Image
            $image = "";
            if ($r->getImage() !== null) {
                $image = $r->getImage();
            }
            $html .= '<tr>';
            $html .= '<td>' . dgettext('tuleap-docman', 'Image Id:') . '</td>';
            $html .= '<td>';
            $html .= '<input type="text" name="image" value="' . $image . '" />';
            $html .= ' ' . dgettext('tuleap-docman', 'Refer to a document id available in the document manager (Warning: <strong>permissions apply</strong>).');
            $html .= '</td>';
            $html .= '</tr>';

            // Current image
            $html .= '<tr>';
            $html .= '<td>';
            $html .= dgettext('tuleap-docman', 'Current image:');
            $html .= '</td>';
            $reportHtml = new Docman_ReportHtml($r, $this, $this->defaultUrl);
            $html .= '<td>';
            $html .= $reportHtml->getReportImage();
            $html .= '</td>';
            $html .= '</tr>';

            // Submit
            $html .= '<tr>';
            $html .= '<td colspan="2">';
            $html .= '<input type="submit" name="sub" value="' . $GLOBALS['Language']->getText('global', 'btn_update') . '">';
            $html .= '</td>';
            $html .= '</tr>';

            $html .= '</table>';

            $html .= '</form>';
        }

        return $html;
    }

    public function _getImportForm()
    {
        $GLOBALS['HTML']->includeFooterJavascriptSnippet("new ProjectAutoCompleter('import_search_report_from_group', '" . util_get_dir_image_theme() . "', false);");

        $html = '';

        $html .= '<form name="docman_report_import" method="post" action="?">';
        $html .= '<input type="hidden" name="group_id" value="' . $this->groupId . '">';
        $html .= '<input type="hidden" name="action" value="report_import">';

        $html .= '<table border="0">';

        // Select project
        $html .= '<tr>';
        $html .= '<td valign="top">' . dgettext('tuleap-docman', 'Project:') . '</td>';
        // Group id selector
        $html .= '<td>';
        $html .= '<input type="text" id="import_search_report_from_group" name="import_search_report_from_group" size="60" value="';
        $html .= dgettext('tuleap-docman', 'Enter project short name or identifier here.');
        $html .= '" />';
        $html .= '</td>';
        $html .= '</tr>';

        // Select report
        $html .= '<tr>';
        $html .= '<td valign="top">' . dgettext('tuleap-docman', 'Report:') . '(' . dgettext('tuleap-docman', 'report_id') . ')' . '</td>';
        $html .= '<td>';
        $html .= '<input type="text" name="import_report_id" value="" />';
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '</table>';

        // Submit
        $html .= '<input type="submit" name="submit" value="' . $GLOBALS['Language']->getText('global', 'btn_create') . '">';
        $html .= '</form>';

        return $html;
    }

    public function _content($params)
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
            $html .= '<h3>' . dgettext('tuleap-docman', 'Report list') . '</h3>';
            $html .= '<p>' . dgettext('tuleap-docman', 'You can modify the settings of the report you already saved for this project.') . '</p>';
            $html .= $this->_getReportTable();

            // Import from another project
            $html .= '<h3>' . dgettext('tuleap-docman', 'Import reports from another project.') . '</h3>';
            $html .= '<p>' . dgettext('tuleap-docman', 'You can import in this project a report defined in another project (either your \'Personal\' reports or any \'Project\' wide). The import only works if properties and values are the same in the two projects (same name, case sensitive). The import will work as best by trying to import as much as possible.') . '</p>';
            $html .= $this->_getImportForm();
        }

        echo $html;
    }
}
