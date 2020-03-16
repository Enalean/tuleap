<?php
/**
 * Copyright (c) Enalean, 2013-2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */


require_once __DIR__ . '/../../include/pre.php';

// Valid group id
$valid_groupid = new Valid_GroupId();
$valid_groupid->required();
if (! $request->valid($valid_groupid)) {
    exit_error(
        $Language->getText('project_admin_index', 'invalid_p'),
        $Language->getText('project_admin_index', 'p_not_found')
    );
}
$group_id = $request->get('group_id');

//must be a project admin
session_require(array('group' => $group_id, 'admin_flags' => 'A'));

$user_manager         = UserManager::instance();
$generic_user_factory = new GenericUserFactory($user_manager, ProjectManager::instance(), new GenericUserDao());
$generic_user         = $generic_user_factory->fetch($group_id);

$redirect_url = '/project/admin/editgenericmember.php?group_id=' . (int) $group_id;
$token = new CSRFSynchronizerToken($redirect_url);

if ($request->get('update_generic_user')) {
    $token->check();

    $password = $request->getValidated('password');
    $email    = $request->getValidated('email');

    if ($password) {
        $generic_user->setPassword($password);
    }
    $generic_user->setEmail($email);

    if ($user_manager->updateDb($generic_user)) {
        $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin', 'generic_member_updated'));
    } else {
        $GLOBALS['Response']->addFeedback('warning', $Language->getText('project_admin', 'generic_member_not_changed'));
    }

    $GLOBALS['HTML']->redirect($redirect_url);
}

$hp    = Codendi_HTMLPurifier::instance();
$title = $Language->getText('project_admin', 'generic_member_settings');
project_admin_header(
    array(
        'title' => $title,
        'group' => $group_id,
        'help'  => 'project-admin.html'
    ),
    'members'
);

echo '<h2>' . $title . '</h2>';
echo '<form method="POST" action="">';
echo $token->fetchHTMLInput();
echo '<input type="hidden" name="group_id" value="' . (int) $group_id . '" />
    <p>
        <label for="newtracker_login"><b>' . $Language->getText('account_login', 'name') . '</b>:<br />
        ' . $generic_user->getUserName() . '
    </p>
    <p>
        <label for="newtracker_real_name"><b>' . $Language->getText('account_register', 'realname') . '</b>:</label><br />
        ' . $generic_user->getRealName() . '
    </p>
    <p>
        <label for="generic_user_email"><b>' . _('Change email address') . '</b>:</label><br />
        <input type="email" name="email" id="generic_user_email" value="' .
            $hp->purify($generic_user->getEmail(), CODENDI_PURIFIER_CONVERT_HTML) . '" />
     </p>
     <p>
        <label for="generic_user_password"><b>' . $Language->getText('admin_user_changepw', 'password_field_label') . '</b>:</label><br />
        <input type="password" name="password" id="generic_user_password" value="" autocomplete="off" /><br />
        <span class="help">' . $Language->getText('project_admin', 'generic_member_leave_blank') . '</span>
     </p>
     <input type="submit" name="update_generic_user" value="' . $Language->getText('global', 'btn_submit') . '" />
</form>';

project_admin_footer(array());
