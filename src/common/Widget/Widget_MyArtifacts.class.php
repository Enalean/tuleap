<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

require_once __DIR__ . '/../../www/my/my_utils.php';

/**
* Widget_MyArtifacts
*
* Artifact assigned to or submitted by this person
*/
class Widget_MyArtifacts extends Widget // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    private bool|string $artifact_show;

    public function __construct()
    {
        parent::__construct('myartifacts');
        $this->artifact_show = user_get_preference('my_artifacts_show');
        if ($this->artifact_show === false) {
            $this->artifact_show = 'AS';
            user_set_preference('my_artifacts_show', $this->artifact_show);
        }
    }

    public function getTitle()
    {
        return $GLOBALS['Language']->getText('my_index', 'my_arts');
    }

    public function updatePreferences(Codendi_Request $request)
    {
        $request->valid(new Valid_String('cancel'));
        $vShow = new Valid_WhiteList('show', ['A', 'S', 'N', 'AS']);
        $vShow->required();
        if (! $request->exist('cancel')) {
            if ($request->valid($vShow)) {
                switch ($request->get('show')) {
                    case 'A':
                        $this->artifact_show = 'A';
                        break;
                    case 'S':
                        $this->artifact_show = 'S';
                        break;
                    case 'N':
                        $this->artifact_show = 'N';
                        break;
                    case 'AS':
                    default:
                        $this->artifact_show = 'AS';
                }
                user_set_preference('my_artifacts_show', $this->artifact_show);
            }
        }
        return true;
    }

    public function hasPreferences($widget_id)
    {
        return true;
    }

    public function getPreferences(int $widget_id, int $content_id): string
    {
        $purifier = Codendi_HTMLPurifier::instance();

        $selected_a  = $this->artifact_show === 'A'  ? 'selected="selected"' : '';
        $selected_s  = $this->artifact_show === 'S'  ? 'selected="selected"' : '';
        $selected_as = $this->artifact_show === 'AS' ? 'selected="selected"' : '';

        return '
            <div class="tlp-form-element">
                <label class="tlp-label" for="show-' . $widget_id . '">
                    ' . $purifier->purify($GLOBALS['Language']->getText('my_index', 'display_arts')) . '
                </label>
                <select type="text"
                    class="tlp-select"
                    id="show-' . $widget_id . '"
                    name="show"
                >
                    <option value="A" ' . $selected_a . '>
                        ' . $purifier->purify($GLOBALS['Language']->getText('my_index', 'a_info')) . '
                    </option>
                    <option value="S" ' . $selected_s . '>
                        ' . $purifier->purify($GLOBALS['Language']->getText('my_index', 's_info')) . '
                    </option>
                    <option value="AS" ' . $selected_as . '>
                        ' . $purifier->purify($GLOBALS['Language']->getText('my_index', 'as_info')) . '
                    </option>
                </select>
            </div>
            ';
    }

    public function isAjax()
    {
        return true;
    }

    public function getContent()
    {
        $html_my_artifacts = '<table style="width:100%">';
        if ($atf = new ArtifactTypeFactory(false)) {
            $my_artifacts = $atf->getMyArtifacts(UserManager::instance()->getCurrentUser()->getId(), $this->artifact_show);
            if (db_numrows($my_artifacts) > 0) {
                $html_my_artifacts .= $this->displayArtifacts($my_artifacts, 0);
            }
        } else {
            $html_my_artifacts = $GLOBALS['Language']->getText('my_index', 'err_artf');
        }
        $html_my_artifacts .= '<TR><TD COLSPAN="3">' . (($this->artifact_show == 'N' || db_numrows($my_artifacts) > 0) ? '&nbsp;' : $GLOBALS['Language']->getText('global', 'none')) . '</TD></TR>';
        $html_my_artifacts .= '</table>';
        $html_my_artifacts .= $this->getPriorityColorsKey();
        return $html_my_artifacts;
    }

    private function getPriorityColorsKey()
    {
        $html = '<P class="small"><B>' . $GLOBALS['Language']->getText('include_utils', 'prio_colors') . '</B><BR>
		<table border=0><tr>';

        for ($i = 1; $i < 10; $i++) {
            $html .= '
			<TD class="' . $GLOBALS['HTML']->getPriorityColor($i) . '">' . $i . '</TD>';
        }
        $html .= '</tr></table>';
        return $html;
    }

    private function displayArtifacts($list_trackers, $print_box_begin): string
    {
        $request = HTTPRequest::instance();

        $vItemId = new Valid_UInt('hide_item_id');
        $vItemId->required();
        if ($request->valid($vItemId)) {
            $hide_item_id = $request->get('hide_item_id');
        } else {
            $hide_item_id = null;
        }

        $vArtifact = new Valid_WhiteList('hide_artifact', [0, 1]);
        $vArtifact->required();
        if ($request->valid($vArtifact)) {
            $hide_artifact = $request->get('hide_artifact');
        } else {
            $hide_artifact = null;
        }

        $j                 = $print_box_begin;
        $html_my_artifacts = "";
        $html              = "";
        $html_hdr          = "";

        $aid_old      = 0;
        $atid_old     = 0;
        $group_id_old = 0;
        $count_aids   = 0;
        $group_name   = "";
        $tracker_name = "";

        $artifact_types = [];

        $pm       = ProjectManager::instance();
        $purifier = Codendi_HTMLPurifier::instance();
        while ($trackers_array = db_fetch_array($list_trackers)) {
            $atid     = $trackers_array['group_artifact_id'];
            $group_id = $trackers_array['group_id'];

            // {{{ check permissions
            //create group
            $group = $pm->getProject($group_id);
            if (! $group || ! is_object($group) || $group->isError()) {
                    exit_no_group();
            }
            //Create the ArtifactType object
            if (! isset($artifact_types[$group_id])) {
                $artifact_types[$group_id] = [];
            }
            if (! isset($artifact_types[$group_id][$atid])) {
                $artifact_types[$group_id][$atid]                                 = [];
                $artifact_types[$group_id][$atid]['at']                           = new ArtifactType($group, $atid);
                $artifact_types[$group_id][$atid]['user_can_view_at']             = $artifact_types[$group_id][$atid]['at']->userCanView();
                $artifact_types[$group_id][$atid]['user_can_view_summary_or_aid'] = null;
            }
            //Check if user can view artifact
            if ($artifact_types[$group_id][$atid]['user_can_view_at'] && $artifact_types[$group_id][$atid]['user_can_view_summary_or_aid'] !== false) {
                if (is_null($artifact_types[$group_id][$atid]['user_can_view_summary_or_aid'])) {
                    $at = $artifact_types[$group_id][$atid]['at'];
                    //Create ArtifactFieldFactory object
                    if (! isset($artifact_types[$group_id][$atid]['aff'])) {
                        $artifact_types[$group_id][$atid]['aff'] = new ArtifactFieldFactory($at);
                    }
                    $aff = $artifact_types[$group_id][$atid]['aff'];
                    //Retrieve artifact_id field
                    $field = $aff->getFieldFromName('artifact_id');
                    //Check if user can read it
                    $user_can_view_aid = $field->userCanRead($group_id, $atid);
                    //Retrieve percent_complete field
                    $field = $aff->getFieldFromName('percent_complete');
                    //Check if user can read it
                    $user_can_view_percent_complete = $field && $field->userCanRead($group_id, $atid);
                    //Retriebe summary field
                    $field = $aff->getFieldFromName('summary');
                    //Check if user can read it
                    $user_can_view_summary                                            = $field->userCanRead($group_id, $atid);
                    $artifact_types[$group_id][$atid]['user_can_view_summary_or_aid'] = $user_can_view_aid || $user_can_view_summary;
                }
                if ($artifact_types[$group_id][$atid]['user_can_view_summary_or_aid']) {
                    //work on the tracker of the last round if there was one
                    if ($atid != $atid_old && $count_aids != 0) {
                        [$hide_now, $count_diff, $hide_url] =
                            my_hide_url('artifact', $atid_old, $hide_item_id, $count_aids, $hide_artifact, $request->get('dashboard_id'));
                        $html_hdr                           = ($j ? '<tr class="boxitem"><td colspan="3">' : '') .
                        $hide_url . '<A HREF="/tracker/?group_id=' . $group_id_old . '&atid=' . $atid_old . '">' .
                        $group_name . " - " . $tracker_name . '</A>&nbsp;&nbsp;&nbsp;&nbsp;';
                        $count_new                          = max(0, $count_diff);

                        $html_hdr          .= my_item_count($count_aids, $count_new) . '</td></tr>';
                        $html_my_artifacts .= $html_hdr . $html;

                        $count_aids = 0;
                        $html       = '';
                        $j++;
                    }

                    if ($count_aids == 0) {
                      //have to call it to get at least the hide_now even if count_aids is false at this point
                        $hide_now = my_hide('artifact', $atid, $hide_item_id, $hide_artifact);
                    }

                    $group_name   = $trackers_array['group_name'];
                    $tracker_name = $trackers_array['name'];
                    $aid          = $trackers_array['artifact_id'];
                    $summary      = $trackers_array['summary'];
                    $atid_old     = $atid;
                    $group_id_old = $group_id;

                    // If user is assignee and submitter of an artifact, it will
                    // appears 2 times in the result set.
                    if ($aid != $aid_old) {
                        $count_aids++;
                    }

                    if (! $hide_now && $aid != $aid_old) {
                        // Form the 'Submitted by/Assigned to flag' for marking
                        $AS_flag = my_format_as_flag2($trackers_array['assignee'], $trackers_array['submitter']);

                        //get percent_complete if this field is used in the tracker
                        $percent_complete = '';
                        if ($user_can_view_percent_complete) {
                            $sql =
                                "SELECT afvl.value " .
                                "FROM artifact_field_value afv,artifact_field af, artifact_field_value_list afvl, artifact_field_usage afu " .
                                "WHERE af.field_id = afv.field_id AND af.field_name = 'percent_complete' " .
                                "AND afv.artifact_id = " . db_ei($aid) . " " .
                                "AND afvl.group_artifact_id = " . db_ei($atid) . " AND af.group_artifact_id = " . db_ei($atid) . " " .
                                "AND afu.group_artifact_id = " . db_ei($atid) . " AND afu.field_id = af.field_id AND afu.use_it = 1 " .
                                "AND afvl.field_id = af.field_id AND afvl.value_id = afv.valueInt";
                            $res = db_query($sql);
                            if (db_numrows($res) > 0) {
                                $percent_complete = '<TD class="small">' . db_result($res, 0, 'value') . '</TD>';
                            }
                        }
                        $html .= '
                            <TR class="' . get_priority_color($trackers_array['severity']) .
                            '"><TD class="small"><A HREF="/tracker/?func=detail&group_id=' .
                        $group_id . '&aid=' . $aid . '&atid=' . $atid .
                            '">' . $aid . '</A></TD>' .
                            '<TD class="small"' . ($percent_complete ? '>' : ' colspan="2">');
                        if ($user_can_view_summary) {
                            $html .= stripslashes($summary);
                        }
                        $html .= '&nbsp;' . $AS_flag . '</TD>' . $percent_complete . '</TR>';
                    }
                    $aid_old = $aid;
                }
            }
        }

        //work on the tracker of the last round if there was one
        if ($atid_old != 0 && $count_aids != 0) {
            [$hide_now, $count_diff, $hide_url] = my_hide_url('artifact', $atid_old, $hide_item_id, $count_aids, $hide_artifact, $request->get('dashboard_id'));
            $html_hdr                           = ($j ? '<tr class="boxitem"><td colspan="3">' : '') .
              $hide_url . '<A HREF="/tracker/?group_id=' . $group_id_old . '&atid=' . $atid_old . '">' .
              $purifier->purify($group_name) . " - " . $tracker_name . '</A>&nbsp;&nbsp;&nbsp;&nbsp;';
            $count_new                          = max(0, $count_diff);

            $html_hdr          .= my_item_count($count_aids, $count_new) . '</td></tr>';
            $html_my_artifacts .= $html_hdr . $html;
        }

        return $html_my_artifacts;
    }

    public function getAjaxUrl($owner_id, $owner_type, $dashboard_id)
    {
        $request  = HTTPRequest::instance();
        $ajax_url = parent::getAjaxUrl($owner_id, $owner_type, $dashboard_id);
        if ($request->exist('hide_item_id') || $request->exist('hide_artifact')) {
            $ajax_url .= '&hide_item_id=' . urlencode($request->get('hide_item_id')) .
                '&hide_artifact=' . urlencode($request->get('hide_artifact'));
        }

        return $ajax_url;
    }

    public function getCategory()
    {
        return _('Trackers');
    }

    public function getDescription()
    {
        return $GLOBALS['Language']->getText('widget_description_my_artifacts', 'description');
    }
}
