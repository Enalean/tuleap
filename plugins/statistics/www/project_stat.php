<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

require_once __DIR__ . '/../../../src/www/include/pre.php';
require_once __DIR__ . '/../include/Statistics_DiskUsageHtml.class.php';

use Tuleap\Statistics\DiskUsagePie\DiskUsagePieDisplayer;
use Tuleap\Statistics\DiskUsage\Subversion\Collector as SVNCollector;
use Tuleap\Statistics\DiskUsage\Subversion\Retriever as SVNRetriever;

// First, check plugin availability
$pluginManager = PluginManager::instance();
$p             = $pluginManager->getPluginByName('statistics');
if (! $p || ! $pluginManager->isPluginEnabled($p)) {
    $GLOBALS['Response']->redirect('/');
}

$vGroupId = new Valid_UInt('group_id');
$vGroupId->required();
if ($request->valid($vGroupId)) {
    $groupId = $request->get('group_id');
    $project = ProjectManager::instance()->getProject($groupId);
} else {
    $GLOBALS['Response']->redirect('/');
}

// Grant access only to project admins
$user = UserManager::instance()->getCurrentUser();
if (! $user->isAdmin($groupId)) {
    $GLOBALS['Response']->redirect('/');
}

$vPeriod = new Valid_WhiteList('period', ['year', 'months']);
$vPeriod->required();
if ($request->valid($vPeriod)) {
    $period = $request->get('period');
} else {
    $period = 'months';
}

if ($period === 'year') {
    $GLOBALS['Response']->addFeedback('info', dgettext('tuleap-statistics', 'The chart displayed uses old data which has been purged in order to display it quickly. The purge operation keeps one day\'s worth of data for each week ended more than 3 months ago. Beyond 2 years ago, one day of each month is kept.'));
}

$disk_usage_dao     = new Statistics_DiskUsageDao();
$svn_log_dao        = new SVN_LogDao();
$svn_retriever      = new SVNRetriever($disk_usage_dao);
$svn_collector      = new SVNCollector($svn_log_dao, $svn_retriever);
$disk_usage_manager = new Statistics_DiskUsageManager(
    $disk_usage_dao,
    $svn_collector,
    EventManager::instance()
);

$duHtml = new Statistics_DiskUsageHtml($disk_usage_manager);

// selected service
$vServices = new Valid_WhiteList('services', array_keys($disk_usage_manager->getProjectServices(false)));
$vServices->required();
if ($request->validArray($vServices)) {
    $selectedServices = $request->get('services');
} else {
    $selectedServices = array_keys($disk_usage_manager->getProjectServices(false));
}

if ($project && ! $project->isError()) {
    // Prepare params
    $serviceParam = '';
    $first        = true;
    foreach ($selectedServices as $serv) {
        if ($first != true) {
            $serviceParam .= '&';
        }
        $serviceParam .= 'services[]=' . $serv;
        $first         = false;
    }

    //Get dates for start and end period to watch statistics
    $info       = $p->getPluginInfo();
    $statPeriod = $info->getPropertyValueForName('statistics_period');
    if (! $statPeriod) {
        $statPeriod = 3;
    }

    if ($period == 'year') {
        $statDuration = 12;
        $link         = '?' . $serviceParam . '&group_id=' . $groupId . '&period=months';
    } else {
        $statDuration = $statPeriod;
        $link         = '?' . $serviceParam . '&group_id=' . $groupId . '&period=year';
    }

    $endDate   = date('Y-m-d');
    $startDate = date('Y-m-d', mktime(0, 0, 0, date('m') - $statDuration, date('d'), date('y')));

    $params['group'] = $groupId;
    $params['title'] = _('Project Admin') . ': ' . $project->getPublicName();
    project_admin_header($params, \Tuleap\Project\Admin\Navigation\NavigationPresenterBuilder::DATA_ENTRY_SHORTNAME);

    echo '<h2>' . dgettext('tuleap-statistics', 'Disk usage') . '</h2>';
    $usedProportion        = $disk_usage_manager->returnTotalProjectSize($groupId);
    $allowedQuota          = $disk_usage_manager->getProperty('allowed_quota');
    $project_quota_manager = new ProjectQuotaManager();
    $customQuota           = $project_quota_manager->getProjectCustomQuota($groupId);
    if ($customQuota) {
        $allowedQuota = $customQuota;
    }
    if ($allowedQuota) {
        echo '<div id="help_init" class="stat_help">' . sprintf(dgettext('tuleap-statistics', 'This project uses <b>%1$s</b> of the <b>%2$s</b> allowed quota.'), $duHtml->sizeReadable($usedProportion), $allowedQuota . 'GiB') . '</div>';

        $pie_displayer = new DiskUsagePieDisplayer(
            $disk_usage_manager,
            $project_quota_manager,
            new Statistics_DiskUsageOutput(
                $disk_usage_manager
            )
        );

        $pie_displayer->displayDiskUsagePie($project);
    } else {
        echo '<LABEL><b>';
        echo dgettext('tuleap-statistics', 'Total project size:');
        echo '</b></LABEL>';
        echo $duHtml->sizeReadable($usedProportion);
    }

    $title      = dgettext('tuleap-statistics', 'Disk usage for the last year');
    $link_label = sprintf(dgettext('tuleap-statistics', 'View statistics for the last %1$s months'), $statPeriod);
    if ($period === 'months') {
        $title      = sprintf(dgettext('tuleap-statistics', 'Disk usage for the %1$s last months'), $statDuration);
        $link_label = dgettext('tuleap-statistics', 'View statistics for the last year');
    }
    //Display tooltip for start and end date.
    echo '<h2><span class="plugin_statistics_period" data-test="statistics-period" title="' . sprintf(dgettext('tuleap-statistics', 'From %1$s to %2$s.'), $startDate, $endDate) . '">' . $title . '</span></h2>';
    echo '<div class="stat_help">' . dgettext('tuleap-statistics', "Differences may exist between actual size of a project/service and statistics which are computed daily") . '</div>';
    echo '<p><a href="' . $link . '" data-test="last-year-statistics">' . $link_label . '</a></p>';
    echo '<form name="progress_by_service" method="get" action="?">';
    echo '<input type="hidden" name="group_id" value="' . $groupId . '" />';
    echo '<input type="hidden" name="period" value="' . $period . '" />';
    echo '<table>';
    echo '<tr>';
    echo '<th>Services</th>';
    echo '</tr>';

    echo '<tr>';
    $services = [];
    foreach ($disk_usage_manager->getProjectServices(false) as $service => $label) {
        $services[] = ['value' => $service, 'text' => $label];
    }
    echo '<td valign="top">';
    echo html_build_multiple_select_box_from_array($services, 'services[]', $selectedServices, '6', false, '', false, '', false, '', false) . ' ';
    echo '</td>';
    echo '</tr>';
    echo '</table>';

    echo '<input type="submit" value="' . $GLOBALS['Language']->getText('global', 'btn_submit') . '"/>';
    echo '</form>';

    echo '<table><tr><td valign="top">';
    $duHtml->getServiceEvolutionForPeriod($startDate, $endDate, $groupId, true);
    echo '</td><td valign="top"><img src="project_stat_graph.php?' . $serviceParam . '&group_id=' . $groupId . '&start_date=' . $startDate . '&end_date=' . $endDate . '" title="Project disk usage graph" />';
    echo '</td></tr></table>';

    site_project_footer($params);
} else {
    $GLOBALS['Response']->redirect('/');
}
