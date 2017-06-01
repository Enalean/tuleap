<?php
/**
 * Copyright (c) Enalean, 2011 - 2017. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

require_once 'common/widget/Widget.class.php';
require_once 'common/user/UserManager.class.php';
require_once 'common/include/Toggler.class.php';


/**
 * Widget_MyArtifacts
 *
 * Artifact assigned to or submitted by this person
 */
class Tracker_Widget_MyArtifacts extends Widget {
    const ID        = 'plugin_tracker_myartifacts';
    const PREF_SHOW = 'plugin_tracker_myartifacts_show';

    protected $artifact_show;

    function __construct() {
        parent::__construct(self::ID);
        $this->artifact_show = user_get_preference(self::PREF_SHOW);
        if($this->artifact_show === false) {
            $this->artifact_show = 'AS';
            user_set_preference(self::PREF_SHOW, $this->artifact_show);
        }
    }

    function getTitle() {
        return $GLOBALS['Language']->getText('plugin_tracker_widget_myartifacts', 'my_arts') . ' [' . $GLOBALS['Language']->getText('plugin_tracker_widget_myartifacts', strtolower($this->artifact_show)) . ']';
    }

    function updatePreferences($request) {
        $request->valid(new Valid_String('cancel'));
        $vShow = new Valid_WhiteList('show', array('A', 'S', 'AS'));
        $vShow->required();
        if (!$request->exist('cancel')) {
            if ($request->valid($vShow)) {
                switch($request->get('show')) {
                    case 'A':
                        $this->artifact_show = 'A';
                        break;
                    case 'S':
                        $this->artifact_show = 'S';
                        break;
                    default:
                        $this->artifact_show = 'AS';
                }
                user_set_preference(self::PREF_SHOW, $this->artifact_show);
            }
        }
        return true;
    }

    function hasPreferences() {
        return true;
    }

    public function getPreferencesForBurningParrot($widget_id)
    {
        $purifier = Codendi_HTMLPurifier::instance();

        $selected_a  = $this->artifact_show === 'A'  ? 'selected="selected"' : '';
        $selected_s  = $this->artifact_show === 'S'  ? 'selected="selected"' : '';
        $selected_as = $this->artifact_show === 'AS' ? 'selected="selected"' : '';

        return '
            <div class="tlp-form-element">
                <label class="tlp-label" for="show-'. (int)$widget_id .'">
                    '. $purifier->purify($GLOBALS['Language']->getText('plugin_tracker_widget_myartifacts', 'display_arts')) .'
                </label>
                <select type="text"
                    class="tlp-select"
                    id="show-'. (int)$widget_id .'"
                    name="show"
                >
                    <option value="A" '. $selected_a .'>
                        '. $purifier->purify($GLOBALS['Language']->getText('plugin_tracker_widget_myartifacts', 'a_info')) .'
                    </option>
                    <option value="S" '. $selected_s .'>
                        '. $purifier->purify($GLOBALS['Language']->getText('plugin_tracker_widget_myartifacts', 's_info')) .'
                    </option>
                    <option value="AS" '. $selected_as .'>
                        '. $purifier->purify($GLOBALS['Language']->getText('plugin_tracker_widget_myartifacts', 'as_info')) .'
                    </option>
                </select>
            </div>
            ';
    }

    function getPreferences() {
        $prefs  = '';
        $prefs .= $GLOBALS['Language']->getText('plugin_tracker_widget_myartifacts', 'display_arts').' <select name="show">';
        $prefs .= '<option value="A"  '.($this->artifact_show === 'A'?'selected="selected"':'').'>'.$GLOBALS['Language']->getText('plugin_tracker_widget_myartifacts', 'a_info');
        $prefs .= '<option value="S"  '.($this->artifact_show === 'S'?'selected="selected"':'').'>'.$GLOBALS['Language']->getText('plugin_tracker_widget_myartifacts', 's_info');
        $prefs .= '<option value="AS" '.($this->artifact_show === 'AS'?'selected="selected"':'').'>'.$GLOBALS['Language']->getText('plugin_tracker_widget_myartifacts', 'as_info');
        $prefs .= '</select>';
        return $prefs;

    }

    function isAjax() {
        return true;
    }

    function getContent() {
        $html_my_artifacts = '';

        $taf = Tracker_ArtifactFactory::instance();
        $um = UserManager::instance();
        $user_id = $um->getCurrentUser()->getId();
        switch ($this->artifact_show) {
        case 'A':
            $my_artifacts = $taf->getUserOpenArtifactsAssignedTo($user_id);
            break;
        case 'S':
            $my_artifacts = $taf->getUserOpenArtifactsSubmittedBy($user_id);
            break;
        default:
            $my_artifacts = $taf->getUserOpenArtifactsSubmittedByOrAssignedTo($user_id);
            break;
        }

        if (count($my_artifacts) > 0) {
            $html_my_artifacts .= $this->_display_artifacts($my_artifacts);
        } else {
            $html_my_artifacts .= $GLOBALS['Language']->getText('plugin_tracker_widget_myartifacts', 'no_artifacts');
        }

        return $html_my_artifacts;
    }

    function _display_artifacts($artifacts) {
        $request = HTTPRequest::instance();
        $hp = Codendi_HTMLPurifier::instance();

        $html_my_artifacts = '';
        $html_current_tracker_header = '';
        $html_current_tracker_arts = '';
        $tracker_artifacts_counter = 0;

        $tracker = false;
        foreach ($artifacts as $tracker_id => $tracker_and_its_artifacts) {
            if (count($tracker_and_its_artifacts['artifacts'])) {
                $tracker = $tracker_and_its_artifacts['tracker'];

                //header (project name - tracker name)
                $div_id              = 'plugin_tracker_my_artifacts_tracker_' . $tracker->getId();
                $classname           = Toggler::getClassname($div_id);
                $group_id            = $tracker->getGroupId();
                $project             = ProjectManager::instance()->getProject($group_id);
                $project_and_tracker = $project->getPublicName() . ' - ' . $tracker->getName();

                $html_my_artifacts .= '<div>';
                $html_my_artifacts .= '<div class="' . $classname . '" id="' . $div_id . '">';
                $html_my_artifacts .= '<a href="/plugins/tracker/?tracker=' . $tracker->getId() . '">';
                $html_my_artifacts .= '<strong>' . $hp->purify($project_and_tracker, CODENDI_PURIFIER_CONVERT_HTML) . '</strong>';
                $html_my_artifacts .= '</a>';
                $html_my_artifacts .= ' [' . count($tracker_and_its_artifacts['artifacts']) . ']';
                $html_my_artifacts .= ' </div>';
                $html_my_artifacts .= '<ul class="plugin_tracker_my_artifacts_list">';
                foreach ($tracker_and_its_artifacts['artifacts'] as $artifact_and_its_title) {
                    // Display artifact
                    $html_my_artifacts .=  '<li>';
                    $html_my_artifacts .=  $artifact_and_its_title['artifact']->fetchWidget($tracker->getItemName(), $artifact_and_its_title['title']);
                    $html_my_artifacts .=  '</li>';
                }
                $html_my_artifacts .= '</ul>';
                $html_my_artifacts .= '</div>';
            }
        }
        return $html_my_artifacts;
    }

    function getAjaxUrl($owner_id, $owner_type) {
        $request = HTTPRequest::instance();
        $ajax_url = parent::getAjaxUrl($owner_id, $owner_type);
        if ($request->exist('hide_item_id') || $request->exist('hide_artifact')) {
            $ajax_url .= '&hide_item_id=' . $request->get('hide_item_id') . '&hide_artifact=' . $request->get('hide_artifact');
        }
        return $ajax_url;
    }

    function getCategory() {
        return 'trackers';
    }

    function getDescription() {
        return $GLOBALS['Language']->getText('plugin_tracker_widget_myartifacts','description');
    }
}
?>
