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

require_once 'common/widget/WidgetLayoutManager.class.php';
require_once 'Tracker_Widget_Renderer.class.php';

/**
 * Widget_MyTrackerRenderer
 * 
 * Personal tracker renderer
 */
class Tracker_Widget_ProjectRenderer extends Tracker_Widget_Renderer {
    const ID = 'plugin_tracker_projectrenderer';

    function __construct() {
        parent::__construct(self::ID, HTTPRequest::instance()->get('group_id'), WidgetLayoutManager::OWNER_TYPE_GROUP);
    }
    
    function canBeUsedByProject($project) {
        return true;
    }
    
    function display($layout_id, $column_id, $readonly, $is_minimized, $display_preferences, $owner_id, $owner_type) {
        $arrf = Tracker_Report_RendererFactory::instance();
        $store_in_session = false;
        if ($renderer = $arrf->getReportRendererById($this->renderer_id, null, $store_in_session)) {
            parent::display($layout_id, $column_id, $readonly, $is_minimized, $display_preferences, $owner_id, $owner_type);
        }
    }
}
?>
