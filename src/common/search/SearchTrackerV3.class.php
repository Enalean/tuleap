<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Search_SearchTrackerV3 {
    const NAME = 'tracker';

    public function search($group_id, $words, $crit, $offset, $atid) {
        include_once('www/tracker/include/ArtifactTypeHtml.class.php');
        include_once('www/tracker/include/ArtifactHtml.class.php');
        //
        //      get the Group object
        //
        $pm = ProjectManager::instance();
        $group = $pm->getProject($group_id);
        if (!$group || !is_object($group) || $group->isError()) {
            exit_no_group();
        }
        //
        //      Create the ArtifactType object
        //
        $ath = new ArtifactTypeHtml($group, $atid);
        if (!$ath || !is_object($ath)) {
            exit_error($GLOBALS['Language']->getText('global', 'error'), $GLOBALS['Language']->getText('global', 'error'));
        }
        if ($ath->isError()) {
            exit_error($GLOBALS['Language']->getText('global', 'error'), $ath->getErrorMessage());
        }
        // Check if this tracker is valid (not deleted)
        if (!$ath->isValid()) {
            exit_error($GLOBALS['Language']->getText('global', 'error'), $GLOBALS['Language']->getText('global', 'error'));
        }

        // Create field factory
        $art_field_fact = new ArtifactFieldFactory($ath);

        $params = array('title' => $group->getPublicName() . ': \'' . $ath->getName() . '\' ' . $GLOBALS['Language']->getText('tracker_browse', 'search_report'),
            'titlevals' => array($ath->getName()),
            'pagename' => 'tracker_browse',
            'atid' => $ath->getID(),
            'sectionvals' => array($group->getPublicName()),
            'pv' => 0,
            'help' => 'ArtifactBrowsing.html');

        //$ath->header($params);
        echo '<div id="tracker_toolbar_clear"></div>';

        $array = explode(" ", $words);
        $words1 = implode($array, "%' $crit artifact.details LIKE '%");
        $words2 = implode($array, "%' $crit artifact.summary LIKE '%");
        $words3 = implode($array, "%' $crit artifact_history.new_value LIKE '%");

        $sql = "SELECT SQL_CALC_FOUND_ROWS artifact.artifact_id,
                   artifact.summary,
                   artifact.open_date,
                   user.user_name
           FROM artifact INNER JOIN user ON user.user_id=artifact.submitted_by
              LEFT JOIN artifact_history ON artifact_history.artifact_id=artifact.artifact_id
              LEFT JOIN permissions ON (permissions.object_id = CAST(artifact.artifact_id AS CHAR) AND permissions.permission_type = 'TRACKER_ARTIFACT_ACCESS')
           WHERE artifact.group_artifact_id='" . db_ei($atid) . "'
             AND (
                   artifact.use_artifact_permissions = 0
                   OR
                   (
                       permissions.ugroup_id IN (" . implode(',', UserManager::instance()->getCurrentUser()->getUgroups($group_id, $atid)) . ")
                   )
             )
             AND (
                   (artifact.details LIKE '%" . db_es($words1) . "%')
                   OR
                   (artifact.summary LIKE '%" . db_es($words2) . "%')
                   OR
                   (artifact_history.field_name='comment' AND (artifact_history.new_value LIKE '%" . db_es($words3) . "%'))
             )
           GROUP BY open_date DESC
           LIMIT " . db_ei($offset) . ", 25";
        $result = db_query($sql);
        $rows_returned = db_result(db_query('SELECT FOUND_ROWS() as nb'), 0, 'nb');
        if (!$result || $rows_returned < 1) {
            $no_rows = 1;
            echo '<H2>' . $GLOBALS['Language']->getText('search_index', 'no_match_found', htmlentities(stripslashes($words), ENT_QUOTES, 'UTF-8')) . '</H2>';
            echo db_error();
        } else {

            echo '<H3>' . $GLOBALS['Language']->getText('search_index', 'search_res', array(htmlentities(stripslashes($words), ENT_QUOTES, 'UTF-8'), $rows_returned)) . "</H3><P>\n";

            $title_arr = array();

            $summary_field = $art_field_fact->getFieldFromName("summary");
            if ($summary_field->userCanRead($group_id, $atid))
                $title_arr[] = $GLOBALS['Language']->getText('search_index', 'artifact_summary');
            $submitted_field = $art_field_fact->getFieldFromName("submitted_by");
            if ($submitted_field->userCanRead($group_id, $atid))
                $title_arr[] = $GLOBALS['Language']->getText('search_index', 'submitted_by');
            $date_field = $art_field_fact->getFieldFromName("open_date");
            if ($date_field->userCanRead($group_id, $atid))
                $title_arr[] = $GLOBALS['Language']->getText('search_index', 'date');
            $status_field = $art_field_fact->getFieldFromName("status_id");
            if ($status_field->userCanRead($group_id, $atid))
                $title_arr[] = $GLOBALS['Language']->getText('global', 'status');

            echo html_build_list_table_top($title_arr);

            echo "\n";


            $art_displayed = 0;
            $rows = 0;
            while ($arr = db_fetch_array($result)) {
                $rows++;
                $curArtifact = new Artifact($ath, $arr['artifact_id']);
                if ($curArtifact->isStatusClosed($curArtifact->getStatusID())) {
                    $status = $GLOBALS['Language']->getText('global', 'closed');
                } else {
                    $status = $GLOBALS['Language']->getText('global', 'open');
                }
                // Only display artifacts that the user is allowed to see
                if ($curArtifact->userCanView(user_getid())) {
                    print "\n<TR class=\"" . html_get_alt_row_color($art_displayed) . "\">";
                    if ($summary_field->userCanRead($group_id, $atid))
                        print "<TD><A HREF=\"/tracker/?group_id=$group_id&func=detail&atid=$atid&aid="
                                . $arr['artifact_id'] . "\"><IMG SRC=\"" . util_get_image_theme('msg.png') . "\" BORDER=0 HEIGHT=12 WIDTH=10> "
                                . $arr['summary'] . "</A></TD>";
                    if ($submitted_field->userCanRead($group_id, $atid))
                        print "<TD>" . $arr['user_name'] . "</TD>";
                    if ($date_field->userCanRead($group_id, $atid))
                        print "<TD>" . format_date($GLOBALS['Language']->getText('system', 'datefmt'), $arr['open_date']) . "</TD>";
                    if ($status_field->userCanRead($group_id, $atid))
                        print "<TD>" . $status . "</TD>";
                    print "</TR>";
                    $art_displayed++;
                    if ($art_displayed > 24) {
                        break;
                    } // Only display 25 results.
                }
            }
            echo "</TABLE>\n";
        }
    }
}
