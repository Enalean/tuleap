<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All rights reserved
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
require_once 'common/plugin/Plugin.class.php';

class tracker_date_reminderPlugin extends Plugin {

    function __construct($id) {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
    }

    function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'TrackerDateReminderPluginInfo')) {
            include_once('TrackerDateReminderPluginInfo.class.php');
            $this->pluginInfo = new TrackerDateReminderPluginInfo($this);
        }
        return $this->pluginInfo;
    }

}

?>
