<?php
// Copyright (c) Enalean, 2016-Present. All Rights Reserved.
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
require_once __DIR__ . '/commit_utils.php';

$request  = HTTPRequest::instance();
$group_id = $request->get('group_id');

if (!$group_id) {
    exit_no_group(); // need a group_id !!!
}


session_require(array('group' => $group_id,'admin_flags' => 'A'));

commits_header(array(
    'title' => $GLOBALS['Language']->getText('cvs_admin_commit', 'title'),
    'help'  => 'cvs.html#cvs-administration',
    'group' => $group_id
));

// get project name
$sql = "SELECT unix_group_name, cvs_tracker, cvs_watch_mode, cvs_events_mailing_list, cvs_events_mailing_header, cvs_preamble, cvs_is_private from groups where group_id=" . db_ei($group_id);

$result = db_query($sql);
$projectname = db_result($result, 0, 'unix_group_name');
$cvs_tracked = db_result($result, 0, 'cvs_tracker');
$cvs_watch_mode = db_result($result, 0, 'cvs_watch_mode');
$cvs_mailing_list = db_result($result, 0, 'cvs_events_mailing_list');
$cvs_mailing_header = db_result($result, 0, 'cvs_events_mailing_header');
$cvs_preamble = db_result($result, 0, 'cvs_preamble');
$cvs_is_private = db_result($result, 0, 'cvs_is_private');

if ($cvs_mailing_list == 'NULL') {
    $cvs_mailing_list = '';
}
$custom_mailing_header = $cvs_mailing_header;

if ($cvs_mailing_header == 'NULL') {
    $custom_mailing_header = "";
}

$project = ProjectManager::instance()->getProject($group_id);
$checked  = $project->isPublic() && !$cvs_is_private ? '' : 'checked="true"';
$readonly = $project->isPublic() ? '' : 'readonly="true" disabled="true"';

echo "<h2>" . $GLOBALS['Language']->getText('cvs_admin_commit', 'title') . "</h2>";

echo '<FORM ACTION="?" METHOD="GET">
	<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="' . $group_id . '">
	<INPUT TYPE="HIDDEN" NAME="func" VALUE="setAdmin">
	<h3>' . $GLOBALS['Language']->getText('cvs_admin_commit', 'private_hdr') . '</h3>
    <p>
    <label class="checkbox" for="cvs_private"><input type="hidden" name="private" ' . $checked . ' ' . $readonly . ' value="0" />
    <input type="checkbox" name="private" ' . $checked . ' ' . $readonly . ' value="1" id="cvs_private" />
    ' . $GLOBALS['Language']->getText('cvs_admin_commit', 'private_lbl') . '</label>';
if (!$project->isPublic()) {
    echo '<br /><em>' . $GLOBALS['Language']->getText('cvs_admin_commit', 'private_private_msg') . '</em>';
}
    echo '</p>
	<h3>' . $GLOBALS['Language']->getText('cvs_admin_commit', 'tracking_hdr') .
'</H3><p>' . $GLOBALS['Language']->getText('cvs_admin_commit', 'tracking_msg', array($GLOBALS['sys_name'])) .
        '<p>' . $GLOBALS['Language']->getText('cvs_admin_commit', 'tracking_lbl') .
        '&nbsp;&nbsp;&nbsp;&nbsp;<SELECT name="tracked"> ' .
        '<OPTION VALUE="1"' . (($cvs_tracked == '1') ? ' SELECTED' : '') . '>' . $GLOBALS['Language']->getText('global', 'on') . '</OPTION>' .
        '<OPTION VALUE="0"' . (($cvs_tracked == '0') ? ' SELECTED' : '') . '>' . $GLOBALS['Language']->getText('global', 'off') . '</OPTION>' .
    '</SELECT></p>' .
    '<h3>' . $GLOBALS['Language']->getText('cvs_admin_commit', 'watches_hdr') .
    '</H3><p>' . $GLOBALS['Language']->getText('cvs_admin_commit', 'watches_msg') .
        '<p>' . $GLOBALS['Language']->getText('cvs_admin_commit', 'watches_lbl') .
        '&nbsp;&nbsp;&nbsp;&nbsp;<SELECT name="watches"> ' .
        '<OPTION VALUE="1"' . (($cvs_watch_mode == '1') ? ' SELECTED' : '') . '>' . $GLOBALS['Language']->getText('global', 'on') . '</OPTION>' .
        '<OPTION VALUE="0"' . (($cvs_watch_mode == '0') ? ' SELECTED' : '') . '>' . $GLOBALS['Language']->getText('global', 'off') . '</OPTION>' .
        '</SELECT></p>' .
        '<H3>' . $GLOBALS['Language']->getText('cvs_admin_commit', 'notif_hdr') .
        '</H3><p>' . $GLOBALS['Language']->getText('cvs_admin_commit', 'notif_msg') . '</p>' .
        '<br>' . $GLOBALS['Language']->getText('cvs_admin_commit', 'mail_to') .
         ':<br><INPUT TYPE="TEXT" SIZE="70" NAME="mailing_list" VALUE="' . $cvs_mailing_list . '">' .
        '<p>' . $GLOBALS['Language']->getText('cvs_admin_commit', 'subject') . ': <br>' .
        '<INPUT TYPE="TEXT" SIZE="30" NAME="custom_mailing_header" VALUE="' . $custom_mailing_header .
        '"></p> <h3>' . $GLOBALS['Language']->getText('cvs_admin_commit', 'preamble_hdr') .
'</h3><P>' . $GLOBALS['Language']->getText('cvs_admin_commit', 'preamble_msg', array("/cvs/?func=info&group_id=" . $group_id, $GLOBALS['sys_name'])) .
        '<p><TEXTAREA cols="70" rows="8" wrap="virtual" name="form_preamble">' . $cvs_preamble . '</TEXTAREA>';
echo '</p><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="' . $GLOBALS['Language']->getText('global', 'btn_submit') . '"></p></FORM>';

commits_footer(array());
