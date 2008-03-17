<?php
/**
 * Copyright � STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * $Id$
 */

require_once('Docman_Report.class.php');
require_once('Docman_FilterFactory.class.php');
require_once('Docman_ReportColumnFactory.class.php');
require_once('Docman_SettingsBo.class.php');
require_once('Docman_ReportDao.class.php');
require_once('Docman_PermissionsManager.class.php');

class Docman_ReportFactory {
    var $groupId;

    function Docman_ReportFactory($groupId) {
        $this->groupId = $groupId;
    }

    /**
     * Create a report for table view based on URL
     */
    function &get($reportId, $request, $item, &$feedback) {
        $report = null;

        $report = new Docman_Report();

        // Drop all filters
        if($request->exist('clear_filters')) {
            $this->initReport($report, $request, $item);
            $this->initColumns($report, $request);
            return $report;
        }
            
        $noDbReport = true;
        // First, try to find a report in DB
        //if($request->exist('report_id')) {
        //    $reportId = (int) $request->get('report_id');
        // todo Verify validity of the info
        //}
        if($reportId > 0) {
            // todo Verify validity of the info
            $dao =& $this->getDao();
            $dar = $dao->searchById($reportId);
            if($dar && !$dar->isError() && $dar->rowCount() == 1) {
                $noDbReport = false;
                $row = $dar->getRow();
                $report = new Docman_Report();
                $report->initFromRow($row);
                        
                $filterFactory = new Docman_FilterFactory($this->groupId);
                $fa = $filterFactory->addFiltersToReport($report);
                    
                $this->initColumns($report, $request);
            }
        }
            
            
        if($noDbReport) {
            // Init from url
            $this->initReport($report, $request, $item);
            $this->initFilters($report, $request, $feedback);
            $this->initColumns($report, $request);
        }

        // Save current report
        if($request->exist('save_report')) {
            $um   =& UserManager::instance();
            $user = $um->getCurrentUser();
            $dpm  =& Docman_PermissionsManager::instance($this->groupId);

            $report->setUserId($user->getId());

            // New report
            if($request->get('save_report') == 'newp' 
               || $request->get('save_report') == 'newi') {
                if($request->exist('report_name')) { 
                    $reportName = $request->get('report_name');
                    // todo Validate report name
                    $report->setScope('I');
                    if($dpm->userCanAdmin($user)) {
                        if($request->get('save_report') == 'newp') {
                            $report->setScope('P');
                        }
                    }
                    $report->setName($reportName);
                    $this->saveReport($report);
                }
            }
            // Override an existing one
            if(is_numeric($request->get('save_report'))) {
                $reportId = (int) $request->get('save_report');

                // validate reportId
                $updReportOk = false;
                $refReport = $this->getReportById($reportId);
                if($refReport !== null) {
                    if($refReport->getGroupId() == $this->groupId) {
                        if($dpm->userCanAdmin($user)){
                            $updReportOk = true;
                        } else {
                            if($refReport->getScope() == 'I' 
                               && $refReport->getUserId() == $user->getId()) {
                                $updReportOk = true;
                            }
                        }
                    }
                    
                }
                
                if($updReportOk) {
                    if($request->exist('report_name') && trim($request->get('report_name')) != '') {
                        $refReport->setName($request->get('report_name'));
                    }
                    $refReport->setItemId($item->getId());
                    // Replace filters in ref report by the filters built from the URL.
                    $refReport->setFiltersArray($report->getFiltersArray());

                    $this->saveReport($refReport);
                }
            }
                
        }
        return $report;
    }
    
    function initReport(&$report, $request, $item) {
        if($request->exist('advsearch')
           && $request->get('advsearch') == 1) {
            $report->setAdvancedSearch(true);
        }
        $report->setItemId($item->getId());
        $report->setGroupId($this->groupId);
    }

    function initFilters(&$report, $request, &$feedback) {        
        $filterFactory = new Docman_FilterFactory($this->groupId);

        $mdFactory = new Docman_MetadataFactory($this->groupId);
        $mdIter = $mdFactory->getMetadataForGroup(true);
        $mdIter->rewind();
        while($mdIter->valid()) {
            $md = $mdIter->current();
            $filter = $filterFactory->createFilterOnMatch($md, $request, $report->getAdvancedSearch());
            $this->_validateFilterAndCreate($report, $filter, $feedback);
            $mdIter->next();
        }
        // Special case for a fake metadata: generic text search
        $filter = $filterFactory->getGlobalSearchFilter($request);
        $this->_validateFilterAndCreate($report, $filter, $feedback);
    }

    function _validateFilterAndCreate(&$report, $filter, &$feedback) {
        if($filter !== null) {
            // Validate submitted paramters
            $validateFilterFactory = new Docman_ValidateFilterFactory();
            $validateFilter = $validateFilterFactory->getFromFilter($filter);
            if($validateFilter !== null) {
                if(!$validateFilter->validate()) {
                    $feedback->log('error', $validateFilter->getMessage());
                }
            }
            $report->addFilter($filter);
            unset($filter);
        }
    }

    /**
     * The code to display the columns is now totally dynamic and can display
     * all the metadata of the project. However it raises 2 problems:
     * - To be able to fetch all the metadata efficently. We cannot rely on a
     *   code that fetch metadata values for each item individualy.
     * - To be able to sort on dynamic metadata (values in metadata_value
     *   table). This issue is more complex to handle because we will have to
     *   intoduce metadata_value table in searchItemVersion query and it will
     *   be a lot more complex (On JOIN per metadata to display, ...)
     *
     * Today, the customization of the report is not a requirement so I don't
     * develop the feature. We only provide static metadata to display on table
     * report.
     */
    function initColumns(&$report, $request) {
        $settingsBo =  Docman_SettingsBo::instance($this->groupId);
        $useStatus = $settingsBo->getMetadataUsage('status');

        if($useStatus) {
            $columnsOnReport = array('status', 'title', 'description', 'location', 'owner', 'update_date');
            // report with a dynamic field:
            //$columnsOnReport = array('status', 'title', 'description', 'field_2', 'location', 'owner', 'update_date');
        }
        else {
            $columnsOnReport = array('title', 'description', 'location', 'owner', 'update_date');
        }
        $keepRefOnUpdateDate = null;
        $thereIsAsort = false;

        $colFactory = new Docman_ReportColumnFactory($this->groupId);
        foreach($columnsOnReport as $colLabel) {
            $column = $colFactory->getColumnFromLabel($colLabel);
            if($column !== null) {
                $column->initFromRequest($request);

                // If no sort, sort on update_date in DESC by default
                if($colLabel == 'update_date') {
                    $keepRefOnUpdateDate =& $column;
                }
                if($column->getSort() !== null) {
                    $thereIsAsort = true;
                }

                $report->addColumn($column);
            }
            unset($column);
        }
        if(!$thereIsAsort && $keepRefOnUpdateDate !== null) {
            $keepRefOnUpdateDate->setSort(PLUGIN_DOCMAN_SORT_DESC);
        }
    }

    function getReportById($id) {
        $report = null;
        $dao =& $this->getDao();
        $dar = $dao->searchById($id);
        if($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $report = new Docman_Report();
            $report->initFromRow($dar->current());
        }
        return $report;
    }

    function getProjectReportsForGroup() {
        $ra = array();
        $dao =& $this->getDao();
        $dar = $dao->searchProjectReportByGroupId($this->groupId);
        $i = 0;
        while($dar->valid()) {
            $ra[$i] = new Docman_Report();
            $ra[$i]->initFromRow($dar->current());
            $i++;
            $dar->next();
        }
        $rai = new ArrayIterator($ra);
        return $rai;
    }

    function getPersonalReportsForUser($user) {
        $ra = array();
        $dao =& $this->getDao();
        $dar = $dao->searchPersonalReportByUserId($this->groupId, $user->getId());
        $i = 0;
        while($dar->valid()) {
            $ra[$i] = new Docman_Report();
            $ra[$i]->initFromRow($dar->current());
            $i++;
            $dar->next();
        }
        $rai = new ArrayIterator($ra);
        return $rai;
    }

    function saveReport($report) {
        if($report->getId() !== null) {
            $this->updateReport($report);
        } else {
            $this->createReport($report);
        }
    }

    function updateReport($report) {
        $success = $this->updateReportSettings($report);
        if($success) {
            $filterFactory = new Docman_FilterFactory($this->groupId);
            $filterFactory->truncateFilters($report);
            $filterFactory->createFiltersFromReport($report);
        }
    }

    function updateReportSettings($report) {
        $dao =& $this->getDao();
        return $dao->updateReport($report->getId(), $report->getName(), $report->getTitle(), $report->getItemId(), $report->getAdvancedSearch(), $report->getScope(), $report->getDescription(), $report->getImage());
    }

    function createReport($report) {
        $dao =& $this->getDao();

        // report
        $id = $dao->create($report->getName(), $report->getTitle(), $report->getGroupId(), $report->getUserId(), $report->getItemId(), $report->getScope(), $report->getIsDefault(), $report->getAdvancedSearch(), $report->getDescription(), $report->getImage());
        
        if($id) {
            $report->setId($id);
            // filters
            $filterFactory = new Docman_FilterFactory($this->groupId);
            $filterFactory->createFiltersFromReport($report);
            return $id;
        }
        return false;
    }

    function deleteReport($report) {
        $dao =& $this->getDao();
        $filterFactory = new Docman_FilterFactory($this->groupId);
        if($filterFactory->truncateFilters($report)) {
            return $dao->deleteById($report->getId());
        } else {
            return false;
        }
    }
    
    /**
     *
     */
    function cloneReport($srcReport, $dstGroupId, $metadataMapping, $user, $forceScopeToI = false) {
        $dstReportFactory = new Docman_ReportFactory($dstGroupId);
        $srcFilterFactory = new Docman_FilterFactory($this->groupId);

        // Create new report
        $dstReport = $srcReport;
        $dstReport->setGroupId($dstGroupId);
        $dstReport->setUserId($user->getId());
        if($forceScopeToI) {
            $dstReport->setScope('I');
        }

        // Save report
        $rId = $dstReportFactory->createReport($dstReport);
        if($rId !== false) {
            $dstReport->setId($rId);
        
            // Copy filters
            $srcFilterFactory->copy($srcReport, $dstReport, $metadataMapping);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Clone reports from a project to another one
     */
    function copy($dstGroupId, $metadataMapping, $user, $forceScope = false) {
        $ri = $this->getProjectReportsForGroup();
        $ri->rewind();
        while($ri->valid()) {
            $srcReport =& $ri->current();
            $this->cloneReport($srcReport, $dstGroupId, $metadataMapping, $user, $forceScope);
            $ri->next();
        }
    }

    //
    // Object accessor
    //
    function &getDao() {
        $dao = new Docman_ReportDao(CodexDataAccess::instance());
        return $dao;
    }

}

?>
