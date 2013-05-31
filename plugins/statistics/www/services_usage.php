<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'pre.php';
require_once dirname(__FILE__).'/../include/Statistics_ServicesUsageDao.class.php';
require_once dirname(__FILE__).'/../include/Statistics_Services_UsageFormatter.php';

$pluginManager = PluginManager::instance();
$p = $pluginManager->getPluginByName('statistics');
if (!$p || !$pluginManager->isPluginAvailable($p)) {
    header('Location: '.get_server_url());
}

//Grant access only to site admin
if (!UserManager::instance()->getCurrentUser()->isSuperUser()) {
    header('Location: '.get_server_url());
}

$request = HTTPRequest::instance();

$error = false;

$vStartDate = new Valid('start');
$vStartDate->addRule(new Rule_Date());
$vStartDate->required();
$startDate = $request->get('start');
if ($request->valid($vStartDate)) {
    $startDate = $request->get('start');
} else {
    $startDate = date('Y-m-d', strtotime('-1 month'));
}

$vEndDate = new Valid('end');
$vEndDate->addRule(new Rule_Date());
$vEndDate->required();
$endDate = $request->get('end');
if ($request->valid($vEndDate)) {
    $endDate = $request->get('end');
} else {
    $endDate = date('Y-m-d');
}

if ($startDate >= $endDate) {
    $error = true;
    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_statistics', 'period_error'));
}

$groupId  = null;
$vGroupId = new Valid_GroupId();
$vGroupId->required();
if ($request->valid($vGroupId)) {
    $groupId = $request->get('group_id');
}

if (!$error && $request->exist('export')) {
    $startDate = $request->get('start');
    $endDate   = $request->get('end');

    header('Content-Type: text/csv');
    header('Content-Disposition: filename=services_usage_'.$startDate.'_'.$endDate.'.csv');
    echo "Start date : $startDate \n";
    echo "End date : $endDate \n\n";

    $dao          = new Statistics_ServicesUsageDao(CodendiDataAccess::instance(), $startDate, $endDate);
    $csv_exporter = new Statistics_Services_UsageFormatter($startDate, $endDate);

    $csv_exporter->buildDatas($dao->getNameOfActiveProjectsBeforeEndDate(), "Project");
    $csv_exporter->buildDatas($dao->getDescriptionOfActiveProjectsBeforeEndDate(), "Description");
    $csv_exporter->buildDatas($dao->getRegisterTimeOfActiveProjectsBeforeEndDate(), "Creation date");
    $csv_exporter->buildDatas($dao->getInfosFromTroveGroupLink(), "Organization");
    $csv_exporter->buildDatas($dao->getAdministrators(), "Created by");
    $csv_exporter->buildDatas($dao->getAdministratorsRealNames(), "Created by (Real name)");
    $csv_exporter->buildDatas($dao->getAdministratorsEMails(), "Created by (Email)");
    $csv_exporter->buildDatas($dao->getCVSActivities(), "CVS activities");
    $csv_exporter->buildDatas($dao->getSVNActivities(), "SVN activities");
    $csv_exporter->buildDatas($dao->getGitActivities(), "GIT activities");
    $csv_exporter->buildDatas($dao->getFilesPublished(), "Files published");
    $csv_exporter->buildDatas($dao->getDistinctFilesPublished(), "Distinct files published");
    $csv_exporter->buildDatas($dao->getNumberOfDownloadedFilesBeforeEndDate(), "Downloaded files (before end date)");
    $csv_exporter->buildDatas($dao->getNumberOfDownloadedFilesBetweenStartDateAndEndDate(), "Downloaded files (between start date and end date)");
    $csv_exporter->buildDatas($dao->getNumberOfActiveMailingLists(), "Active mailing lists");
    $csv_exporter->buildDatas($dao->getNumberOfInactiveMailingLists(), "Inactive mailing lists");
    $csv_exporter->buildDatas($dao->getNumberOfActiveForums(), "Active forums");
    $csv_exporter->buildDatas($dao->getNumberOfInactiveForums(), "Inactive forums");
    $csv_exporter->buildDatas($dao->getForumsActivitiesBetweenStartDateAndEndDate(), "Forums activities");
    $csv_exporter->buildDatas($dao->getNumberOfWikiDocuments(), "Wiki documents");
    $csv_exporter->buildDatas($dao->getNumberOfModifiedWikiPagesBetweenStartDateAndEndDate(), "Modified wiki pages");
    $csv_exporter->buildDatas($dao->getNumberOfDistinctWikiPages(), "Distinct wiki pages");
    $csv_exporter->buildDatas($dao->getNumberOfOpenArtifactsBetweenStartDateAndEndDate(), "Open artifacts");
    $csv_exporter->buildDatas($dao->getNumberOfClosedArtifactsBetweenStartDateAndEndDate(), "Closed artifacts");
    $csv_exporter->buildDatas($dao->getNumberOfUserAddedBetweenStartDateAndEndDate(), "Users added");
    $csv_exporter->buildDatas($dao->getAddedDocumentBetweenStartDateAndEndDate(), "Added documents");
    $csv_exporter->buildDatas($dao->getDeletedDocumentBetweenStartDateAndEndDate(), "Deleted documents");
    $csv_exporter->buildDatas($dao->getNumberOfNewsBetweenStartDateAndEndDate(), "News");
    $csv_exporter->buildDatas($dao->getActiveSurveys(), "Active surveys");
    $csv_exporter->buildDatas($dao->getSurveysAnswersBetweenStartDateAndEndDate(), "Surveys answers");
    $csv_exporter->buildDatas($dao->getProjectWithCIActivated(), "Continuous integration activated");
    $csv_exporter->buildDatas($dao->getNumberOfCIJobs(), "Continuous integration jobs");

    echo $csv_exporter->exportCSV();

} else {
    $title = $GLOBALS['Language']->getText('plugin_statistics', 'services_usage');
    $GLOBALS['HTML']->includeCalendarScripts();
    $GLOBALS['HTML']->header(array('title' => $title));
    echo '<h1>'.$title.'</h1>';

    echo '<form name="form_service_usage_stats" method="get">';
    echo '<table>';
    echo '<tr>';
    echo '<td>';
    echo '<b>'.$GLOBALS['Language']->getText('plugin_statistics', 'start_date').'</b>';
    echo '</td><td>';
    echo '<b>'.$GLOBALS['Language']->getText('plugin_statistics', 'end_date').'</b>';
    echo '</td>';
    echo '</tr><tr>';
    echo '<td>';
    list($timestamp,) = util_date_to_unixtime($startDate);
    echo html_field_date('start', $startDate, false, 10, 10, 'form_service_usage_stats', false);
    echo '</td><td>';
    list($timestamp,) = util_date_to_unixtime($endDate);
    echo html_field_date('end', $endDate, false, 10, 10, 'form_service_usage_stats', false);
    echo '</td>';
    echo '</tr><tr><td>';
    echo '<input type="submit" name="export" value="'.$GLOBALS['Language']->getText('plugin_statistics', 'csv_export_button').'" >';
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    echo '</form>';
    $GLOBALS['HTML']->footer(array());
}
?>
