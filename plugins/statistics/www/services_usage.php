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
    $startDate = date('Y-m-d', strtotime('-1 year'));
}

$vEndDate = new Valid('end');
$vEndDate->addRule(new Rule_Date());
$vEndDate->required();
$endDate = $request->get('end');
if ($request->valid($vEndDate)) {
    $endDate = $request->get('end');
} else {
    $endDate = date('Y-m-d', strtotime('+1 month'));
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
    header('Content-Type: text/csv');
    header('Content-Disposition: filename=scm_stats_'.$startDate.'_'.$endDate.'.csv');
    $statsSvn = new Statistics_Formatter_Svn($startDate, $endDate, $groupId);
    echo $statsSvn->getStats();
    $statsCvs = new Statistics_Formatter_Cvs($startDate, $endDate, $groupId);
    echo $statsCvs->getStats();
    $em = EventManager::instance();
    $params['formatter'] = new Statistics_Formatter($startDate, $endDate, $groupId);
    $em->processEvent('statistics_collector', $params);
    exit;
} else {
    $title = $GLOBALS['Language']->getText('plugin_statistics', 'services_usage');
    $GLOBALS['HTML']->includeCalendarScripts();
    $GLOBALS['HTML']->header(array('title' => $title));
    echo '<h1>'.$title.'</h1>';

    echo '<form name="form_scm_stats" method="get">';
    echo '<table>';
    echo '<tr>';
    echo '<td>';
    echo '<b>'.$GLOBALS['Language']->getText('plugin_statistics', 'scm_start').'</b>';
    echo '</td><td>';
    echo '<b>'.$GLOBALS['Language']->getText('plugin_statistics', 'scm_end').'</b>';
    echo '</td>';
    echo '</tr><tr>';
    echo '<td>';
    list($timestamp,) = util_date_to_unixtime($startDate);
    echo html_field_date('start', $startDate, false, 10, 10, 'form_scm_stats', false);
    echo '</td><td>';
    list($timestamp,) = util_date_to_unixtime($endDate);
    echo html_field_date('end', $endDate, false, 10, 10, 'form_scm_stats', false);
    echo '</td>';
    echo '</tr><tr><td>';
    echo '<input type="submit" name="export" value="'.$GLOBALS['Language']->getText('plugin_statistics', 'scm_export_button').'" >';
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    echo '</form>';

    $dao = new Statistics_ServicesUsageDao(CodendiDataAccess::instance(), $startDate, $endDate);
    var_dump($dao->getNameOfActiveProjectsBeforeEndDate());

    echo '<hr/>';
    var_dump($dao->getDescriptionOfActiveProjectsBeforeEndDate());
    echo '<hr/>';
    var_dump($dao->getRegisterTimeOfActiveProjectsBeforeEndDate());
    echo '<hr/>';
    var_dump($dao->getInfosFromTroveGroupLink());
    echo '<hr/>';
    var_dump($dao->getAdministrators());
    echo '<hr/>';
    var_dump($dao->getAdministratorsRealNames());
    echo '<hr/>';
    var_dump($dao->getAdministratorsEMails());
    echo '<hr/>';
    var_dump($dao->getCVSActivities());
    echo '<hr/>';
    var_dump($dao->getSVNActivities());
    echo '<hr/>';
    var_dump($dao->getGitActivities());
    echo '<hr/>';
    var_dump($dao->getFilesPublished());
    echo '<hr/>';
    var_dump($dao->getDistinctFilesPublished());
    echo '<hr/>';
    var_dump($dao->getNumberOfDownloadedFilesBeforeEndDate());
    echo '<hr/>';
    var_dump($dao->getNumberOfDownloadedFilesBetweenStartDateAndEndDate());
    echo '<hr/>';
    var_dump($dao->getNumberOfActiveMailingLists());
    echo '<hr/>';
    var_dump($dao->getNumberOfInactiveMailingLists());
    echo '<hr/>';
    var_dump($dao->getNumberOfActiveForums());
    echo '<hr/>';
    var_dump($dao->getNumberOfInactiveForums());
    echo '<hr/>';
    var_dump($dao->getForumsActivitiesBetweenStartDateAndEndDate());
    echo '<hr/>';
    var_dump($dao->getNumberOfWikiDocuments());
    echo '<hr/>';
    var_dump($dao->getNumberOfModifiedWikiPagesBetweenStartDateAndEndDate());
    echo '<hr/>';
    var_dump($dao->getNumberOfDistinctWikiPages());
    echo '<hr/>';
    var_dump($dao->getNumberOfOpenArtifactsBetweenStartDateAndEndDate());
    echo '<hr/>';
    var_dump($dao->getNumberOfClosedArtifactsBetweenStartDateAndEndDate());
    echo '<hr/>';
    var_dump($dao->getNumberOfUserAddedBetweenStartDateAndEndDate());
    echo '<hr/>';
    var_dump($dao->getProjectCode());
    echo '<hr/>';
    var_dump($dao->getAddedDocumentBetweenStartDateAndEndDate());
    echo '<hr/>';
    var_dump($dao->getDeletedDocumentBetweenStartDateAndEndDate());
    echo '<hr/>';
    var_dump($dao->getNumberOfNewsBetweenStartDateAndEndDate());
    echo '<hr/>';
    var_dump($dao->getActiveSurveys());
    echo '<hr/>';
    var_dump($dao->getSurveysAnswersBetweenStartDateAndEndDate());
    echo '<hr/>';
    var_dump($dao->getProjectWithCIActivated());
    echo '<hr/>';
    var_dump($dao->getNumberOfCIJobs());
    $GLOBALS['HTML']->footer(array());
}
?>
