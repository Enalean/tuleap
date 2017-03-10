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
require_once ('Statistics_DiskUsageGraph.class.php');

use Tuleap\SVN\DiskUsage\Retriever;
use Tuleap\SVN\DiskUsage\Collector;

/**
 * Statisitics_Widget_ProjectStatistics
 */
class Statistics_Widget_ProjectStatistics extends Widget {

    /**
     * Constructor
     *
     * @return void
     */
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
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');

        $disk_usage_dao = new Statistics_DiskUsageDao();
        $svn_log_dao    = new SVN_LogDao();
        $retriever      = new Retriever($disk_usage_dao);
        $collector      = new Collector($svn_log_dao, $retriever);

        $duMgr  = new Statistics_DiskUsageManager($disk_usage_dao, $collector, EventManager::instance());
        $duHtml = new Statistics_DiskUsageHtml($duMgr);

        return $duHtml->getTotalProjectSize($group_id);
    }

    /**
     * Widget is used in project summary page
     *
     * @param $project current project
     *
     * @return Boolean
     *
     * @see Widget::canBeUsedByProject()
     */
    public function canBeUsedByProject($project) {
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