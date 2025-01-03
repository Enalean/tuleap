<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
 * Copyright (c) Enalean, 2017-Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

use Tuleap\Statistics\DiskUsage\Subversion\Collector as SVNCollector;
use Tuleap\Statistics\DiskUsage\Subversion\Retriever as SVNRetriever;

/**
 * Statisitics_Widget_ProjectStatistics
 */
class Statistics_Widget_ProjectStatistics extends Widget
{
    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct('plugin_statistics_projectstatistics');
    }

    /**
     * Return the title of the widget
     *
     * @return String
     *
     * @see Widget::getTitle()
     */
    public function getTitle()
    {
        return dgettext('tuleap-statistics', 'Project statistics');
    }

    /**
     * Show the content of the widget
     *
     * @return void
     *
     * @see Widget::getContent()
     */
    public function getContent()
    {
        $request  = HTTPRequest::instance();
        $group_id = $request->get('group_id');

        $disk_usage_dao = new Statistics_DiskUsageDao();
        $svn_log_dao    = new SVN_LogDao();
        $svn_retriever  = new SVNRetriever($disk_usage_dao);
        $svn_collector  = new SVNCollector($svn_log_dao, $svn_retriever);

        $disk_usage_manager = new Statistics_DiskUsageManager(
            $disk_usage_dao,
            $svn_collector,
            EventManager::instance()
        );

        $duHtml = new Statistics_DiskUsageHtml($disk_usage_manager);

        return $duHtml->getTotalProjectSize($group_id);
    }

    /**
     * Return the widget description
     *
     * @return String
     *
     * @see Widget::getDescription()
     */
    public function getDescription()
    {
        return dgettext('tuleap-statistics', 'Show project disk statistics');
    }

    /**
     * Return the widget category
     *
     * @return string
     *
     * @see Widget::getCategory()
     */
    public function getCategory()
    {
        return dgettext('tuleap-statistics', 'Project statistics');
    }
}
