<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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

require_once('common/widget/Widget.class.php');
require_once('Statistics_DiskUsageHtml.class.php');

/**
 * Statisitics_Widget_ProjectStatistics
 */
class Statistics_Widget_ProjectStatistics extends Widget {
    public function __construct() {
        $this->Widget('plugin_statistics_projectstatistics');
    }

    /**
     * Return the title of the widget
     *
     * @return String
     *
     * @see Widget::getTitle()
     */
    public function getTitle() {
        return $GLOBALS['Language']->getText('plugin_statistics', 'widget_title_projectstatistics');
    }

    /**
     * Show the content of the widget
     *
     * @return void
     *
     * @see Widget::getContent()
     */
    public function getContent() {
        //TODO : add cumulative graph
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');

        $duMgr  = new Statistics_DiskUsageManager();
        $duHtml = new Statistics_DiskUsageHtml($duMgr);

        $duMgrDao = $duMgr->_getDao();
        $recentDate = $duMgrDao->searchMostRecentDate();
        $totalSize = $duMgrDao->returnTotalSizeProject($group_id,$recentDate);
        $projectSize= $totalSize->getRow();
        echo '<label><b>';
        echo $GLOBALS['Language']->getText('plugin_statistics', 'widget_total_project_size');
        echo '</b></label>';
        echo $duHtml->sizeReadable($projectSize['size']);
    }

    /**
     * @param $project current project
     *
     * @return Boolean
     *
     * @see Widget::canBeUsedByProject()
     */
    public function canBeUsedByProject(&$project) {
        return true;
    }

    /**
     * Return the widget description
     *
     * @return String
     *
     * @see Widget::getDescription()
     */
    function getDescription() {
        return $GLOBALS['Language']->getText('plugin_statistics', 'widget_desc_projectstatistics');
    }

    /**
     * Return the widget category
     *
     * @return string
     *
     * @see Widget::getCategory()
     */
    function getCategory() {
        return 'plugin_statistics';
    }
}
?>