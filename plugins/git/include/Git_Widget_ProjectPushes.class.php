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

require_once('common/chart/Chart.class.php');
/**
 * TODO: Class comment
 */
class Git_Widget_ProjectPushes extends Widget {

    /**
     * Constructor of the widget.
     */
    public function __construct() {
        parent::__construct('plugin_git_project_pushes');
    }

    public function canBeUsedByProject($project) {
        //TODO
    }

    /**
     * Get the title of the widget.
     *
     * @return string
     */
    public function getTitle() {
        return $GLOBALS['Language']->getText('plugin_git', 'widget_project_pushes_title');
    }

    /**
     * Compute the content of the widget
     *
     * @return string html
     */
    public function getContent() {
        $content = '';
        return $content;
    }

    /**
     * The category of the widget is scm
     *
     * @return string
     */
    function getCategory() {
        return 'scm';
    }

    /**
     * Display widget's description
     *
     * @return String
     */
    function getDescription() {
        return $GLOBALS['Language']->getText('plugin_git', 'widget_project_pushes_description');
    }

    function isAjax() {
        //TODO
    }
}
?>
