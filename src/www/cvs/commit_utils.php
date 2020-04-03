<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
 * SourceForge: Breaking Down the Barriers to Open Source Development
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

/*

    Commits Manager
    By Thierry Jacquin, Nov 2003

*/

function uniformat_date($format, $date)
{
    if (preg_match("/([0-9]{4})-?([0-9]{2})-?([0-9]{2}) ?([0-9]{2}):?([0-9]{2}):?([0-9]{2})/", $date, $gp)) {
        list(,$y, $m, $d, $h, $min, $s) = $gp;
        $time = mktime($h, $min, $s, $m, $d, $y);
        $date = date($format, $time);
    }
    return $date;
}

function commits_header($params)
{
    \Tuleap\Project\ServiceInstrumentation::increment('cvs');

    $params['toptab'] = 'cvs';
    $group_id        = $params['group'];

    $additional_params = array();
    if (isset($params['body_class'])) {
        $additional_params = array(
            'body_class' => $params['body_class']
        );
    }

    $pm = ProjectManager::instance();
    $project = $pm->getProject($group_id);

    $service = $project->getService(Service::CVS);
    if ($service === null) {
        exit_error(
            $GLOBALS['Language']->getText('global', 'error'),
            $GLOBALS['Language']->getText('cvs_commit_utils', 'error_off')
        );
        return;
    }

    $toolbar = array();
    $toolbar[] = array('title' => $GLOBALS['Language']->getText('svn_utils', 'svn_info'),
        'url'   => '/cvs/?func=info&group_id=' . $group_id);

    if ($project->isPublic() || user_isloggedin()) {
        $toolbar[] = array('title' => $GLOBALS['Language']->getText('cvs_commit_utils', 'menu_browse'),
            'url'   => '/cvs/viewvc.php/?root=' . $project->getUnixName(false) . '&roottype=cvs');
    }

    if (user_isloggedin()) {
        $toolbar[] = array('title' => $GLOBALS['Language']->getText('cvs_commit_utils', 'menu_my'),
            'url'   => '/cvs/?func=browse&group_id=' . $group_id . '&set=my');
        $toolbar[] = array('title' => $GLOBALS['Language']->getText('cvs_commit_utils', 'menu_query'),
            'url'   => '/cvs/?func=browse&group_id=' . $group_id);
    }
    if (user_ismember($group_id, 'A')) {
        $toolbar[] = array('title' => $GLOBALS['Language']->getText('cvs_commit_utils', 'menu_admin'),
            'url'   => '/cvs/?func=admin&group_id=' . $group_id);
    }
    if (!isset($params['help'])) {
        $params['help'] = "cvs.html";
    }
    $toolbar[] = array('title' => $GLOBALS['Language']->getText('global', 'help'),
        'url'   => 'javascript:help_window(\'/doc/' . UserManager::instance()->getCurrentUser()->getShortLocale() . '/user-guide/code-versioning/' . $params['help'] . '\');');

    $service->displayHeader(
        $params['title'],
        array(
            array(
                'title' => $GLOBALS['Language']->getText('project_admin_editservice', 'service_cvs_lbl_key'),
                'url' => '/cvs/?group_id=' . $group_id
            )
        ),
        $toolbar,
        $additional_params
    );
}

function commits_footer($params)
{
    site_project_footer($params);
}

function commits_branches_box($group_id, $name = 'branch', $checked = 'xzxz', $text_100 = 'None')
{
    if (!$group_id) {
        return $GLOBALS['Language']->getText('cvs_commit_utils', 'error_nogid');
    } else {
        $sql = "SELECT unix_group_name from groups where group_id=" . db_ei($group_id);

        $result         = db_query($sql);
        $projectname    = db_result($result, 0, 'unix_group_name');
        $cvs_repository = db_es('/cvsroot/' . $projectname);
     /*
      List of possible commits_categories set up for the project
     */
        $sql = "select distinct cvs_branches.* FROM cvs_branches, cvs_checkins, cvs_repositories  where " .
        "cvs_checkins.repositoryid=cvs_repositories.id AND cvs_repositories.repository='$cvs_repository' " .
        "AND cvs_checkins.branchid=cvs_branches.id";
        $result = db_query($sql);

        return html_build_select_box($result, $name, $checked, true, $text_100);
    }
}

function commits_data_get_technicians($projectname)
{
    // Get list of all people who once committed something in the CVS
    // including those who may have been removed from the project since then.
    $cvs_repository = db_es('%/' . $projectname);
    $sql = "SELECT DISTINCT user.user_name, user.user_name " .
        "FROM cvs_checkins, cvs_repositories, user " .
        "WHERE (cvs_repositories.repository like '$cvs_repository') AND (cvs_repositories.id = cvs_checkins.repositoryid) AND (cvs_checkins.whoid=user.user_id) " .
        "ORDER BY user.user_name ASC";
    return db_query($sql);
}

function commits_technician_box($projectname, $name = '_commiter', $checked = 'xzxz', $text_100 = 'None')
{
    if (!$projectname) {
        return $GLOBALS['Language']->getText('cvs_commit_utils', 'error_nogid');
    } else {
        $result = commits_data_get_technicians($projectname);
        if (!in_array($checked, util_result_column_to_array($result))) {
            // Selected 'my commits' but never commited
            $checked = 'xzxz';
        }
        return html_build_select_box($result, $name, $checked, true, $text_100);
    }
}

function commits_tags_box($group_id, $name = '_tag', $checked = 'xzxz', $text_100 = 'None')
{
    $sql = "SELECT unix_group_name from groups where group_id=" . db_ei($group_id);

    $result = db_query($sql);
    $projectname = db_result($result, 0, 'unix_group_name');

    $cvs_repository = db_es('/cvsroot/' . $projectname);
    $sql = "select distinct stickytag, stickytag from cvs_checkins, cvs_repositories where cvs_checkins.repositoryid=cvs_repositories.id AND cvs_repositories.repository='$cvs_repository'";
    $result = db_query($sql);
    return html_build_select_box($result, $name, $checked, true, $text_100);
}

function show_commitslist(
    $group_id,
    $result,
    $offset,
    $total_rows,
    $set = 'any',
    $commiter = '100',
    $tag = '100',
    $branch = '100',
    $srch = '',
    $chunksz = 15,
    $morder = '',
    $msort = 0
) {
    /*
        Accepts a result set from the commits table. Should include all columns from
        the table, and it should be joined to USER to get the user_name.
    */
    $url = '?func=browse&group_id=' . $group_id . '&set=' . $set . '&msort=' . $msort;

    if ($set == 'custom') {
        $url .= $pref_stg;
    }

    $url_nomorder = $url;
    $url .= "&morder=$morder";

    if ($morder != '') {
        $orderstr = ' ' . $GLOBALS['Language']->getText('cvs_commit_utils', 'sorted_by') . ' ' . commit_criteria_list_to_text($morder, $url_nomorder);
    } else {
        $orderstr = '';
    }
    echo '<A name="results"></A>';
    echo '<h3>' . $total_rows . ' ' . $GLOBALS['Language']->getText('cvs_commit_utils', 'matching') . ($total_rows > 1 ? 's' : '') . $orderstr . '</h3>';

    $nav_bar = '<table width= "100%"><tr>';
    $nav_bar .= '<td width="20%" align ="left">';

    if ($msort) {
        $url_alternate_sort = str_replace('msort=1', 'msort=0', $url) .
        '&order=#results';
        $text = $GLOBALS['Language']->getText('cvs_commit_utils', 'deactivate');
    } else {
        $url_alternate_sort = str_replace('msort=0', 'msort=1', $url) .
        '&order=#results';
        $text = $GLOBALS['Language']->getText('cvs_commit_utils', 'activate');
    }

    echo '<P>' . $GLOBALS['Language']->getText('cvs_commit_utils', 'sort_msg', array($url . '&order=#results',$url_alternate_sort,$text));

    // If all bugs on screen so no prev/begin pointer at all
    if ($total_rows > $chunksz) {
        if ($offset > 0) {
            $nav_bar .=
            '<A HREF="' . $url . '&offset=0#results"><B>&lt;&lt;  ' . $GLOBALS['Language']->getText('global', 'begin') . '</B></A>' .
            '&nbsp;&nbsp;&nbsp;&nbsp;' .
            '<A HREF="' . $url . '&offset=' . ($offset - $chunksz) .
            '#results"><B>< ' . $GLOBALS['Language']->getText('global', 'prev') . ' ' . $chunksz . '</B></A></td>';
        } else {
            $nav_bar .=
            '<span class="disable">&lt;&lt; ' . $GLOBALS['Language']->getText('global', 'begin') . '&nbsp;&nbsp;&lt; ' . $GLOBALS['Language']->getText('global', 'prev') . ' ' . $chunksz . '</span>';
        }
    }

    $nav_bar .= '</td>';

    $offset_last = min($offset + $chunksz - 1, $total_rows - 1);
    $nav_bar .= '<td width= "60% " align = "center" class="small">Items ' . ($offset + 1) . ' - ' .
    ($offset_last + 1) . "</td>\n";

    $nav_bar .= '<td width="20%" align ="right">';

    // If all bugs on screen, no next/end pointer at all
    if ($total_rows > $chunksz) {
        if (($offset + $chunksz) < $total_rows) {
            $offset_end = ($total_rows - ($total_rows % $chunksz));
            if ($offset_end == $total_rows) {
                $offset_end -= $chunksz;
            }

            $nav_bar .=
            '<A HREF="' . $url . '&offset=' . ($offset + $chunksz) .
            '#results" class="small"><B>' . $GLOBALS['Language']->getText('global', 'next') . ' ' . $chunksz . ' &gt;</B></A>' .
            '&nbsp;&nbsp;&nbsp;&nbsp;' .
            '<A HREF="' . $url . '&offset=' . ($offset_end) .
            '#results" class="small"><B>' . $GLOBALS['Language']->getText('global', 'end') . ' &gt;&gt;</B></A></td>';
        } else {
            $nav_bar .=
            '<span class="disable">' . $GLOBALS['Language']->getText('global', 'next') . ' ' . $chunksz .
            ' &gt;&nbsp;&nbsp;' . $GLOBALS['Language']->getText('global', 'end') . ' &gt;&gt;</span>';
        }
    }
    $nav_bar .= '</td>';
    $nav_bar .= "</tr></table>\n";

    echo $nav_bar;

    $filter_str = '';
    if ($commiter != '100') {
        $filter_str = "&commiter='$commiter'";
    }
    if ($tag != '100') {
        $filter_str = $filter_str . "&tag='$tag'";
    }
    if ($branch != '100') {
        $filter_str = $filter_str . "&branch='$branch'";
    }
    if ($srch != '') {
        $filter_str = $filter_str . "&srch='$srch'";
    }

    $rows = db_numrows($result);
    $url .= "&order=";
    $title_arr = array();
    $title_arr[] = $GLOBALS['Language']->getText('cvs_commit_utils', 'id');
    $title_arr[] = $GLOBALS['Language']->getText('cvs_commit_utils', 'description');
    $title_arr[] = $GLOBALS['Language']->getText('cvs_commit_utils', 'date');
    $title_arr[] = $GLOBALS['Language']->getText('cvs_commit_utils', 'who');

    $links_arr = array();
    $links_arr[] = $url . 'id#results';
    $links_arr[] = $url . 'description#results';
    $links_arr[] = $url . 'f_when#results';
    $links_arr[] = $url . 'user_name#results';

    $url_nomorder = $url;
    $url .= "&morder=$morder";

    echo html_build_list_table_top($title_arr, $links_arr);

    for ($i = 0; $i < $rows; $i++) {
        $filename = db_result($result, $i, 'filename');
        if (!$filename) {
            $filename = '';
        }
        // $commits_url = '<A HREF="/commits/download.php/Commits'.$commit_id.'.txt?commit_id='.$id.'">'.$filename.'</a>';

        // if (commits.id == '0', will fetch on desc id, else on commit_id
        $id_str = db_result($result, $i, 'id');
        $id_link = '&commit_id=' . $id_str;
        $id_sublink = '';
        if ($id_str == '0') {
            $id_str = ' ? ';
            $id_link = "&checkin_id=" . db_result($result, $i, 'did') .
            "&when=" . db_result($result, $i, 'c_when') . $filter_str;
        }

        echo '
			<TR class="' . util_get_alt_row_color($i) . '">' .
        '<TD class="small"><b><A HREF="?func=detailcommit&group_id=' . $group_id . $id_link . $filter_str . '">' . $id_str .
        '</b></A></TD>' .
        '<TD class="small">' . Codendi_HTMLPurifier::instance()->purify(implode('<br>', preg_split("/\n/D", db_result($result, $i, 'description'))), CODENDI_PURIFIER_BASIC_NOBR, $group_id) . $id_sublink . '</TD>' .
      // '<TD class="small">'.$commits_url.'</TD>'.
        '<TD class="small">' . uniformat_date($GLOBALS['Language']->getText('system', 'datefmt'), db_result($result, $i, 'c_when')) . '</TD>' .
      // '<TD class="small">'.util_user_link(db_result($result,$i,'assigned_to_user')).'</TD>'.
        '<TD class="small">' . util_user_link(db_result($result, $i, 'who')) . '</TD></TR>';
    }

    /*
        Show extra rows for <-- Prev / Next -->
    */
    echo '</TD></TR></TABLE>';
    echo $nav_bar;
}

function makeCvsLink($group_id, $filename, $text, $rev = '', $displayfunc = '')
{
    $res_grp = db_query("SELECT * FROM groups WHERE group_id=" . db_ei($group_id));

    $view_str = $displayfunc;
    if ($rev) {
        $view_str .= '&revision=' . $rev;
    }

    $row_grp = db_fetch_array($res_grp);
    $group_name = $row_grp['unix_group_name'];
    return '<A HREF="/cvs/viewvc.php/' . $filename . '?root=' . $group_name . '&roottype=cvs' . $view_str . '"><B>' . $text . "</B></A>";
}

function makeCvsDirLink($group_id, $filename, $text, $dir = '')
{
    $res_grp = db_query("SELECT * FROM groups WHERE group_id=" . db_ei($group_id));
    $row_grp = db_fetch_array($res_grp);
    $group_name = $row_grp['unix_group_name'];
    return '<A HREF="/cvs/viewvc.php/' . $dir . '?root=' . $group_name . '&roottype=cvs"><B>' . $text . '</B></A>';
}

// Check is a sort criteria is already in the list of comma
// separated criterias. If so invert the sort order, if not then
// simply add it
function commit_add_sort_criteria($criteria_list, $order, $msort)
{
    $found = false;
    if ($criteria_list) {
        $arr = explode(',', $criteria_list);
        $i = 0;
        foreach ($arr as $attr) {
            preg_match("/\s*([^<>]*)([<>]*)/", $attr, $match);
            list(,$mattr,$mdir) = $match;
            //echo "<br><pre>DBG \$mattr=$mattr,\$mdir=$mdir</pre>";
            if ($mattr == $order) {
                if (($mdir == '>') || (!isset($mdir))) {
                    $arr[$i] = $order . '<';
                } else {
                    $arr[$i] = $order . '>';
                }
                $found = true;
            }
            $i++;
        }
    }

    if (! $found) {
        if (! $msort) {
            unset($arr);
        }
        $arr[] = $order . '<';
    }

    return(join(',', $arr));
}

// Transform criteria list to SQL query (+ means ascending
// - is descending)
function commit_criteria_list_to_query($criteria_list)
{
    $criteria_list = str_replace('>', ' ASC', $criteria_list);
    $criteria_list = str_replace('<', ' DESC', $criteria_list);

    return $criteria_list;
}

// Transform criteria list to readable text statement
// $url must not contain the morder parameter
function commit_criteria_list_to_text($criteria_list, $url)
{
    if ($criteria_list) {
        $arr    = explode(',', $criteria_list);
        $morder = '';

        foreach ($arr as $crit) {
            $morder .= ($morder ? "," . $crit : $crit);
            $attr = str_replace('>', '', $crit);
            $attr = str_replace('<', '', $attr);

            $arr_text[] = '<a href="' . $url . '&morder=' . $morder . '#results">' .
            commit_field_get_label($attr) . '</a><img src="' . util_get_dir_image_theme() .
            ((substr($crit, -1) == '<') ? 'dn' : 'up') .
            '_arrow.png" border="0">';
        }
    }

    return join(' > ', $arr_text);
}

function commit_field_get_label($sortField)
{
    if ($sortField == "id") {
        return $GLOBALS['Language']->getText('cvs_commit_utils', 'id');
    }
    if ($sortField == "f_when") {
        return $GLOBALS['Language']->getText('cvs_commit_utils', 'date');
    }
    return $sortField;
}


function show_commit_details($group_id, $commit_id, $result)
{
    /*
        Accepts a result set from the commits table. Should include all columns from
        the table, and it should be joined to USER to get the user_name.
    */

    $rows = db_numrows($result);
    $url = "/cvs/?func=detailcommit&commit_id=$commit_id&group_id=$group_id&order=";
    $purifier = Codendi_HTMLPurifier::instance();
    $list_log = '<pre>' . $purifier->purify(util_line_wrap(db_result($result, 0, 'description')), CODENDI_PURIFIER_BASIC_NOBR, $group_id) . '</pre>';

    if ($commit_id) {
        $hdr = '[' . $GLOBALS['Language']->getText('cvs_commit_utils', 'commit') . $commit_id . '] - ';
    } else {
        $hdr = $GLOBALS['Language']->getText('cvs_commit_utils', 'checkin') . ' ';
    }
    echo '<h2>' . $hdr . uniformat_date($GLOBALS['Language']->getText('system', 'datefmt'), db_result($result, 0, 'c_when')) . '</h2></h2>';
    echo '<table WIDTH="100%" BORDER="0" CELLSPACING="1" CELLPADDING="2"><tr class="' . util_get_alt_row_color(0) . '"><td>' . $list_log . '</td></tr></table>';

    $crossref_fact = new CrossReferenceFactory($commit_id, ReferenceManager::REFERENCE_NATURE_CVSCOMMIT, $group_id);
    $crossref_fact->fetchDatas();
    if ($crossref_fact->getNbReferences() > 0) {
        echo '<h3> ' . $GLOBALS['Language']->getText('cross_ref_fact_include', 'references') . '</h3>';
        $crossref_fact->DisplayCrossRefs();
    }

    echo '<h3>' . $GLOBALS['Language']->getText('cvs_commit_utils', 'impacted_file') . '</h3>';
    $title_arr = array();
    $title_arr[] = $GLOBALS['Language']->getText('cvs_commit_utils', 'file');
    $title_arr[] = $GLOBALS['Language']->getText('cvs_commit_utils', 'rev');
    $title_arr[] = $GLOBALS['Language']->getText('cvs_commit_utils', 'branch');
    $title_arr[] = $GLOBALS['Language']->getText('cvs_commit_utils', 'type');
    $title_arr[] = $GLOBALS['Language']->getText('cvs_commit_utils', 'added_line');
    $title_arr[] = $GLOBALS['Language']->getText('cvs_commit_utils', 'removed_line');

    $links_arr = array();
    $links_arr[] = $url . 'filename';
    $links_arr[] = $url . 'revision';
    $links_arr[] = $url . 'branch';
    $links_arr[] = $url . 'type';
    $links_arr[] = $url . 'addedlines';
    $links_arr[] = $url . 'removedlines';

    echo html_build_list_table_top($title_arr, $links_arr);

    for ($i = 0; $i < $rows; $i++) {
        $commit_id = db_result($result, $i, 'id');
        $type = db_result($result, $i, 'type');
        $added = db_result($result, $i, 'addedlines');
        $removed = db_result($result, $i, 'removedlines');
        $revision = db_result($result, $i, 'revision');
        $filename = db_result($result, $i, 'dir') . '/' . db_result($result, $i, 'file');

        if ('remove' === strtolower($type)) {
            $type_text = $GLOBALS['Language']->getText('cvs_commit_utils', 'remove');
        } elseif ('add' === strtolower($type)) {
            $type_text = $GLOBALS['Language']->getText('cvs_commit_utils', 'add');
        } else {
            $type_text = $GLOBALS['Language']->getText('cvs_commit_utils', 'change');
        }

        if (
            ($type == "Change") &&
            ($added == 999) &&
            ($removed == 999)
        ) { // the default values
          // back to rcs to complete
            $repo = db_result($result, $i, 'repository');
            $command = "rlog -r" . escapeshellarg($revision) . " " . escapeshellarg($repo . "/" . $filename);
            $output = array();
            exec($command, $output, $ret);
            $added = 0;
            $removed = 0;
            $l = 0;
            while ($l < count($output)) { // parse the rlog result till getting "state: Exp;  lines:"
                $line = $output[$l];
                $l++;
                if (preg_match('/state: +Exp; +lines: +\+([0-9]*) +\-([0-9]*)$/', $line, $na)) {
                       $added         = db_ei($na[1]);
                       $removed       = db_ei($na[2]);
                       $repository_id = db_ei(db_result($result, $i, 'repositoryid'));
                       $dir_id        = db_ei(db_result($result, $i, 'dirid'));
                       $file_id       = db_ei(db_result($result, $i, 'fileid'));
                       $sql_up        = "UPDATE cvs_checkins SET addedlines=" . $added . ", removedlines=" . $removed . " WHERE repositoryid=" . $repository_id . " AND dirid=" . $dir_id . " AND fileid=" . $file_id . " AND revision='" . db_es($revision) . "'";
                       $res = db_query($sql_up);
                       break;
                }
            }
        }

        if (!$filename) {
            $filename = '';
        } else {
            if ($type == 'Remove') {
                $filename = makeCvsDirLink($group_id, db_result($result, $i, 'file'), $filename, db_result($result, $i, 'dir'));
                $rev_text = '';
            } else {
        // Clean file path to remove duplicate separators
                $filename = preg_replace('/\/\//', '/', $filename);
                $filename = preg_replace('/\.\//', '', $filename);

                if ($type == 'Change') {
                          // horrible hack to 'guess previous revision' to diff with
                          $prev = explode(".", $revision);

                          $lastIndex = sizeof($prev);
                          $lastIndex = $lastIndex - 1;
                    if ($prev[$lastIndex] != '1') {
                        $prev[$lastIndex] = $prev[$lastIndex] - 1;
                        $previous = join(".", $prev);
                    } else {
                        $index = 0;
                        $new_prev = array();
                        while ($index <= $lastIndex - 2) {
                            $new_prev[$index] = $prev[$index];
                            $index++;
                        }
                        $previous = join('.', $new_prev);
                    }
                    $type = makeCvsLink($group_id, $filename, $type_text, '', '&r1=' . $previous . '&r2=' . $revision);
                }

                $rev_text = makeCvsLink($group_id, $filename, $revision, $revision, '&view=markup');
                $filename = makeCvsLink($group_id, $filename, $filename, '', '&view=log');
            }
        }
        // $commits_url = '<A HREF="/commits/download.php/Commits'.$commit_id.'.txt?commit_id='.$id.'">'.$filename.'</a>';

        echo '
			<TR class="' . util_get_alt_row_color($i) . '">' .
        '<TD class="small"><b>' . $filename . '</b></TD>' .
        '<TD class="small">' . $rev_text . '</TD>' .
        '<TD class="small">' . db_result($result, $i, 'branch') . '</TD>' .
        '<TD class="small">' . $type . '</TD>' .
        '<TD class="small">' . $added . '</TD>' .
        '<TD class="small">' . $removed . '</TD></TR>';
    }

    /*
        Show extra rows for <-- Prev / Next -->
    */
    echo '
		<TR><TD COLSPAN="2" class="small">';
    if ($offset > 0) {
        echo '<A HREF="?func=browse&group_id=' . $group_id . '&set=' . $set . '&offset=' . ($offset - 50) . '"><B>&lt; ' . $GLOBALS['Language']->getText('global', 'prev') . '</B></A>';
    } else {
        echo '&nbsp;';
    }
    echo '</TD><TD>&nbsp;</TD><TD COLSPAN="2" class="small">';

    if ($rows == 50) {
        echo '<A HREF="?func=browse&group_id=' . $group_id . '&set=' . $set . '&offset=' . ($offset + 50) . '"><B>' . $GLOBALS['Language']->getText('global', 'prev') . ' 50 &gt;</B></A>';
    } else {
        echo '&nbsp;';
    }
    echo '</TD></TR></TABLE>';
}


// Are there any commits in the cvs history ?
function format_cvs_history($group_id)
{
    $res_cvsfullhist = get_cvs_history($group_id);

    if (!$res_cvsfullhist || db_numrows($res_cvsfullhist) < 1) {
        $output = '<P>' . $GLOBALS['Language']->getText('cvs_intro', 'no_history');
    } else {
        $cvshist = array();
        while ($row_cvsfullhist = db_fetch_array($res_cvsfullhist)) {
            $cvshist[$row_cvsfullhist['user_name']]['full'] = $row_cvsfullhist['commits'];
            $cvshist[$row_cvsfullhist['user_name']]['last'] = 0;
        }

      // Now over the last 7 days
        $res_cvslasthist = get_cvs_history($group_id, 7 * 24 * 3600);

        while ($row_cvslasthist = db_fetch_array($res_cvslasthist)) {
            $cvshist[$row_cvslasthist['user_name']]['last'] = $row_cvslasthist['commits'];
        }

      // Format output
        $output = '<P><b>' . $GLOBALS['Language']->getText('cvs_intro', 'nb_commits') . '</b><BR>&nbsp;';
        foreach ($cvshist as $user => $value) {
            $output .= '<BR>' . $user . ' (' . $cvshist[$user]['last'] . '/'
            . $cvshist[$user]['full'] . ')';
        }
    }
    return $output;
}


// list the number of commits by user either since the beginning of
// history if the period argument is not given or if it is given then
// over the last "period" of time.
// period is expressed in seconds
function get_cvs_history($group_id, $period = false)
{
    $pm = ProjectManager::instance();
    $group = $pm->getProject($group_id);

    if ($period) {
      // All times in cvs tables are stored in UTC ???
        $date_clause = "AND co.comm_when >= " . date("YmdHis", (gmdate('U') - $period)) . " ";
    } else {
        $date_clause = "";
    }
    $cvs_repository = db_es('/cvsroot/' . $group->getUnixName(false));
    $query = "SELECT u.user_name, count(co.id) as commits " .
    "FROM cvs_commits co, user u, cvs_repositories repo, cvs_checkins ci " .
    "WHERE co.whoid=u.user_id " .
    "AND repo.repository='$cvs_repository' " .
    "AND ci.repositoryid=repo.id " .
    "AND ci.whoid=co.whoid " .
    "AND ci.commitid=co.id " .
    $date_clause .
    "GROUP BY co.whoid ORDER BY user_name";
    $result = db_query($query);
    return($result);
}

function get_user_shell($user_id)
{
    $user_id  = db_ei($user_id);
    $res_user = db_query("SELECT shell FROM user WHERE user_id=$user_id");
    $row_user = db_fetch_array($res_user);
    return $row_user['shell'];
}

function check_cvs_access($username, $group_name, $cvspath)
{
    $pm = ProjectManager::instance();
    $project = $pm->getProjectByUnixName($group_name);

  //accept old url containing a .diff at the end of the filename
    if (strpos($cvspath, '.diff') == (strlen($cvspath) - 5)) {
        $cvspath = substr($cvspath, 0, (strlen($cvspath) - 5));
    }

  // if the file path exists as such then it's a directory
  // else add the ,v extension because it's a file
    $path = "/cvsroot/" . $group_name . '/' . $cvspath;
    if (!is_dir($path)) {
        $path = $path . ',v';
    }
    $mode = fileperms($path);

  // Also check permissions on top directory (in case of private repo)
    $mode_top = fileperms("/cvsroot/" . $group_name);

  // A directory that is not world readable can only be viewed
  // through viewvc if the user is a project member
    if ($project && (($mode_top & 0x0004) == 0 || ($mode & 0x0004) == 0) && !user_ismember($project->getID())) {
        return false;
    } else {
        return true;
    }
}


// Return the group ID from a repository name
// Repository names look like '/cvsroot/groupname', without trailing slash!
function get_group_id_from_repository($repository)
{
    $pm = ProjectManager::instance();
    $project = $pm->getProjectByUnixName(basename($repository));
    if (! $project) {
        return false;
    }

    return $project->getID();
}

function cvs_get_revisions(&$project, $offset, $chunksz, $_tag = 100, $_branch = 100, $_commit_id = '', $_commiter = 100, $_srch = '', $order_by = '', $pv = 0)
{
    //if status selected, and more to where clause
    if ($_branch != 100) {
        //for open tasks, add status=100 to make sure we show all
        $branch_str = "AND cvs_checkins.branchid=" . db_ei($_branch);
    } else {
        //no status was chosen, so don't add it to where clause
        $branch_str = '';
    }

    //if assigned to selected, and more to where clause
    if ($_commit_id != '') {
        $_commit_id = db_ei($_commit_id);
        $commit_str = "AND cvs_commits.id=$_commit_id AND cvs_checkins.commitid != 0 ";
    } else {
        $commit_str = '';
    }

    if ($_commiter != 100) {
        $_commiter    = db_es($_commiter);
        $commiter_str = "AND user.user_id=cvs_checkins.whoid " .
          "AND user.user_name='$_commiter' ";
    } else {
        //no assigned to was chosen, so don't add it to where clause
        $commiter_str = '';
    }

    if ($_srch != '') {
        $_srch    = db_es('%' . $_srch . '%');
        $srch_str = "AND cvs_descs.description like '$_srch' ";
    } else {
        $srch_str = "";
    }

    //build page title to make bookmarking easier
    //if a user was selected, add the user_name to the title
    //same for status

    //commits_header(array('title'=>'Browse Commits'.
    //    (($_assigned_to)?' For: '.user_getname($_assigned_to):'').
    //    (($_tag && ($_tag != 100))?' By Status: '. get_commits_status_nam//e($_status):''),
    //           'help' => 'CommitsManager.html'));

    // get repository id
    $cvs_repository = db_es('/cvsroot/' . $project->getUnixName(false));
    $query = "SELECT id from cvs_repositories where cvs_repositories.repository='$cvs_repository' ";
    $rs = db_query($query);
    $repo_id = db_result($rs, 0, 0);
    $repo_id = $repo_id ? $repo_id : -1;

    $select = 'SELECT distinct cvs_checkins.commitid as id, cvs_checkins.commitid as revision, cvs_descs.id as did, cvs_descs.description, cvs_commits.comm_when as c_when, cvs_commits.comm_when as date, cvs_commits.comm_when as f_when, user.user_name as who ';
    $from = "FROM cvs_descs, cvs_checkins, user, cvs_commits ";
    $where = "WHERE cvs_checkins.descid=cvs_descs.id " .
    "AND " . (check_cvs_access(user_getname(), $project->getUnixName(false), '') ? 1 : 0) . " " .
        "AND cvs_checkins.commitid=cvs_commits.id " .
        "AND user.user_id=cvs_checkins.whoid " .
            "AND cvs_checkins.repositoryid=" . $repo_id . " " .
        "$commiter_str " .
            "$commit_str " .
        "$srch_str " .
        "$branch_str ";

    $limit = '';
    if (! $pv) {
        $limit = " LIMIT $offset,$chunksz";
    }

    if (!$order_by) {
        $order_by = " ORDER BY id desc, f_when desc ";
    }

    $sql = $select .
    $from .
    $where .
    $order_by .
    $limit;

    $result = db_query($sql);

    /* expensive way to have total rows number didn'get a cheaper one */

    $sql1 = $select .
    $from .
    $where;
    $result1 = db_query($sql1);
    $totalrows = db_numrows($result1);

    return array($result, $totalrows);
}

function cvs_get_revision_detail($commit_id)
{
    $commit_id = db_ei($commit_id);
    $sql = "SELECT repository, cvs_commits.comm_when as c_when, repositoryid, description, file, fileid, dir, dirid, type, branch, revision, addedlines, removedlines " .
                    "FROM cvs_dirs, cvs_descs, cvs_files, cvs_checkins, cvs_branches, cvs_repositories, cvs_commits " .
                    "WHERE cvs_checkins.fileid=cvs_files.id " .
                    "   AND cvs_checkins.dirid=cvs_dirs.id " .
                    "   AND cvs_checkins.commitid=cvs_commits.id " .
                    "   AND cvs_checkins.branchid=cvs_branches.id " .
                    "   AND cvs_checkins.descid=cvs_descs.id " .
                    "   AND cvs_checkins.repositoryid=cvs_repositories.id " .
                    "   AND cvs_checkins.commitid=$commit_id ";
    $result = db_query($sql);
    return $result;
}
