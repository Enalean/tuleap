<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require 'pre.php';

// First, check plugin availability
$pluginManager = PluginManager::instance();
$p = $pluginManager->getPluginByName('statistics');
if (!$p || !$pluginManager->isPluginAvailable($p)) {
    header('Location: '.get_server_url());
}

// Grant access only to site admin
if (!UserManager::instance()->getCurrentUser()->isSuperUser()) {
    header('Location: '.get_server_url());
}

$title = 'Various statistics';
$GLOBALS['HTML']->header(array('title' => $title, 'main_classes' => array('tlp-framed')));
echo '<h1>'.$title.'</h1>';
?>

<dl>
    <dt><a href="frequence_stat.php">Frequencies (Graph)</a></dt>
    <dd>Display various figures about number of <em>X</em> over a given <em>period</em>.
    For instance, the numbser of <em>new user account</em> per <em>month</em> within the last
    two years.</dd>

    <dt><a href="usage_stat.php">Usage progress (CSV)</a></dt>
    <dd>Generate a CSV file that contains the progress of some data figures
    (number of users, number of project) over the time. For each
    month, it outputs the number of create project and user account.</dd>

    <dt><a href="disk_usage.php">Disk usage (Data/Graphs)</a></dt>
    <dd>Display disk usage of the platform.</dd>

    <dt><a href="scm_stats.php"><?php echo $GLOBALS['Language']->getText('plugin_statistics', 'scm_title'); ?> (CSV)</a></dt>
    <dd><?php echo $GLOBALS['Language']->getText('plugin_statistics', 'scm_description'); ?></dd>

    <dt><a href="project_quota.php"><?php echo $GLOBALS['Language']->getText('plugin_statistics', 'quota_title'); ?></a></dt>
    <dd><?php echo $GLOBALS['Language']->getText('plugin_statistics', 'quota_description'); ?></dd>

    <dt><a href="services_usage.php">Service usage</a></dt>
    <dd><?php echo $GLOBALS['Language']->getText('plugin_statistics', 'services_usage'); ?></dd>
</dl>

<?php
$GLOBALS['HTML']->footer(array());
?>
