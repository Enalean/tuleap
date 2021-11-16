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

use Tuleap\ConcurrentVersionsSystem\ServiceCVS;

require_once __DIR__ . '/commit_utils.php';

$request  = HTTPRequest::instance();
$group_id = $request->get('group_id');

if (! $group_id) {
    exit_no_group(); // need a group_id !!!
}


session_require(['group' => $group_id, 'admin_flags' => 'A']);

$pm      = ProjectManager::instance();
$project = $pm->getProject($group_id);
$service = $project->getService(\Service::CVS);
if (! ($service instanceof ServiceCVS)) {
    exit_error(
        $GLOBALS['Language']->getText('global', 'error'),
        _('This Project Has Turned CVS Off')
    );
}

$service->displayCVSAdminHeader($request->getCurrentUser());

// get project name
$sql = "SELECT unix_group_name, cvs_tracker, cvs_watch_mode, cvs_events_mailing_list, cvs_events_mailing_header, cvs_preamble, cvs_is_private from `groups` where group_id=" . db_ei($group_id);

$result             = db_query($sql);
$projectname        = db_result($result, 0, 'unix_group_name');
$cvs_tracked        = db_result($result, 0, 'cvs_tracker');
$cvs_watch_mode     = db_result($result, 0, 'cvs_watch_mode');
$cvs_mailing_list   = db_result($result, 0, 'cvs_events_mailing_list');
$cvs_mailing_header = db_result($result, 0, 'cvs_events_mailing_header');
$cvs_preamble       = db_result($result, 0, 'cvs_preamble');
$cvs_is_private     = db_result($result, 0, 'cvs_is_private');

if ($cvs_mailing_list == 'NULL') {
    $cvs_mailing_list = '';
}
$custom_mailing_header = $cvs_mailing_header;

if ($cvs_mailing_header == 'NULL') {
    $custom_mailing_header = "";
}

$project  = ProjectManager::instance()->getProject($group_id);
$checked  = $project->isPublic() && ! $cvs_is_private ? '' : 'checked="true"';
$readonly = $project->isPublic() ? '' : 'readonly="true" disabled="true"';

$purifier = Codendi_HTMLPurifier::instance();

echo '<FORM ACTION="?" METHOD="GET" class="cvs-admin">
	<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="' . $purifier->purify($group_id) . '">
	<INPUT TYPE="HIDDEN" NAME="func" VALUE="setAdmin">
	<h3>' . _('CVS Permissions') . '</h3>
    <p>
    <label class="checkbox" for="cvs_private"><input type="hidden" name="private" ' . $checked . ' ' . $readonly . ' value="0" />
    <input type="checkbox" name="private" ' . $checked . ' ' . $readonly . ' value="1" id="cvs_private" />
    ' . _('private repository') . '</label>';
if (! $project->isPublic()) {
    echo '<br /><em>' . _('Private projects can\'t make their repository public.') . '</em>';
}
    echo '</p>
	<h3>' . _('CVS Tracking') .
'</H3><p>' . sprintf(_('When CVS tracking is on, the commits (file change, addition and removal) are registered in the %1$s database so that they can be browsed and searched. Else commits are not logged in the database; only the CVS history is available'), ForgeConfig::get('sys_name')) .
        '<p>' . _('CVS Tracking') .
        '&nbsp;&nbsp;&nbsp;&nbsp;<SELECT name="tracked"> ' .
        '<OPTION VALUE="1"' . (($cvs_tracked == '1') ? ' SELECTED' : '') . '>' . $GLOBALS['Language']->getText('global', 'on') . '</OPTION>' .
        '<OPTION VALUE="0"' . (($cvs_tracked == '0') ? ' SELECTED' : '') . '>' . $GLOBALS['Language']->getText('global', 'off') . '</OPTION>' .
    '</SELECT></p>' .
    '<h3>' . _('CVS Watch Mode') .
    '</H3><p>' . _('CVS creates working copies of project files read-only, to remind developers to run the cvs edit command before working on them.') .
        '<p>' . _('CVS Watch Mode') .
        '&nbsp;&nbsp;&nbsp;&nbsp;<SELECT name="watches"> ' .
        '<OPTION VALUE="1"' . (($cvs_watch_mode == '1') ? ' SELECTED' : '') . '>' . $GLOBALS['Language']->getText('global', 'on') . '</OPTION>' .
        '<OPTION VALUE="0"' . (($cvs_watch_mode == '0') ? ' SELECTED' : '') . '>' . $GLOBALS['Language']->getText('global', 'off') . '</OPTION>' .
        '</SELECT></p>' .
        '<H3>' . _('E-mail notification on commits') .
        '</H3><p>' . _('Each commit event can also be notified via email to specific recipients or mailing lists (comma separated). A specific subject header for the email message can also be specified.') . '</p>' .
        '<br>' . _('Mail To') .
         ':<br><INPUT TYPE="TEXT" SIZE="70" NAME="mailing_list" VALUE="' . $cvs_mailing_list . '">' .
        '<p>' . _('Subject header') . ': <br>' .
        '<INPUT TYPE="TEXT" SIZE="30" NAME="custom_mailing_header" VALUE="' . $custom_mailing_header .
        '"></p> <h3>' . _('CVS Preamble') .
'</h3><P>' . sprintf(_('Introductory message to replace the default one on the <a href="%1$s">CVS welcome page</a>. (HTML tags allowed)'), "/cvs/?func=info&group_id=" . $purifier->purify(urlencode($group_id))) .
        '<p><TEXTAREA cols="70" rows="8" wrap="virtual" name="form_preamble">' . $cvs_preamble . '</TEXTAREA>';
echo '</p><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="' . $GLOBALS['Language']->getText('global', 'btn_submit') . '"></p></FORM>';

commits_footer([]);
