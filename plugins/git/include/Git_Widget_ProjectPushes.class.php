<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
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

/**
 * Widget displaying last git pushes for the project
 */
class Git_Widget_ProjectPushes extends Widget
{

    public $pluginPath;

    /**
     * Constructor of the widget.
     *
     * @param String $pluginPath Path of plugin git
     *
     * @return Void
     */
    public function __construct($pluginPath)
    {
        $this->pluginPath = $pluginPath;
        // TODO: Make weeks number as a widget preferences stored by project.
        parent::__construct('plugin_git_project_pushes');
    }

    /**
     * Get the title of the widget.
     *
     * @return string
     */
    public function getTitle()
    {
        return dgettext('tuleap-git', 'Last Git pushes');
    }

    /**
     * Compute the content of the widget
     *
     * @return string html
     */
    public function getContent()
    {
        $request = HTTPRequest::instance();
        $groupId = $request->get('group_id');
        $plugin = PluginManager::instance()->getPluginByName('git');
        $weeksNumber = $request->get('weeks_number');
        if (empty($weeksNumber)) {
            $weeksNumber =  $plugin->getPluginInfo()->getPropVal('weeks_number');
        }
        $content = '<div style="text-align:center"><p>
                        <img src="' . $this->pluginPath . '/index.php?group_id=' . $groupId . '&weeks_number=' . $weeksNumber . '&action=view_last_git_pushes" title="' . dgettext('tuleap-git', 'Last Git pushes') . '" />
                    </div>';
        return $content;
    }

    /**
     * The category of the widget is scm
     *
     * @return string
     */
    public function getCategory()
    {
        return _('Source code management');
    }

    /**
     * Display widget's description
     *
     * @return String
     */
    public function getDescription()
    {
        return dgettext('tuleap-git', 'Display last Git pushes of the project.');
    }
}
