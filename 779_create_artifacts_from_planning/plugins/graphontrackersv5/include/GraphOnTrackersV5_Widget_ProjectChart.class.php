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

require_once('GraphOnTrackersV5_Widget_Chart.class.php');

/**
* GraphOnTrackersV5_Widget_Chart
* 
* My Tracker Chart
*/
class GraphOnTrackersV5_Widget_ProjectChart extends GraphOnTrackersV5_Widget_Chart {
    function GraphOnTrackersV5_Widget_ProjectChart() {
        $this->GraphOnTrackersV5_Widget_Chart('project_plugin_graphontrackersv5_chart', 
                                            HTTPRequest::instance()->get('group_id'), 
                                            WidgetLayoutManager::OWNER_TYPE_GROUP);
    }
    function canBeUsedByProject(&$project) {
        return true;
    }
}
?>
