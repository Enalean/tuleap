<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
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

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../project/admin/permissions.php';
require_once __DIR__ . '/../forum/forum_utils.php';


$request = HTTPRequest::instance();

if ($request->valid(new Valid_GroupId())) {
    $group_id = $request->get('group_id');
} else {
    $group_id = null;
}

if ($request->valid(new Valid_Pv())) {
    $pv = $request->get('pv');
} else {
    $pv = 0;
}

$pm      = ProjectManager::instance();
$project = $pm->getProject($group_id);
if ($group_id) {
    $title = $Language->getText('news_index', 'news_for', $project->getPublicName());
} else {
    $title = $Language->getText('news_index', 'news');
}

news_header(\Tuleap\Layout\HeaderConfigurationBuilder::get($GLOBALS['Language']->getText('news_admin_index', 'title'))
    ->inProject($project, Service::NEWS)
    ->withPrinterVersion($pv)
    ->build());

if ($pv != 2) {
    if ($pv == 1) {
        echo '<H3>' . $Language->getText('news_index', 'news') . '</H3>';
    } else {
        echo "<TABLE width='100%'><TR><TD>";
        echo '<H3>' . $Language->getText('news_index', 'news') . '</H3>';
        echo "</TD>";
        echo "<TD align='left'> ( <A HREF='?group_id=" . Codendi_HTMLPurifier::instance()->purify(urlencode($group_id ?? '')) . "&pv=1'><img src='" . util_get_image_theme("msg.png") . "' border='0'>&nbsp;" . $Language->getText('global', 'printer_version') . "</A> ) </TD>";
        echo "</TR></TABLE>";
    }

    echo '<P>' . $Language->getText('news_index', 'choose_news') . '<P>';
} else {
    echo '<P>';
}

/*
    Put the result set (list of forums for this group) into a column with folders
*/
if ($group_id && ($group_id != ForgeConfig::get('sys_news_group'))) {
    $sql = "SELECT * FROM news_bytes WHERE group_id=" . db_ei($group_id) . " AND is_approved <> '4' ORDER BY date DESC";
} else {
    $sql = "SELECT * FROM news_bytes WHERE is_approved='1' ORDER BY date DESC";
}

$result = db_query($sql);
$rows   = db_numrows($result);

$purifier = Codendi_HTMLPurifier::instance();

if ($rows < 1) {
    echo '<H2>' . $Language->getText('news_index', 'no_news_found');
    if ($group_id) {
        echo $purifier->purify(' ' . $Language->getText('news_index', 'for', $project->getPublicName()));
    }
    echo '</H2>';
    echo '
		<P>' . $Language->getText('news_index', 'no_items_found');
} else {
    echo '<table WIDTH="100%" border=0>
		<TR><TD VALIGN="TOP">';

    for ($j = 0; $j < $rows; $j++) {
        $forum_id = db_result($result, $j, 'forum_id');
        if (news_check_permission($forum_id, $group_id)) {
            if ($group_id) {
                echo '
		<A HREF="/forum/forum.php?forum_id=' . $purifier->purify(urlencode(db_result($result, $j, 'forum_id'))) .
                '&group_id=' . $purifier->purify(urlencode($group_id)) .
                '"><IMG SRC="' . util_get_image_theme("ic/cfolder15.png") . '" HEIGHT=13 WIDTH=15 BORDER=0> &nbsp;' .
                $purifier->purify(db_result($result, $j, 'summary')) . '</A> ';
            } else {
                echo '
		  <A HREF="/forum/forum.php?forum_id=' . $purifier->purify(urlencode(db_result($result, $j, 'forum_id'))) .
                '"><IMG SRC="' . util_get_image_theme("ic/cfolder15.png") . '" HEIGHT=13 WIDTH=15 BORDER=0> &nbsp;' .
                $purifier->purify(db_result($result, $j, 'summary')) . '</A> ';
            }
            echo '
		<BR>';
        }
    }

    echo '
	</TD></TR></TABLE>';
}

// Display footer page
news_footer([]);
