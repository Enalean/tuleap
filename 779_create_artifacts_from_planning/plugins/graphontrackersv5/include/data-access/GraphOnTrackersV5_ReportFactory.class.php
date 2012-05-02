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

 
require_once('GraphOnTrackersV5_Report.class.php');
require_once('GraphOnTrackersV5_ReportDao.class.php');

class GraphOnTrackersV5_ReportFactory {

    /**
     * Copy the reports from the tracker $from_id to the tracker $to_id
     * The copied reports must have scope == 'P'
     */
    public function copyReports($from_id, $to_id) {
        $user_id = UserManager::instance()->getCurrentUser()->getId();
        $dao = new GraphOnTrackersV5_ReportDao(CodendiDataAccess::instance());
        foreach($dao->searchByTrackerIdAndScope($from_id, 'P') as $row) {
            //retrieve the report
            $report = new GraphOnTrackersV5_Report($row['report_graphic_id']);
            //Create a new one
            $copied_report = GraphOnTrackersV5_Report::create($to_id, $user_id, $report->getName(), $report->getDescription(), $report->getScope());
            
            //Copy the charts
            $this->copyCharts($report, $copied_report);
        }
    }
    
    /**
     * Copy the charts from Report $from_report to Report $to_report
     */
    public function copyCharts($from_report, $to_report) {
        foreach($from_report->getCharts() as $c) {
            $new_chart = $to_report->createChart($c->getChartType());
            $row = $c->getRow();
            $row['id'] = $new_chart->getId();
            $row['rank'] = 'end';
            $new_chart->update($row);
        }
    }
}
?>
