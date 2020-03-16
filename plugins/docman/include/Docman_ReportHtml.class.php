<?php
/**
 * Copyright (c) Enalean, 2011 - 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class Docman_ReportHtml
{
    public $report;
    public $view;
    public $defaultUrl;
    /**
     * @var Codendi_HTMLPurifier
     */
    public $hp;

    public function __construct($report, $view, $defaultUrl)
    {
        $this->report     = $report;
        $this->view       = $view;
        $this->defaultUrl = $defaultUrl;
        $this->hp         = Codendi_HTMLPurifier::instance();
    }

    public function getSelectOption($value, $text, $selected = null)
    {
        $html = '';
        $html .= '<option value="' . $value . '"';
        if ($value == $selected) {
            $html .= ' selected="selected"';
        }
        $html .= '>' . $this->hp->purify($text) . "</option>\n";
        return $html;
    }

    public function getSelectOptionFromReportIterator($reportIter, $current = null)
    {
        $html = '';
        $reportIter->rewind();
        while ($reportIter->valid()) {
            $r = $reportIter->current();
            $html .= $this->getSelectOption($r->getId(), $r->getName(), $current);
            $reportIter->next();
        }
        return $html;
    }

    public function getReportSelector($item)
    {
        $html = '';

        $reportFactory = new Docman_ReportFactory($this->report->getGroupId());
        $current = $this->report->getId();

        $html = '';
        $html .= '<form name="plugin_docman_select_report" action="' . $this->defaultUrl . '" method="get" style="display: inline; float:right;" id="plugin_docman_select_report_id" >';
        $html .= '<select name="report_id" id="plugin_docman_select_saved_report">';

        // Project wide report
        $html .= $this->getSelectOption('-1', dgettext('tuleap-docman', '-- Project saved search:'), $current);
        $reportIter = $reportFactory->getProjectReportsForGroup();
        while ($reportIter->valid()) {
            $r = $reportIter->current();
            $html .= $this->getSelectOption($r->getId(), $r->getName(), $current);
            $reportIter->next();
        }

        // Personal reports
        $html .= $this->getSelectOption('-1', dgettext('tuleap-docman', '-- My saved search:'), $current);
        $user = $this->getCurrentUser();
        $reportIter = $reportFactory->getPersonalReportsForUser($user);
        while ($reportIter->valid()) {
            $r = $reportIter->current();
            $html .= $this->getSelectOption($r->getId(), $r->getName(), $current);
            $reportIter->next();
        }

        $html .= '</select>';
        $html .= '<input type="hidden" name="action" value="search" />';
        $html .= '<input type="hidden" name="group_id" value="' . $this->report->getGroupId() . '" />';
        $html .= '<input type="hidden" name="id" value="' . $item->getId() . '" />';
        $html .= '<noscript><input type="submit" value="' . $GLOBALS['Language']->getText('global', 'btn_submit') . '" /></noscript>';
        $html .= '</form>';

        return $html;
    }

    public function _getFilterDisplayBox($filter, $params, $trashLinkBase, &$displayedFilters)
    {
        $html = '';
        $htmlFilter = Docman_HtmlFilterFactory::getFromFilter($filter);
        if ($htmlFilter !== null) {
            $displayedFilters[] = $filter->md->getLabel();
            $html .= $htmlFilter->toHtml('plugin_docman_filters', $trashLinkBase);
        }
        return $html;
    }

    public function getSelectedFilters($params, &$displayedFilters)
    {
        $html = '';

        $html .= '<table class="docman_form" data-test="docman_form_table">';
        $fi = $this->report->getFilterIterator();
        $trashLinkBase = $this->view->_buildSearchUrl($params, array('del_filter' => ''));

        if ($fi->count() == 0) {
            $html .= '<div style="text-align:center; font-style:italic;">';
            $filterFactory = new Docman_FilterFactory($this->report->getGroupId());
            $f = $filterFactory->getFakeGlobalSearchFilter();
            $html .= $this->_getFilterDisplayBox($f, $params, false, $displayedFilters);
            $html .= '</div>';
        }

        // Display filters fields
        $fi->rewind();
        while ($fi->valid()) {
            $f = $fi->current();
            $html .= $this->_getFilterDisplayBox($f, $params, $trashLinkBase, $displayedFilters);
            $fi->next();
        }

        $ci = $this->report->getColumnIterator();
        $ci->rewind();
        while ($ci->valid()) {
            $c = $ci->current();
            $html .= $c->getSortSelectorHtml();
            $ci->next();
        }
        $html .= '</table>';

        return $html;
    }

    public function getFiltersOptions($params, $displayedFilters)
    {
        $html = '';

        $html .= '<div id="docman_report_options">';
        $html .= '<strong>' . dgettext('tuleap-docman', 'Search criteria:') . '</strong>&nbsp;';
        // Add a new filter
        $html .= dgettext('tuleap-docman', 'Add');
        $html .= '&nbsp;';
        $html .= '<select name="add_filter" id="plugin_docman_report_add_filter">';
        $html .= $this->getSelectOption('--', '--');

        // Std metadata
        $mdFactory = new Docman_MetadataFactory($this->report->getGroupId());
        $mdIter = $mdFactory->getMetadataForGroup(true);
        $mdIter->rewind();
        while ($mdIter->valid()) {
            $md = $mdIter->current();
            if (!in_array($md->getLabel(), $displayedFilters)) {
                $html .= $this->getSelectOption($md->getLabel(), $md->getName(), '');
            }
            $mdIter->next();
        }

        // Special filters
        $gsmd = $this->report->getGlobalSearchMetadata();
        $itmd = $this->report->getItemTypeSearchMetadata();

        $showGlobalSearch = !in_array($gsmd->getLabel(), $displayedFilters);
        $showItemTypeSearch = !in_array($itmd->getLabel(), $displayedFilters);
        if ($showGlobalSearch || $showItemTypeSearch) {
            $html .= $this->getSelectOption('--', '--');

            if ($showGlobalSearch) {
                $html .= $this->getSelectOption($gsmd->getLabel(), $gsmd->getName(), '');
            }

            if ($showItemTypeSearch) {
                $html .= $this->getSelectOption($itmd->getLabel(), $itmd->getName(), '');
            }
        }

        $html .= '</select>';

        // Advanced search
        $html .= '&nbsp;';
        if ($this->report->advancedSearch) {
            $html .= '<input type="hidden" name="advsearch" value="1" />';
            $html .= sprintf(dgettext('tuleap-docman', '(or use <a href="%1$s">Simple Search</a>)'), $this->view->_buildSearchUrl($params, ['advsearch' => 0]));
        } else {
            $html .= sprintf(dgettext('tuleap-docman', '(or use <a href="%1$s">Advanced Search</a>)'), $this->view->_buildSearchUrl($params, ['advsearch' => 1]));
        }

        $html .= '</div><!-- docman_report_options-->';

        return $html;
    }

    public function getReportOptions()
    {
        $html = '';

        $user = $this->getCurrentUser();
        $dpm  = Docman_PermissionsManager::instance($this->report->getGroupId());

        $html .= '<div id="docman_report_save">';
        $html .= '<strong>' . dgettext('tuleap-docman', 'Save options:') . '</strong>&nbsp;';

        // Save filter
        $html .= dgettext('tuleap-docman', 'Save as');
        $html .= '&nbsp;';
        $html .= '<select name="save_report" id="plugin_docman_report_save">';

        $reportFactory = new Docman_ReportFactory($this->report->getGroupId());
        // For docman admin, project reports
        if ($dpm->userCanAdmin($user)) {
            $reportIter = $reportFactory->getProjectReportsForGroup();
            if ($reportIter->count() > 0) {
                $html .= $this->getSelectOption('--', dgettext('tuleap-docman', '-- Project reports:'));
            }
            $html .= $this->getSelectOptionFromReportIterator($reportIter);
        }

        // For everyone, personal reports
        $reportIter = $reportFactory->getPersonalReportsForUser($user);
        if ($reportIter->count() > 0) {
            $html .= $this->getSelectOption('--', dgettext('tuleap-docman', '-- Personal reports:'));
        }
        $html .= $this->getSelectOptionFromReportIterator($reportIter);

        // New report
        $html .= $this->getSelectOption('--', '--');
        $html .= $this->getSelectOption('newi', dgettext('tuleap-docman', 'New personal report...'));
        if ($dpm->userCanAdmin($user)) {
            $html .= $this->getSelectOption('newp', dgettext('tuleap-docman', 'New project report...'));
        }
        $html .= '</select>';

        $html .= '<noscript>';
        $html .= '&nbsp;';
        $html .= dgettext('tuleap-docman', 'New filter name:');
        $html .= '<input type="text" name="report_name" value="" />';
        $html .= '</noscript>';

        $html .= '&nbsp;';
        $settingsUrl = $this->defaultUrl . '&action=report_settings';
        $html .= '<a href="' . $settingsUrl . '">' . dgettext('tuleap-docman', 'Show my saved search') . '</a>';

        $html .= '</div><!-- docman_report_save-->';

        return $html;
    }

    /**
     * Entry point
     */
    public function toHtml($params)
    {
        $html = '';

        $html .= $this->getReportSelector($params['item']);

        $toggleIc = '<img src="' . util_get_image_theme("ic/toggle_minus.png") . '" id="docman_toggle_filters" data-test="docman_report_search">';
        $toggle   = '<a href="#" title="' . dgettext('tuleap-docman', 'Toggle search criteria selection') . '">' . $toggleIc . '</a>';
        $title    = dgettext('tuleap-docman', 'Search');

        $hidden_fields = '';
        $hidden_fields .= '<input type="hidden" name="group_id" value="' . $this->report->getGroupId() . '" />';
        $hidden_fields .= '<input type="hidden" name="id" value="' . $params['item']->getId() . '" />';
        $hidden_fields .= '<input type="hidden" name="action" value="search" />';

        $global_txt = $this->hp->purify($params['docman']->request->get('global_txt'));

        $html .= "<div id=\"docman_filters_title\">\n";
        $html .= '<form method="get" action="?" id="plugin_docman_report_form_global">';
        $html .= $toggle;
        $html .= ' ' . $title . ' ';
        $html .= $hidden_fields;
        $html .= '<input type="text"
                        class="text_field"
                        title="' . dgettext('tuleap-docman', 'Global text search') . '"
                        value="' . $global_txt . '"
                        name="global_txt"
                        data-test="docman_search"
                        />';
        $html .= '<input type="submit" value="' . $GLOBALS['Language']->getText('global', 'btn_apply') . '" name="global_filtersubmit" data-test="docman_search_button" />';
        $html .= '</form>';
        $html .= "</div>\n";

        $html .= "<div id=\"docman_filters_fieldset\">\n";

        $html .= '<div style="float: left;">';
        $html .= '<form name="plugin_docman_filters" method="get" action="?" id="plugin_docman_report_form" >';
        $html .= $hidden_fields;

        $displayedFilters = array();
        $html .= $this->getSelectedFilters($params, $displayedFilters);
        $html .= $this->getFiltersOptions($params, $displayedFilters);
        $html .= $this->getReportOptions();

        $html .= '<input id="docman_report_submit" name="filtersubmit" type="submit" value="' . $GLOBALS['Language']->getText('global', 'btn_apply') . '">';
        $html .= '&nbsp;';
        $html .= '<input id="docman_report_submit" name="clear_filters" type="submit" value="' . dgettext('tuleap-docman', 'Clear all') . '">';

        $html .= '</form>';
        $html .= "</div> <!-- left -->\n";

        $html .= '<div style="float: right;">';
        //Retrieve the minimum length allowed when searching pattern
        $dao = Docman_ReportFactory::getDao();
        $minLen = $dao->getMinLengthForPattern();
        $html .= '<div class="docman_help">
            <table style="width: 100%; padding-left: 2em;">
                <tr>
                    <td>' . dgettext('tuleap-docman', 'Allowed patterns:') . '
                        <ol style="margin-top: 0;">
                            <li><span class="code">lorem</span></li>
                            <li><span class="code">lorem*</span></li>
                            <li><span class="code">*lorem</span></li>
                            <li><span class="code">*lorem*</span></li>
                        </ol>
                    </td>
                    <td valign="top" style="border-left: 1px solid black; padding-left: 1em;">
                        ' . dgettext('tuleap-docman', 'When the pattern doesn\'t start with * (pattern #1 or #2):') . '
                        <ul style="list-style: none; margin-top: 0;">
                            <li><span class="code">word</span>
                            ' . sprintf(dgettext('tuleap-docman', 'length must be greater than %1$s'), $minLen) . '
                            </li>
                            <li><span class="code">+word</span>
                            ' . dgettext('tuleap-docman', 'lorem must be in result') . '
                            </li>
                            <li><span class="code">-word</span>
                            ' . dgettext('tuleap-docman', 'lorem must not be in result') . '
                            </li>
                            <li><span class="code">"some words"</span>
                            ' . dgettext('tuleap-docman', 'look for exact sentence') . '
                            </li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                    ' . dgettext('tuleap-docman', 'Info: Global text search will search pattern in all "Text" properties (but not within documents yet).') . '
                    </td>
                </tr>
            </table>
        </div></div>';

        $html .= '<div style="clear: both;"></div>';

        $html .= "</div> <!-- docman_filters_fieldset -->\n";

        $html .= "\n<!-- filter list -->\n";

        return $html;
    }

    public function getReportImage()
    {
        $html = '';
        if ($this->report->getImage() !== null) {
            $itemId = $this->report->getImage();
            if ($itemId > 0) {
                // Get Item
                $itemFactory = new Docman_ItemFactory($this->report->getGroupId());
                $item = $itemFactory->getItemFromDb($itemId);
                if ($item !== null) {
                    // Check perms
                    $dPm = Docman_PermissionsManager::instance($item->getGroupId());
                    $user = $this->getCurrentUser();
                    $html .= "<div style=\"text-align:center\">\n";
                    if ($dPm->userCanRead($user, $item->getId())) {
                        $html .= '<img src="' . $this->defaultUrl . '&id=' . $itemId . '" >';
                    } else {
                        $html .= dgettext('tuleap-docman', 'An image is associated to this report but you don\'t have the permission to see it.');
                    }
                    $html .= "</div>\n";
                }
            }
        }
        return $html;
    }

    public function getReportCustomization()
    {
        $html = '';

        if ($this->report->getDescription() !== null) {
            $html .= $this->hp->purify($this->report->getDescription(), CODENDI_PURIFIER_BASIC, $this->report->getGroupId());
        }
        $html .= $this->getReportImage();

        return $html;
    }

    /**
     * @return PFUser
     */
    private function getCurrentUser()
    {
        $um = UserManager::instance();
        $user = $um->getCurrentUser();
        return $user;
    }
}
