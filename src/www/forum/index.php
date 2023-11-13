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
} else {
    $group_id = $request->get('group_id');
}

if ($request->valid(new Valid_Pv())) {
    $pv = $request->get('pv');
} else {
    $pv = 0;
}


$pm      = ProjectManager::instance();
$project = $pm->getProject($group_id);
$title   = sprintf(_('Forums for \'%1$s\''), $project->getPublicName());

forum_header(\Tuleap\Layout\HeaderConfigurationBuilder::get($title)
    ->inProject($project, Service::FORUM)
    ->withPrinterVersion((int) $pv)
    ->build());


if (user_isloggedin() && user_ismember($group_id)) {
    $public_flag = '<3';
} else {
    $public_flag = '=1';
}

$sql = "SELECT forum_group_list.group_forum_id, forum_group_list.forum_name, forum_group_list.description, COUNT(forum.msg_id) as total
    FROM forum_group_list
    LEFT JOIN forum ON forum_group_list.group_forum_id = forum.group_forum_id
    WHERE forum_group_list.group_id='" . db_ei($group_id) . "' AND forum_group_list.is_public $public_flag
    GROUP BY forum_group_list.group_forum_id;";

$result = db_query($sql);

$rows = db_numrows($result);

$purifier = Codendi_HTMLPurifier::instance();

if (! $result || $rows < 1) {
    $pm = ProjectManager::instance();
    echo '<H1>' . $purifier->purify(sprintf(_('No forums found for \'%1$s\''), $project->getPublicName())) . '</H1>';
    echo db_error();
    forum_footer();
    exit;
}

if (isset($pv) && $pv) {
    echo '<H3>' . _('Discussion Forums') . '</H3>';
} else {
    echo "<TABLE width='100%'><TR><TD>";
    echo '<H3>' . _('Discussion Forums') . '</H3>';
    echo "</TD>";
        echo "<TD align='left'> ( <A HREF='?group_id=" . $purifier->purify(urlencode($group_id)) . "&pv=1'><img src='" . util_get_image_theme("msg.png") . "' border='0'>&nbsp;" . $Language->getText('global', 'printer_version') . "</A> ) </TD>";
    echo "</TR></TABLE>";
}

echo '<P>' . _('Choose a forum and you can browse, search, and post messages.') . '<P>';

/*
  Put the result set (list of forums for this group) into a column with folders
*/

for ($j = 0; $j < $rows; $j++) {
    echo '<A HREF="forum.php?forum_id=' . $purifier->purify(urlencode(db_result($result, $j, 'group_forum_id'))) . '">' .
        html_image("ic/cfolder15.png", ["border" => "0"]) .
        '&nbsp;' .
        $purifier->purify(html_entity_decode(db_result($result, $j, 'forum_name'))) . '</A> ';
    //message count
    $total_msg = (int) db_result($result, $j, 'total');
    echo '(' . $purifier->purify(sprintf(dngettext('tuleap-core', '%d message', '%d messages', $total_msg), $total_msg)) . ')';
    echo "<BR>\n";
    echo $purifier->purify(html_entity_decode(db_result($result, $j, 'description'))) . '<P>';
}
// Display footer page
forum_footer();
