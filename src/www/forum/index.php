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
require_once __DIR__ . '/../forum/forum_utils.php';

$request = HTTPRequest::instance();

$valid_project_id = new Valid_GroupId();
$valid_project_id->required();
if (! $request->valid($valid_project_id)) {
    exit_no_group();
    exit();
} else {
    $group_id = $request->get('group_id');
}

if ($request->valid(new Valid_Pv())) {
    $pv = $request->get('pv');
} else {
    $pv = 0;
}


$pm = ProjectManager::instance();
$params = ['title' => $Language->getText('forum_index', 'forums_for', $pm->getProject($group_id)->getPublicName()),
              'help' => 'collaboration.html#web-forums',
              'pv'   => isset($pv) ? $pv : false];
forum_header($params);


if (user_isloggedin() && user_ismember($group_id)) {
    $public_flag = '<3';
} else {
    $public_flag = '=1';
}

$sql = "SELECT g.group_forum_id,g.forum_name, g.description, famc.count as total
    FROM forum_group_list g
    LEFT JOIN forum_agg_msg_count famc USING (group_forum_id)
    WHERE g.group_id='" . db_ei($group_id) . "' AND g.is_public $public_flag;";

$result = db_query($sql);

$rows = db_numrows($result);

$purifier = Codendi_HTMLPurifier::instance();

if (! $result || $rows < 1) {
    $pm = ProjectManager::instance();
    echo '<H1>' . $purifier->purify($Language->getText('forum_index', 'no_forums', $pm->getProject($group_id)->getPublicName())) . '</H1>';
    echo db_error();
    forum_footer($params);
    exit;
}

if (isset($pv) && $pv) {
    echo '<H3>' . $Language->getText('forum_forum_utils', 'discuss_forum') . '</H3>';
} else {
    echo "<TABLE width='100%'><TR><TD>";
    echo '<H3>' . $Language->getText('forum_forum_utils', 'discuss_forum') . '</H3>';
    echo "</TD>";
        echo "<TD align='left'> ( <A HREF='?group_id=" . $purifier->purify(urlencode($group_id)) . "&pv=1'><img src='" . util_get_image_theme("msg.png") . "' border='0'>&nbsp;" . $Language->getText('global', 'printer_version') . "</A> ) </TD>";
    echo "</TR></TABLE>";
}

echo '<P>' . $Language->getText('forum_index', 'choose_forum') . '<P>';

/*
  Put the result set (list of forums for this group) into a column with folders
*/

for ($j = 0; $j < $rows; $j++) {
    echo '<A HREF="forum.php?forum_id=' . $purifier->purify(urlencode(db_result($result, $j, 'group_forum_id'))) . '">' .
        html_image("ic/cfolder15.png", ["border" => "0"]) .
        '&nbsp;' .
        $purifier->purify(html_entity_decode(db_result($result, $j, 'forum_name'))) . '</A> ';
    //message count
    echo '(' . $purifier->purify((db_result($result, $j, 'total')) ? db_result($result, $j, 'total') : '0') . ' msgs)';
    echo "<BR>\n";
    echo $purifier->purify(html_entity_decode(db_result($result, $j, 'description'))) . '<P>';
}
// Display footer page
forum_footer($params);
