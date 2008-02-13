<?php
/**
 * Copyright © STMicroelectronics, 2007. All Rights Reserved.
 * 
 * Originally written by Manuel VACELET, 2007.
 * 
 * This file is a part of CodeX.
 * 
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * 
 */

class Docman_ReportHtml {
    var $report;
    var $view;
    var $defaultUrl;
    var $hp;

    /**
     *
     */
    function Docman_ReportHtml($report, $view, $defaultUrl) {
        $this->report = $report;
        $this->view   = $view;
        $this->defaultUrl = $defaultUrl;
        $this->hp = CodeX_HTMLPurifier::instance();
    }

    /**
     *
     */
    function getSelectOption($value, $text, $selected=null) {
        $html = '';
        $html .= '<option value="'.$value.'"';
        if($value == $selected) {
            $html .= ' selected="selected"';
        }
        $html .= '>'.$this->hp->purify($text)."</option>\n";
        return $html;
    }

    function getSelectOptionFromReportIterator($reportIter, $current=null) {
        $html = '';
        $reportIter->rewind();
        while($reportIter->valid()) {
            $r = $reportIter->current();
            $html .= $this->getSelectOption($r->getId(), $r->getName(), $current);
            $reportIter->next();
        }
        return $html;
    }

    /**
     *
     */
    function getReportSelector($item) {
        $html = '';

        $reportFactory = new Docman_ReportFactory($this->report->getGroupId());
        $current = $this->report->getId();

        $html = '';
        $html .= '<form name="plugin_docman_select_report" action="'. $this->defaultUrl .'" method="get" style="display: inline;">';
        $html .= '<select name="report_id" onchange="docman.reportSavedSearchChange(this.form);">';

        // Project wide report
        $html .= $this->getSelectOption('-1', $GLOBALS['Language']->getText('plugin_docman', 'report_saved_prjreports'), $current);
        $reportIter = $reportFactory->getProjectReportsForGroup();
        while($reportIter->valid()) {
            $r = $reportIter->current();
            $html .= $this->getSelectOption($r->getId(), $r->getName(), $current);
            $reportIter->next();
        }

        // Personal reports
        $html .= $this->getSelectOption('-1', $GLOBALS['Language']->getText('plugin_docman', 'report_saved_persoreports'), $current);
        $user = $this->getCurrentUser();
        $reportIter = $reportFactory->getPersonalReportsForUser($user);
        while($reportIter->valid()) {
            $r = $reportIter->current();
            $html .= $this->getSelectOption($r->getId(), $r->getName(), $current);
            $reportIter->next();
        }

        $html .= '</select>';
        $html .= '<input type="hidden" name="action" value="search" />';
        $html .= '<input type="hidden" name="group_id" value="'.$this->report->getGroupId().'" />';
        $html .= '<input type="hidden" name="id" value="'. $item->getId() .'" />';
        $html .= '<noscript><input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" /></noscript>';
        $html .= '</form>';

        return $html;
    }

    function _getFilterDisplayBox($filter, $params, $trashLinkBase, &$displayedFilters) {
        $html = '';
        $htmlFilter = Docman_HtmlFilterFactory::getFromFilter($filter);
        if($htmlFilter !== null) {
            $displayedFilters[] = $filter->md->getLabel();
            $html .= $htmlFilter->toHtml('plugin_docman_filters', $trashLinkBase);
        }
        return $html;
    }

    /**
     *
     */
    function getSelectedFilters($params, &$displayedFilters) {
        $html = '';

        $html .= '<table class="docman_form">';
        $fi = $this->report->getFilterIterator();
        $trashLinkBase = $this->view->_buildSearchUrl($params, array('del_filter' => ''));

        if($fi->count() == 0) {
            $html .= '<div style="text-align:center; font-style:italic;">';
            $filterFactory = new Docman_FilterFactory($this->report->getGroupId());
            $f = $filterFactory->getFakeGlobalSearchFilter();
            $html .= $this->_getFilterDisplayBox($f, $params, false, $displayedFilters);
            $html .= '</div>';
        }

        // Display filters fields
        $fi->rewind();
        while($fi->valid()) {
            $f =& $fi->current();
            $html .= $this->_getFilterDisplayBox($f, $params, $trashLinkBase, $displayedFilters);
            $fi->next();
        }

        $ci = $this->report->getColumnIterator();
        $ci->rewind();
        while($ci->valid()) {
            $c = $ci->current();
            $html .= $c->getSortSelectorHtml();
            $ci->next();
        }
        $html .= '</table>';

        return $html;
    }

    /**
     *
     */   
    function getFiltersOptions($params, $displayedFilters) {
        $html = '';
      
        $html .= '<hr style="clear: both; margin:0.2em;" />';
        $html .= '<div id="docman_report_options">';
        $html .= '<strong>'.$GLOBALS['Language']->getText('plugin_docman', 'report_filters_options').'</strong>&nbsp;';
        //
        // Add a new filter
        //
        $html .= $GLOBALS['Language']->getText('plugin_docman', 'report_add_filter');
        $html .= '&nbsp;';
        $html .= '<select name="add_filter" onchange="docman.reportFiltersOptionsChange(this.form);">';
        $html .= $this->getSelectOption('--', '--');

        // Std metadata
        $mdFactory = new Docman_MetadataFactory($this->report->getGroupId());
        $mdIter = $mdFactory->getMetadataForGroup(true);
        $mdIter->rewind();
        while($mdIter->valid()) {
            $md =& $mdIter->current();
            if(!in_array($md->getLabel(), $displayedFilters)) {
                $html .= $this->getSelectOption($md->getLabel(), $md->getName(), '');
            }
            $mdIter->next();
        }

        // Special filters
        $gsmd = $this->report->getGlobalSearchMetadata();
        if(!in_array($gsmd->getLabel(), $displayedFilters)) {
            $html .= $this->getSelectOption('--', '--');
            $html .= $this->getSelectOption($gsmd->getLabel(), $gsmd->getName(), '');
        }

        $html .= '</select>';

        
        //
        // Advanced search
        //
        if($this->report->advancedSearch) {
            $html .= '<input type="hidden" name="advsearch" value="1" />';
            $advSearchToggle = 0;
        } else {
            $advSearchToggle = 1;
        }        
        $advSearchUrl = $this->view->_buildSearchUrl($params, array('advsearch' => $advSearchToggle));
        $html .= '&nbsp;';
        $html .= $GLOBALS['Language']->getText('plugin_docman', 'filters_advsearch_'.$advSearchToggle, array($advSearchUrl));

        $html .= '</div><!-- docman_report_options-->';


        return $html;
    }

    /**
     * 
     */
    function getReportOptions() {
        $html = '';

        $user = $this->getCurrentUser();
        $dpm  =& Docman_PermissionsManager::instance($this->report->getGroupId());

        $html .= '<div id="docman_report_save">';
        $html .= '<strong>'.$GLOBALS['Language']->getText('plugin_docman', 'report_reports_options').'</strong>&nbsp;';

        //
        // Save filter
        //
        $html .= $GLOBALS['Language']->getText('plugin_docman', 'report_save_report');
        $html .= '&nbsp;';
        $html .= '<select name="save_report" onchange="docman.reportSaveOptionsChange(this.form);">';

        $reportFactory = new Docman_ReportFactory($this->report->getGroupId());
        // For docman admin, project reports
        if($dpm->userCanAdmin($user)) {
            $reportIter = $reportFactory->getProjectReportsForGroup();
            if($reportIter->count() > 0) {
                $html .= $this->getSelectOption('--', $GLOBALS['Language']->getText('plugin_docman', 'report_save_P_reports'));
            }
            $html .= $this->getSelectOptionFromReportIterator($reportIter);
        }

        // For everyone, personal reports
        $reportIter = $reportFactory->getPersonalReportsForUser($user);
        if($reportIter->count() > 0) {
            $html .= $this->getSelectOption('--', $GLOBALS['Language']->getText('plugin_docman', 'report_save_I_reports'));
        }
        $html .= $this->getSelectOptionFromReportIterator($reportIter);

        // New report
        $html .= $this->getSelectOption('--', '--');
        $html .= $this->getSelectOption('newi', $GLOBALS['Language']->getText('plugin_docman', 'report_save_new_report_i'));
        if($dpm->userCanAdmin($user)) {
            $html .= $this->getSelectOption('newp', $GLOBALS['Language']->getText('plugin_docman', 'report_save_new_report_p'));
        }
        $html .= '</select>';

        $html .= '<noscript>';
        $html .= '&nbsp;';
        $html .= $GLOBALS['Language']->getText('plugin_docman', 'report_new_filter_name');
        $html .= '<input type="text" name="report_name" value="" />';
        $html .= '</noscript>';

        $html .= '&nbsp;';
        $settingsUrl = $this->defaultUrl.'&action=report_settings';
        $html .= '<a href="'.$settingsUrl.'">'.$GLOBALS['Language']->getText('plugin_docman', 'report_settings_my').'</a>';

        $html .= '</div><!-- docman_report_save-->';
        
        return $html;
    }

    /**
     * Entry point
     */
    function toHtml($params) {
        $html = '';

        $toggleIc = '<img src="'.util_get_image_theme("ic/toggle_minus.png").'" id="docman_toggle_filters" >';
        $toggle   = '<a href="#" onclick="docman.toggleReport(); return false;" title="'.$GLOBALS['Language']->getText('plugin_docman', 'report_toggle_tooltip').'">'.$toggleIc.'</a>';
        $title    = $GLOBALS['Language']->getText('plugin_docman', 'filters');
       
        $html .= "<div id=\"docman_filters_title\">\n";
        $html .= $toggle;
        $html .= ' '.$title;
        $html .= ' '.$this->getReportSelector($params['item']);
        $html .= "</div>\n";

        $html .= "<div id=\"docman_filters_fieldset\">\n";
        $html .= '<form name="plugin_docman_filters" method="get" action="?">';
        $html .= '<input type="hidden" name="group_id" value="'.$this->report->getGroupId().'" />';
        $html .= '<input type="hidden" name="id" value="'.$params['item']->getId().'" />';
        $html .= '<input type="hidden" name="action" value="search" />';

        $displayedFilters = array();
        $html .= $this->getSelectedFilters($params, $displayedFilters);
        $html .= $this->getFiltersOptions($params, $displayedFilters);
        $html .= $this->getReportOptions();

        $html .= '<input id="docman_report_submit" name="filtersubmit" type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_apply') .'">';
        $html .= '&nbsp;';
        $html .= '<input id="docman_report_submit" name="clear_filters" type="submit" value="'. $GLOBALS['Language']->getText('plugin_docman', 'report_clear_filters') .'">';
        
        $html .= '</form>';
        $html .= "</div>\n";

        // Force toogle of report: cannot wait full page load as in 'New
        // document' because it's way too long and the blinking is awful
        $html .= "<script type=\"text/javascript\">docman.initTableReport();</script>\n";
        $html .= "<!-- filter list-->\n";

        return $html;
    }

    function getReportImage() {
        $html = '';
        if($this->report->getImage() !== null) {
            $itemId = $this->report->getImage();
            if($itemId > 0) {
                // Get Item
                $itemFactory = new Docman_ItemFactory($this->report->getGroupId());
                $item = $itemFactory->findById($itemId);
                if($item !== null) {
                    // Check perms
                    $dPm =& Docman_PermissionsManager::instance($item->getGroupId());
                    $user =& $this->getCurrentUser();
                    $html .= "<div style=\"text-align:center\">\n";
                    if($dPm->userCanRead($user, $item->getId())) {
                        $html .= '<img src="'.$this->defaultUrl.'&id='.$itemId.'" >';
                    } else {
                        $html .= $GLOBALS['Language']->getText('plugin_docman', 'report_image_not_readable');
                    }
                    $html .= "</div>\n";
                }
            }
        }
        return $html;
    }

    function getReportCustomization($params) {
        $html = '';

        if($this->report->getDescription() !== null) {
            $html .= $this->hp->purify($this->report->getDescription(), CODEX_PURIFIER_BASIC, $this->report->getGroupId());
        }
        $html .= $this->getReportImage();

        return $html;
    }

    //
    // Accessors
    //

    function &getCurrentUser() {
        $um =& UserManager::instance();
        $user =& $um->getCurrentUser();
        return $user;
    }
}

?>
