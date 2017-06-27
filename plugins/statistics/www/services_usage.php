<?php
/**
 * Copyright (c) Enalean, 2013 - 2017. All Rights Reserved.
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
require_once dirname(__FILE__).'/../include/Statistics_Services_UsageFormatter.class.php';
require_once dirname(__FILE__).'/../include/Statistics_Formatter.class.php';
require_once dirname(__FILE__).'/../include/Statistics_DiskUsageHtml.class.php';
require_once('www/project/export/project_export_utils.php');

use Tuleap\SVN\DiskUsage\Collector as SVNCollector;
use Tuleap\SVN\DiskUsage\Retriever as SVNRetriever;
use Tuleap\CVS\DiskUsage\Retriever as CVSRetriever;
use Tuleap\CVS\DiskUsage\Collector as CVSCollector;
use Tuleap\CVS\DiskUsage\FullHistoryDao;

$pluginManager = PluginManager::instance();
$p = $pluginManager->getPluginByName('statistics');
if (! $p || ! $pluginManager->isPluginAvailable($p)) {
    $GLOBALS['Response']->redirect('/');
}

//Grant access only to site admin
if (! UserManager::instance()->getCurrentUser()->isSuperUser()) {
    $GLOBALS['Response']->redirect('/');
}

set_time_limit(180);

$request = HTTPRequest::instance();

$vStartDate = new Valid('services_usage_start_date');
$vStartDate->addRule(new Rule_Date());
$vStartDate->required();
$startDate = $request->get('services_usage_start_date');
if ($request->valid($vStartDate)) {
    $startDate = $request->get('services_usage_start_date');
} else {
    $startDate = date('Y-m-d', strtotime('-1 month'));
}

$vEndDate = new Valid('services_usage_end_date');
$vEndDate->addRule(new Rule_Date());
$vEndDate->required();
$endDate = $request->get('services_usage_end_date');
if ($request->valid($vEndDate)) {
    $endDate = $request->get('services_usage_end_date');
} else {
    $endDate = date('Y-m-d');
}

if ($startDate > $endDate) {
    $GLOBALS['Response']->addFeedback(
        Feedback::ERROR,
        $GLOBALS['Language']->getText('plugin_statistics', 'period_error')
    );
    $GLOBALS['Response']->redirect('/plugins/statistics/data_export.php');
}

if ($request->exist('export') && $startDate && $endDate) {

    header('Content-Type: text/csv');
    header('Content-Disposition: filename=services_usage_'.$startDate.'_'.$endDate.'.csv');
    echo "Start date : $startDate \n";
    echo "End date : $endDate \n\n";

    $dao          = new Statistics_ServicesUsageDao(CodendiDataAccess::instance(), $startDate, $endDate);
    $csv_exporter = new Statistics_Services_UsageFormatter(new Statistics_Formatter($startDate, $endDate, get_csv_separator()));

    //Project admin
    $csv_exporter->buildDatas($dao->getIdsOfActiveProjectsBeforeEndDate(), "Project ID");
    $csv_exporter->buildDatas($dao->getNameOfActiveProjectsBeforeEndDate(), "Project Name");
    $csv_exporter->buildDatas($dao->getShortNameOfActiveProjectsBeforeEndDate(), "Project Short Name");
    $csv_exporter->buildDatas($dao->getPrivacyOfActiveProjectsBeforeEndDate(), "Public Project");
    $csv_exporter->buildDatas($dao->getDescriptionOfActiveProjectsBeforeEndDate(), "Description");
    $csv_exporter->buildDatas($dao->getRegisterTimeOfActiveProjectsBeforeEndDate(), "Creation date");
    $csv_exporter->buildDatas($dao->getInfosFromTroveGroupLink(), "Organization");
    $csv_exporter->buildDatas($dao->getAdministrators(), "Created by");
    $csv_exporter->buildDatas($dao->getAdministratorsRealNames(), "Created by (Real name)");
    $csv_exporter->buildDatas($dao->getAdministratorsEMails(), "Created by (Email)");
    $csv_exporter->buildDatas($dao->getNumberOfUserAddedBetweenStartDateAndEndDate(), "Users added");

    //Custom Descriptions
    $custom_description_factory = new Project_CustomDescription_CustomDescriptionFactory(
        new Project_CustomDescription_CustomDescriptionDao()
    );
    $custom_description_value_dao = new Project_CustomDescription_CustomDescriptionValueDao();
    foreach ($custom_description_factory->getCustomDescriptions() as $custom_description) {
        $csv_exporter->buildDatas(
            $custom_description_value_dao->getAllDescriptionValues($custom_description->getId()),
            $custom_description->getLabel()
        );
    }

    //Trove Cats
    $trove_cat_dao         = new TroveCatDao();
    $trove_cat_factory     = new TroveCatFactory($trove_cat_dao);
    $mandatories_trove_cat = $trove_cat_factory->getMandatoryParentCategoriesUnderRoot();

    foreach ($mandatories_trove_cat as $trove_cat) {
        $csv_exporter->buildDatas(
            $trove_cat_dao->getMandatoryCategorySelectForAllProject($trove_cat->getId()),
            $trove_cat->getFullname()
        );
    }

    //CVS & SVN
    $csv_exporter->buildDatas($dao->getCVSActivities(), "CVS activities");
    $csv_exporter->buildDatas($dao->getSVNActivities(), "SVN activities");

    //GIT
    $p = $pluginManager->getPluginByName('git');
    if ($p && $pluginManager->isPluginAvailable($p)) {
        $csv_exporter->buildDatas($dao->getGitWrite(), "GIT write");
        $csv_exporter->buildDatas($dao->getGitRead(), "GIT read");
    }

    //FRS
    $csv_exporter->buildDatas($dao->getFilesPublished(), "Files published");
    $csv_exporter->buildDatas($dao->getDistinctFilesPublished(), "Distinct files published");
    $csv_exporter->buildDatas($dao->getNumberOfDownloadedFilesBeforeEndDate(), "Downloaded files (before end date)");
    $csv_exporter->buildDatas($dao->getNumberOfDownloadedFilesBetweenStartDateAndEndDate(), "Downloaded files (between start date and end date)");
    $csv_exporter->buildDatas($dao->getNumberOfActiveMailingLists(), "Active mailing lists");
    $csv_exporter->buildDatas($dao->getNumberOfInactiveMailingLists(), "Inactive mailing lists");

    //Forums
    $csv_exporter->buildDatas($dao->getNumberOfActiveForums(), "Active forums");
    $csv_exporter->buildDatas($dao->getNumberOfInactiveForums(), "Inactive forums");
    $csv_exporter->buildDatas($dao->getForumsActivitiesBetweenStartDateAndEndDate(), "Forums activities");

    //PHPWiki
    $csv_exporter->buildDatas($dao->getNumberOfWikiDocuments(), "Wiki documents");
    $csv_exporter->buildDatas($dao->getNumberOfModifiedWikiPagesBetweenStartDateAndEndDate(), "Modified wiki pages");
    $csv_exporter->buildDatas($dao->getNumberOfDistinctWikiPages(), "Distinct wiki pages");

    //Trackers v3
    $csv_exporter->buildDatas($dao->getNumberOfOpenArtifactsBetweenStartDateAndEndDate(), "Open artifacts");
    $csv_exporter->buildDatas($dao->getNumberOfClosedArtifactsBetweenStartDateAndEndDate(), "Closed artifacts");

    //Docman
    $csv_exporter->buildDatas($dao->getAddedDocumentBetweenStartDateAndEndDate(), "Added documents");
    $csv_exporter->buildDatas($dao->getDeletedDocumentBetweenStartDateAndEndDate(), "Deleted documents");

    //News
    $csv_exporter->buildDatas($dao->getNumberOfNewsBetweenStartDateAndEndDate(), "News");

    //CI
    $p = $pluginManager->getPluginByName('hudson');
    if ($p && $pluginManager->isPluginAvailable($p)) {
        $csv_exporter->buildDatas($dao->getProjectWithCIActivated(), "Continuous integration activated");
        $csv_exporter->buildDatas($dao->getNumberOfCIJobs(), "Continuous integration jobs");
    }

    //Disk usage
    exportDiskUsageForDate($csv_exporter, $startDate, "Disk usage at start date (MB)");
    exportDiskUsageForDate($csv_exporter, $endDate, "Disk usage at end date (MB)");

    // Let plugins add their own data
    EventManager::instance()->processEvent(
        'plugin_statistics_service_usage',
        array(
            'csv_exporter' => $csv_exporter,
            'start_date'   => $startDate,
            'end_date'     => $endDate
        )
    );

    echo $csv_exporter->exportCSV();

}

function exportDiskUsageForDate(Statistics_Services_UsageFormatter $csv_exporter, $date, $column_name)
{
    $disk_usage_dao  = new Statistics_DiskUsageDao();
    $svn_log_dao     = new SVN_LogDao();
    $svn_retriever   = new SVNRetriever($disk_usage_dao);
    $svn_collector   = new SVNCollector($svn_log_dao, $svn_retriever);
    $cvs_history_dao = new FullHistoryDao();
    $cvs_retriever   = new CVSRetriever($disk_usage_dao);
    $cvs_collector   = new CVSCollector($cvs_history_dao, $cvs_retriever);

    return new Statistics_DiskUsageManager(
        $disk_usage_dao,
        $svn_collector,
        $cvs_collector,
        EventManager::instance()
    );
}
