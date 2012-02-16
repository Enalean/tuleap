<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'common/project/Service.class.php';
require_once dirname(__FILE__).'/../../tracker/include/Tracker/Report/Tracker_Report.class.php';

class AgileDashboardView {
    
    /**
     * @var Service
     */
    private $service;
    
    /**
     * @var BaseLanguage
     */
    private $language;
    
    /**
     * @var Tracker_Report
     */
    private $report;
    
    /**
     * @var Array of Tracker_Report_Criteria
     */
    private $criteria;
    
    public function __construct(Service $service, BaseLanguage $language, Tracker_Report $report, array $criteria) {
        $this->language = $language;
        $this->service  = $service;
        $this->report   = $report;
        $this->criteria = $criteria;
    }
    
    public function render() {
        $title = $this->language->getText('plugin_agiledashboard', 'title');
        
        $this->service->displayHeader($title, array(), array());
        
        $report_can_be_modified = false;
        echo $this->report->fetchDisplayQuery($this->criteria, $report_can_be_modified);
        
        $this->service->displayFooter();
    }
}
?>
