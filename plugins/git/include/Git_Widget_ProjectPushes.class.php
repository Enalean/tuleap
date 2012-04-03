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
class Git_Widget_ProjectPushes extends Widget {

    //The default duration is 3 months back
    public $duration;

    /**
     * Constructor of the widget.
     *
     * @return Void
     */
    public function __construct() {
        parent::__construct('plugin_git_project_pushes');

        $this->duration = user_get_preference('plugin_git_project_pushes_duration');
        if (empty($this->duration)) {
            $this->duration = 12;
        }
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
        $request  = HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $request  = HTTPRequest::instance();
        $content  = '<div style="text-align:center"><p>';
        $graph    = '<img src="/plugins/git/project_last_git_pushes_graph.php?group_id='.$group_id.'&duration='.$this->duration.'" title="'.$GLOBALS['Language']->getText('plugin_git', 'widget_project_pushes_title').'" />';
        $content .= $graph.'</div>';
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

    /**
     * Update preferences
     *
     * @param Array $request HTTP request
     *
     * @return Boolean
     */
    function updatePreferences($request) {
        $request->valid(new Valid_String('cancel'));
        $vWeeks   = new Valid_UInt('plugin_git_project_pushes_duration');
        $vWeeks->required();
        if (!$request->exist('cancel')) {            
            if ($request->valid($vWeeks)) {
                $this->duration = $request->get('plugin_git_project_pushes_duration');
            } else {
                $this->duration = 12;
            }
            user_set_preference('plugin_git_project_pushes_duration', $this->duration);
        }
        return true;
    }

    /**
     * Widget has preferences
     *
     * @return Boolean
     */
    function hasPreferences() {
        return true;
    }

    /**
     * Display preferences form
     *
     * @return String
     */
    function getPreferences() {
        return "<table>
                    <tr>
                        <td>".$GLOBALS['Language']->getText('plugin_git', 'widget_project_pushes_duration')."</td>
                        <td><input name='plugin_git_project_pushes_duration' value='".$this->duration."'/></td>
                        <td>".$GLOBALS['Language']->getText('plugin_git', 'widget_project_pushes_group_by')."</td>
                    </tr>
                </table>";
        
    }
}

?>
