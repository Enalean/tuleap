<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * Originally written by Laurent Julliard 2004 Codendi Team, Xerox
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

require_once __DIR__ . '/svn_data.php';

function svn_header(Project $project, \Tuleap\Layout\HeaderConfiguration $params, string|null $path)
{
    global $Language;

    \Tuleap\Project\ServiceInstrumentation::increment('svncore');

    $service = $project->getService('svn');
    if (! $service) {
        exit_error($Language->getText('global', 'error'), $Language->getText('svn_utils', 'svn_off'));
    }

    $group_id = $project->getID();

    $toolbar   = [];
    $toolbar[] = ['title' => $Language->getText('svn_utils', 'svn_info'),
        'url'   => '/svn/?func=info&group_id=' . $group_id,
    ];

    if ($project->isPublic() || user_isloggedin()) {
        $toolbar[] = ['title' => $Language->getText('svn_utils', 'browse_tree'),
            'url'   => '/svn/viewvc.php/?roottype=svn&root=' . $project->getUnixName(false),
        ];
    }

    $service->displayHeader(
        $params->title,
        [
            [
                'title' => $GLOBALS['Language']->getText('project_admin_editservice', 'service_svn_lbl_key'),
                'url' => '/svn/?group_id=' . $group_id,
            ],
        ],
        $toolbar,
        $params
    );

    echo svn_deprecation_notice($project);
}

function svn_deprecation_notice(\Project $project): string
{
    if ($project->usesService('plugin_svn')) {
        return "<p><div class='alert alert-error'> " . sprintf(_('Subversion Core is deprecated, it will be removed on march 2022. Please install and activate `SVN` plugin and <a href="/plugins/svn/%s/admin-migrate">migrate your repository.</a>'), urlencode($project->getUnixNameLowerCase())) .  "</div></p>";
    }
    return "<p><div class='alert alert-danger'> " . sprintf(_('Subversion Core is deprecated, it will be removed on march 2022. Please <a href="/project/%d/admin/services">install and activate `SVN` plugin.</a>'), urlencode((string) $project->getID())) .  "</div></p>";
}

function svn_footer($params)
{
    site_project_footer($params);
}


function svn_utils_technician_box($group_id, $name = '_commiter', $checked = 'xzxz', $text_100 = 'None')
{
    global $Language;
    if (! $group_id) {
        return $Language->getText('svn_utils', 'g_id_err');
    } else {
        $result = svn_data_get_technicians($group_id);
        if (! in_array($checked, util_result_column_to_array($result))) {
            // Selected 'my commits' but never commited
            $checked = 'xzxz';
        }
        $userids   = util_result_column_to_array($result, 0);
        $usernames = util_result_column_to_array($result, 1);
        // Format user name according to preferences
        $UH = new UserHelper();
        foreach ($usernames as &$username) {
            $username = $UH->getDisplayNameFromUserName($username);
        }
        return html_build_select_box_from_arrays($userids, $usernames, $name, $checked, true, $text_100, false, '', false, '', CODENDI_PURIFIER_CONVERT_HTML);
    }
}


function svn_utils_show_revision_list($result, $offset, $total_rows, $set = 'any', $commiter = '100', $path = '', $chunksz = 15, $morder = '', $msort = 0)
{
    global $group_id,$Language;
    /*
        Accepts a result set from the svn_commits table. Should include all columns from
        the table, and it should be joined to USER to get the user_name.
    */
    $url = '?func=browse&group_id=' . urlencode($group_id) . '&set=' . urlencode($set) . '&msort=' . urlencode($msort);

    if ($set == 'custom') {
        $url .= $pref_stg;
    }

    $purifier = Codendi_HTMLPurifier::instance();

    $url_nomorder = $url;
    $url         .= "&morder=" . $purifier->purify(urlencode($morder));

    if ($morder != '') {
        $orderstr = $Language->getText('svn_utils', 'sorted_by') . ' ' . svn_utils_criteria_list_to_text($morder, $url_nomorder);
    } else {
        $orderstr = '';
    }
    echo '<A name="results"></A>';
    echo '<h3>' . $Language->getText('svn_utils', 'match_ci', $total_rows) . ' ' . $orderstr . '</h3>';

    $nav_bar  = '<table width= "100%"><tr>';
    $nav_bar .= '<td width="20%" align ="left">';

    echo '<P>' . $Language->getText('svn_utils', 'sort', $url . '&order=#results') . ' ';

    if ($msort) {
        $url_alternate_sort = str_replace('msort=1', 'msort=0', $url) .
        '&order=#results';
        $text               = $Language->getText('svn_utils', 'deacti');
    } else {
        $url_alternate_sort = str_replace('msort=0', 'msort=1', $url) .
        '&order=#results';
        $text               = $Language->getText('svn_utils', 'acti');
    }

    echo $Language->getText('svn_utils', 'multi_sort', [$url_alternate_sort, $text]) . "\n";

    // If all bugs on screen so no prev/begin pointer at all
    if ($total_rows > $chunksz) {
        if ($offset > 0) {
            $nav_bar .=
            '<A HREF="' . $purifier->purify($url) . '&offset=0#results"><B><< ' . $Language->getText('global', 'begin') . '</B></A>' .
            '&nbsp;&nbsp;&nbsp;&nbsp;' .
            '<A HREF="' . $purifier->purify($url) . '&offset=' . $purifier->purify(urlencode($offset - $chunksz)) .
            '#results"><B>< ' . $Language->getText('global', 'prev') . ' ' . $purifier->purify($chunksz) . '</B></A></td>';
        } else {
            $nav_bar .=
            '<span class="disable">&lt;&lt; ' . $Language->getText('global', 'begin') . '&nbsp;&nbsp;&lt; ' . $Language->getText('global', 'prev') . ' ' . $purifier->purify($chunksz) . '</span>';
        }
    }

    $nav_bar .= '</td>';

    $offset_last = min($offset + $chunksz - 1, $total_rows - 1);
    $nav_bar    .= '<td width= "60% " align = "center" class="small">' . $Language->getText('svn_utils', 'items', [($offset + 1), ($offset_last + 1)]) . "</td>\n";

    $nav_bar .= '<td width="20%" align ="right">';

    // If all bugs on screen, no next/end pointer at all
    if ($total_rows > $chunksz) {
        if (($offset + $chunksz) < $total_rows) {
            $offset_end = ($total_rows - ($total_rows % $chunksz));
            if ($offset_end == $total_rows) {
                $offset_end -= $chunksz;
            }

            $nav_bar .=
            '<A HREF="' . $url . '&offset=' . $purifier->purify(urlencode($offset + $chunksz)) .
            '#results" class="small"><B>' . $Language->getText('global', 'next') . ' ' . $purifier->purify(urlencode($chunksz)) . ' &gt;</B></A>' .
            '&nbsp;&nbsp;&nbsp;&nbsp;' .
            '<A HREF="' . $url . '&offset=' . $purifier->purify(urlencode($offset_end)) .
            '#results" class="small"><B>' . $Language->getText('global', 'end') . ' &gt;&gt;</B></A></td>';
        } else {
            $nav_bar .=
            '<span class="disable">' . $Language->getText('global', 'next') . ' ' . $purifier->purify(urlencode($chunksz)) .
            ' &gt;&nbsp;&nbsp;' . $Language->getText('global', 'end') . ' &gt;&gt;</span>';
        }
    }
    $nav_bar .= '</td>';
    $nav_bar .= "</tr></table>\n";

    echo $nav_bar;

    $filter_str = '';
    if ($commiter != '100') {
        $filter_str = "&commiter='" . urlencode($commiter) . "'";
    }
    if ($path != '') {
        $url_path   = $purifier->purify(urlencode($path));
        $filter_str = $filter_str . "&path='$url_path'";
    }

    $url        .= "&order=";
    $title_arr   = [];
    $title_arr[] = $Language->getText('svn_browse_revision', 'rev');
    $title_arr[] = $Language->getText('svn_utils', 'desc');
    $title_arr[] = $Language->getText('svn_utils', 'date');
    $title_arr[] = $Language->getText('svn_browse_revision', 'commiter');

    $links_arr   = [];
    $links_arr[] = $url . 'revision#results';
    $links_arr[] = $url . 'description#results';
    $links_arr[] = $url . 'date#results';
    $links_arr[] = $url . 'who#results';

    $url_nomorder = $url;
    $url         .= "&morder=$morder";

    echo html_build_list_table_top($title_arr, $links_arr);

    $i  = 0;
    $uh = UserHelper::instance();
    while ($row = db_fetch_array($result)) {
        // description is escaped in svn-checkins.pl
        $description = htmlspecialchars_decode($row['description'], ENT_QUOTES);
        echo '
			<TR class="' . util_get_alt_row_color($i++) . '">' .
        '<TD class="small"><b><A HREF="?func=detailrevision&group_id=' . $purifier->purify(urlencode($group_id)) . '&commit_id=' . $purifier->purify($row['commit_id']) . $filter_str . '">' . $purifier->purify($row['revision']) .
        '</b></A></TD>' .
        '<TD class="small">' . $purifier->purify($description, CODENDI_PURIFIER_BASIC, $group_id) . '</TD>' .
        '<TD class="small">' . format_date($GLOBALS['Language']->getText('system', 'datefmt'), $row['date']) . '</TD>' .
        '<TD class="small">' . $uh->getLinkOnUserFromUserId($row['whoid']) . '</TD></TR>';
    }

    /*
        Show extra rows for <-- Prev / Next -->
    */
    echo '</TD></TR></TABLE>';
    echo $nav_bar;
}

function svn_utils_make_viewlink($group_name, $filename, $text, $view_params)
{
    $purifier = Codendi_HTMLPurifier::instance();
    return '<A href="/svn/viewvc.php/' . $purifier->purify(urlencode($filename)) . '?root=' . $purifier->purify(urlencode($group_name)) . '&roottype=svn' . $view_params . '"><B>' . $purifier->purify($text) . '</B></A>';
}


// Check if a sort criteria is already in the list of comma
// separated criterias. If so invert the sort order, if not then
// simply add it
function svn_utils_add_sort_criteria($criteria_list, $order, $msort)
{
    //echo "<br>DBG \$criteria_list=$criteria_list,\$order=$order";
    $found = false;
    if ($criteria_list) {
        $arr = explode(',', $criteria_list);
        $i   = 0;
        foreach ($arr as $attr) {
            preg_match("/\s*([^<>]*)([<>]*)/", $attr, $match);
            list(,$mattr,$mdir) = $match;
            //echo "<br><pre>DBG \$mattr=$mattr,\$mdir=$mdir</pre>";
            if ($mattr == $order) {
                if (($mdir == '>') || (! isset($mdir))) {
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

    //echo "<br>DBG \$arr[]=".join(',',$arr);

    return(join(',', $arr));
}

/**
 * @psalm-return array<array{order:"ASC"|"DESC", column: "revision"|"commit_id"|"description"|"date"|"whoid"}>
 */
function svn_utils_criteria_list_to_query(string $criteria_list): array
{
    $order_list = [];

    foreach (explode(',', $criteria_list) as $criteria) {
        if (preg_match('/^(?<column>[a-z]+)(?<order>[<>]?)$/', $criteria, $matches) === 1) {
            $column = $matches['column'];
            if (! in_array($column, ['revision', 'commit_id', 'description', 'date', 'whoid'], true)) {
                continue;
            }

            $order = 'ASC';
            if ($matches['order'] === '<') {
                $order = 'DESC';
            }

            $order_list[] = ['order' => $order, 'column' => $column];
        }
    }

    return $order_list;
}

// Transform criteria list to readable text statement
// $url must not contain the morder parameter
function svn_utils_criteria_list_to_text($criteria_list, $url)
{
    if ($criteria_list) {
        $morder = '';
        $arr    = explode(',', $criteria_list);

        $purifier = Codendi_HTMLPurifier::instance();

        foreach ($arr as $crit) {
            $morder .= ($morder ? "," . $crit : $crit);
            $attr    = str_replace('>', '', $crit);
            $attr    = str_replace('<', '', $attr);

            $arr_text[] = '<a href="' . $url . '&morder=' . $purifier->purify(urlencode($morder)) . '#results">' .
            $purifier->purify(svn_utils_field_get_label($attr)) . '</a><img src="' . util_get_dir_image_theme() .
            ((substr($crit, -1) == '<') ? 'dn' : 'up') .
            '_arrow.png" border="0">';
        }
    }

    return join(' > ', $arr_text);
}

function svn_utils_field_get_label($sortField)
{
    global $Language;
    if ($sortField == "id") {
        return $Language->getText('svn_browse_revision', 'rev');
    } elseif ($sortField == "date") {
        return $Language->getText('svn_utils', 'date');
    } elseif ($sortField == "who") {
        return $Language->getText('svn_browse_revision', 'commiter');
    }
    return $sortField;
}


function svn_utils_show_revision_detail($result, $group_id, $group_name, $commit_id)
{
    global $Language;
    $purifier = Codendi_HTMLPurifier::instance();
    /*
      Accepts a result set from the svn_checkins table. Should include all columns from
      the table, and it should be joined to USER to get the user_name.
    */

    $rows = db_numrows($result);
    $url  = "/svn/?func=detailrevision&commit_id=$commit_id&group_id=$group_id&order=";

    $description = db_result($result, 0, 'description');
    $description = util_line_wrap(htmlspecialchars_decode($description, ENT_QUOTES));
    $list_log    = '<pre>' . $purifier->purify($description, CODENDI_PURIFIER_BASIC_NOBR, $group_id) . '</pre>';

    $revision = db_result($result, 0, 'revision');
    $hdr      = '[' . $Language->getText('svn_browse_revision', 'rev') . ' #' . $revision . '] - ';

    echo '<h2>' . $hdr . format_date($GLOBALS['Language']->getText('system', 'datefmt'), db_result($result, 0, 'date')) . '</h2></h2>';

    echo '<table WIDTH="100%" BORDER="0" CELLSPACING="1" CELLPADDING="2"><tr class="' . util_get_alt_row_color(0) . '"><td>' . $list_log . '</td></tr></table>';

    $crossref_fact = new CrossReferenceFactory($revision, ReferenceManager::REFERENCE_NATURE_SVNREVISION, $group_id);
    $crossref_fact->fetchDatas();
    if ($crossref_fact->getNbReferences() > 0) {
        echo '<h3> ' . $Language->getText('cross_ref_fact_include', 'references') . '</h3>';
        $crossref_fact->DisplayCrossRefs();
    }

    echo '<h3> ' . $Language->getText('svn_utils', 'impacted_files') . '</h3>';
    $title_arr   = [];
    $title_arr[] = $Language->getText('svn_utils', 'file');
    $title_arr[] = $Language->getText('svn_browse_revision', 'rev');
    $title_arr[] = $Language->getText('svn_utils', 'type');
    //$title_arr[]='AddedLines'; To be implemented
    //$title_arr[]='RemovedLines'; To be implemented

    $links_arr   = [];
    $links_arr[] = $url . 'filename';
    $links_arr[] = $url . '';
    $links_arr[] = $url . 'type';

    $pm      = ProjectManager::instance();
    $project = $pm->getProject($group_id);
    $root    = $project->getUnixName();

    echo html_build_list_table_top($title_arr, $links_arr);

    for ($i = 0; $i < $rows; $i++) {
        $type     = db_result($result, $i, 'type');
        $dirname  = db_result($result, $i, 'dir');
        $filename = db_result($result, $i, 'file');
        $fullpath = $dirname . $filename;

        if ($filename) {
            // It' a file
            $viewfile_url = svn_utils_make_viewlink($group_name, $fullpath, $fullpath, "&pathrev=$revision&view=log");
            $viewrev_url  = svn_utils_make_viewlink($group_name, $fullpath, $revision, "&revision=$revision&pathrev=$revision&view=markup");
        } else {
            // It' a directory
            $viewfile_url = svn_utils_make_viewlink($group_name, $fullpath, $fullpath, "&pathrev=$revision");
            $viewrev_url  = svn_utils_make_viewlink($group_name, $fullpath, $revision, "&pathrev=$revision&view=log");
        }

        if ($type == 'Change') {
            $viewtype_url = svn_utils_make_viewlink(
                $group_name,
                $fullpath,
                $Language->getText('svn_utils', 'change'),
                "&r1=" . ($revision - 1) . "&r2=$revision&diff_format=h&pathrev=$revision"
            );
        } elseif ($type == 'Add') {
            $viewtype_url = $Language->getText('svn_utils', 'add');
        } elseif ($type == 'Delete') {
            $viewtype_url = $Language->getText('svn_utils', 'del');
        }

        echo '
	       <TR class="' . util_get_alt_row_color($i) . '">' .
        '<TD class="small"><b>' . $viewfile_url . '</b></TD>' .
        '<TD class="small" width="10%" align="center">' . $viewrev_url . '</TD>' .
        '<TD class="small" width="10%" align="center">' . $viewtype_url . '</TD>';
    //'<TD class="small">'.$added.'</TD>'. // To be done
    //'<TD class="small">'.$removed.'</TD></TR>'; // To be done
    }

    echo '</TD></TR></TABLE>';
}

// Is there anything in the svn history table ?
function svn_utils_format_svn_history($group_id)
{
    global $Language;
    $output          = '';
    $res_svnfullhist = svn_data_get_svn_history($group_id);

    if (! $res_svnfullhist || db_numrows($res_svnfullhist) < 1) {
        print '<P>' . $Language->getText('svn_utils', 'no_hist');
    } else {
        $svnhist = [];
        while ($row_svnfullhist = db_fetch_array($res_svnfullhist)) {
            $svnhist[$row_svnfullhist['user_name']]['full'] = $row_svnfullhist['commits'];
            $svnhist[$row_svnfullhist['user_name']]['last'] = 0;
        }

    // Now over the last 7 days
        $res_svnlasthist = svn_data_get_svn_history($group_id, 7 * 24 * 3600);

        while ($row_svnlasthist = db_fetch_array($res_svnlasthist)) {
            $svnhist[$row_svnlasthist['user_name']]['last'] = $row_svnlasthist['commits'];
        }

        // Format output
        $output = '<P><b>' . $Language->getText('svn_utils', 'ci_week') . '</b><BR>&nbsp;';
        $uh     = new UserHelper();
        $hp     = Codendi_HTMLPurifier::instance();
        foreach ($svnhist as $user => $value) {
            $output .= '<BR>'
                . $hp->purify($uh->getDisplayNameFromUserName($user), CODENDI_PURIFIER_CONVERT_HTML)
                . ' (' . $hp->purify($svnhist[$user]['last']) . '/'
                . $hp->purify($svnhist[$user]['full']) . ')';
        }
    }
    return $output;
}

// read permission access file. The project specific part.
function svn_utils_read_svn_access_file($project_svnroot)
{
    global $Language;

    $filename = "$project_svnroot/.SVNAccessFile";
    $buffer   = '';

    $fd = @fopen("$filename", "r");
    if (! $fd) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('svn_utils', 'file_err', $filename));
        $buffer = false;
    } else {
        $in_settings = false;
        while (! feof($fd)) {
            $line = fgets($fd, 4096);
            if (strpos($line, '# BEGIN CODENDI DEFAULT') !== false) {
                $in_settings = true;
            }
            if (! $in_settings) {
                $buffer .= $line;
            }
            if (strpos($line, '# END CODENDI DEFAULT') !== false) {
                $in_settings = false;
            }
        }
        fclose($fd);
    }
    return $buffer;
}

$GLOBALS['SVNACCESS'] = "None";
$GLOBALS['SVNGROUPS'] = "None";

/**
 * Function svn_utils_parse_access_file : parse the .SVNAccessFile of the project $gname
 * and populate the global arrays $SVNACCESS and $SVNGROUPS.
 *
 * @param string $project_svnroot the unix name of the group (project) we want to parse the access file
 * @global array $SVNACCESS the array populated with the rights for each user for this project $gname
 * @global array $SVNGROUPS the array populated with the members of each ugroup of this project
 *
 * Warning:
 *    The code source of this function is writing in Python too.
 *    If you modify part of this code, thanks to check if
 *    the corresponding Python code needs to be updated too.
 *    (see src/utils/svn/svnaccess.py)
 */
function svn_utils_parse_access_file($project_svnroot)
{
    global $SVNACCESS, $SVNGROUPS,$Language;
    $filename  = "$project_svnroot/.SVNAccessFile";
    $SVNACCESS = [];
    $SVNGROUPS = [];

    $f = @fopen($filename, "rb");
    if ($f === false) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('svn_utils', 'file_err', $filename));
    } else {
        $path_pat    = '/^\s*\[(.*)\]/'; // assume no repo name 'repo:'
        $perm_pat    = '/^\s*([^ ]*)\s*=\s*(.*)$/';
        $group_pat   = '/^\s*([^ ]*)\s*=\s*(.*)$/';
        $empty_pat   = '/^\s*$/';
        $comment_pat = '/^\s*#/';

        $ST_START = 0;
        $ST_GROUP = 1;
        $ST_PATH  = 2;

        $state = $ST_START;

        $content   = @fread($f, filesize($filename));
        $separator = "\n\t\r\0\x0B";
        $line      = strtok($content, $separator);
        while ($line) {
          //echo $line."<br>\n";
            if (preg_match($comment_pat, $line) || preg_match($empty_pat, $line)) {
                $line = strtok($separator);
                continue;
            }
            $m = preg_match($path_pat, $line, $matches);
            if ($m) {
                $path = $matches[1];
                if ($path == "groups") {
                    $state = $ST_GROUP;
                } else {
                    $state = $ST_PATH;
                }
            }

            if ($state == $ST_GROUP) {
                $m = preg_match($group_pat, $line, $matches);
                if ($m) {
                    $group                         = $matches[1];
                    $users                         = $matches[2];
                    $SVNGROUPS[strtolower($group)] = array_map('trim', preg_split("/,/D", strtolower($users)));
                }
            } elseif ($state == $ST_PATH) {
                $m = preg_match($perm_pat, $line, $matches);
                if ($m) {
                    $who  = $matches[1];
                    $perm = $matches[2];

                    if (strpos($who, '@') === 0) {
                        if (array_key_exists(strtolower(substr($who, 1)), $SVNGROUPS)) {
                            foreach ($SVNGROUPS[strtolower(substr($who, 1))] as $user) {
                                if (array_key_exists($user, $SVNACCESS) === false) {
                                    $SVNACCESS[$user] = [];
                                }
                                      $SVNACCESS[$user][$path] = $perm;
                                      //echo "SVNACCESS[$user][$path] = $perm <br>\n";
                            }
                        }
                    } else {
                        if (array_key_exists(strtolower($who), $SVNACCESS) === false) {
                            $SVNACCESS[strtolower($who)] = [];
                        }
                        $SVNACCESS[strtolower($who)][$path] = $perm;
                      //echo "SVNACCESS[$who][$path] = $perm <br>\n";
                    }
                }
            }

            $line = strtok($separator);
        }
        fclose($f);
    }
}


function svn_utils_get_forbidden_paths($username, $project_svnroot)
{
    global $SVNACCESS;

    if ($SVNACCESS == "None") {
        svn_utils_parse_access_file($project_svnroot);
    }

    $em = EventManager::instance();
    $em->processEvent('svn_check_access_username', ['username'        => &$username,
        'project_svnroot' => $project_svnroot,
    ]);

    $forbidden = [];
    if (! user_is_super_user()) {   // super user have all the rights (no forbidden paths)
        if (array_key_exists('*', $SVNACCESS)) {
            foreach ($SVNACCESS['*'] as $path => $perm) {
                if (strpos($perm, 'r') === false) {
                    $forbidden[$path] = true;
                }
            }
        }

        if (array_key_exists(strtolower($username), $SVNACCESS)) {
            foreach ($SVNACCESS[strtolower($username)] as $path => $perm) {
                if (strpos($perm, 'r') === false) {
                    $forbidden[$path] = true;
                } else {
                    if (array_key_exists($path, $forbidden)) {
                        unset($forbidden[$path]);
                    }
                }
            }
        }
    }
    return $forbidden;
}


/**
 * Function svn_utils_check_access : check if the user $username can access the path $svnpath of the project $gname
 * regarding the global arrays $SVNACCESS and $SVNGROUPS.
 *
 * @param string $username the login name of the user we want to check the perms
 * @param string $project_svnroot the unix name of the group (project)
 * @param string $svnpath the subversion path to check
 * @global array $SVNACCESS the array populated with the rights for each user for this project $gname
 * @global array $SVNGROUPS the array populated with the members of each ugroup of this project
 *
 * Warning:
 *    The code source of this function is writing in Python too.
 *    If you modify part of this code, thanks to check if
 *    the corresponding Python code needs to be updated too.
 *    (see src/utils/svn/svnaccess.py)
 */

function svn_utils_check_access($username, $project_svnroot, $svnpath)
{
    global $SVNACCESS;

    if ((user_getname() == $username) && (user_is_super_user())) {
        return true;
    }

    $em = EventManager::instance();
    $em->processEvent('svn_check_access_username', ['username'        => &$username,
        'project_svnroot' => $project_svnroot,
    ]);
    $username = strtolower($username);

    if ($SVNACCESS == "None") {
        svn_utils_parse_access_file($project_svnroot);
    }

    $perm = '';
    $path = '/' . $svnpath;
    while (true) {
        if (array_key_exists($username, $SVNACCESS) && array_key_exists($path, $SVNACCESS[$username])) {
            $perm = $SVNACCESS[$username][$path];
          //echo "match: SVNACCESS[$username][$path] $perm";
            break;
        } elseif (array_key_exists('*', $SVNACCESS) && array_key_exists($path, $SVNACCESS['*'])) {
            $perm = $SVNACCESS['*'][$path];
          //echo "match: SVNACCESS[*][$path] $perm";
            break;
        } else {
          // see if it maches higher in the path
            if ($path == '/') {
                break;
            }
            $idx = strrpos($path, '/');
            if ($idx == 0) {
                $path = '/';
            } else {
                $path = substr($path, 0, $idx);
            }
        }
    }
    if (strpos($perm, 'r') === false) {
        return false;
    } else {
        return true;
    }
}

function svn_utils_is_there_specific_permission($project_svnroot)
{
    $specifics = svn_utils_read_svn_access_file($project_svnroot);
    return ! $specifics || $specifics != '';
}

/**
 * @psalm-param array<array{order:"ASC"|"DESC", column: "revision"|"commit_id"|"description"|"date"|"whoid"}> $order_by
 */
function svn_get_revisions(Project $project, $offset, $chunksz, $_rev_id = '', $_commiter = '', $_srch = '', array $order_by = [], $pv = 0, $foundRows = true)
{
    global $_path;

    $um = UserManager::instance();

    //check user access rights
    $forbidden = svn_utils_get_forbidden_paths($um->getCurrentUser()->getUserName(), $project->getSVNRootPath());

    $select   = 'SELECT';
    $group_by = '';

    if ($foundRows) {
        $select .= ' SQL_CALC_FOUND_ROWS';
    }
    $select .= ' svn_commits.revision as revision, svn_commits.id as commit_id, svn_commits.description as description, svn_commits.date as date, svn_commits.whoid';

    $from  = " FROM svn_commits";
    $where = " WHERE svn_commits.group_id=" . db_ei($project->getGroupId());

    //check user access rights
    if (! empty($forbidden)) {
        $from           .= " INNER JOIN svn_checkins ON (svn_checkins.commitid = svn_commits.id)";
        $from           .= " INNER JOIN svn_dirs ON (svn_dirs.id = svn_checkins.dirid)";
        $where_forbidden = "";
        foreach ($forbidden as $no_access => $v) {
            if ($no_access == $_path) {
                $_path = '';
            }
            $where_forbidden .= " AND svn_dirs.dir not like '" . db_es(substr($no_access, 1)) . "%'";
        }
        $where    .= $where_forbidden;
        $group_by .= ' GROUP BY revision';
    }

    //if status selected, and more to where clause
    if ($_path != '') {
        $path_str = " AND svn_dirs.dir like '%" . db_es($_path) . "%'";
        if (! isset($forbidden) || empty($forbidden)) {
            $from     .= " INNER JOIN svn_checkins ON (svn_checkins.commitid = svn_commits.id)";
            $from     .= " INNER JOIN svn_dirs ON (svn_dirs.id = svn_checkins.dirid)";
            $group_by .= ' GROUP BY revision';
        }
    } else {
        $path_str = "";
    }

    //if revision selected, and more to where clause
    if (isset($_rev_id) && $_rev_id != '') {
        $commit_str = " AND svn_commits.revision='" . db_ei($_rev_id) . "' ";
    } else {
        $commit_str = '';
    }

    if (isset($_commiter) && $_commiter && ($_commiter != 100)) {
        $commiter_str = " AND svn_commits.whoid='" . db_ei($um->getUserByUserName($_commiter)->getId()) . "' ";
    } else {
        //no assigned to was chosen, so don't add it to where clause
        $commiter_str = '';
    }

    if (isset($_srch) && $_srch != '') {
        $srch_str = " AND svn_commits.description like '%" . db_es(htmlspecialchars($_srch)) . "%'";
    } else {
        $srch_str = "";
    }

    $where .= $commiter_str . $commit_str . $srch_str . $path_str;

    $limit = '';
    if (! isset($pv) || ! $pv) {
        $limit = " LIMIT " . db_ei($offset) . "," . db_ei($chunksz);
    }

    if (empty($order_by)) {
        $order_by_sql = " ORDER BY revision DESC ";
    } else {
        $order_by_sql  = ' ORDER BY ';
        $order_by_sql .= implode(
            ',',
            array_map(
                static function (array $order_by_row) {
                    return $order_by_row['column'] . ' ' . $order_by_row['order'];
                },
                $order_by
            )
        );
        $order_by_sql .= ' ';
    }

    $sql = $select . $from . $where . $group_by . $order_by_sql . $limit;
    //echo $sql."<br>\n";
    $result = db_query($sql);

    // Compute the number of rows.
    $totalrows = -1;
    if ($foundRows) {
        $sql1    = 'SELECT FOUND_ROWS() as nb';
        $result1 = db_query($sql1);
        if ($result1 && ! db_error()) {
            $row1      = db_fetch_array($result1);
            $totalrows = $row1['nb'];
        }
    }

    return [$result, $totalrows];
}
