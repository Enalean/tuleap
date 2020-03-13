<?php
/**
 * Copyright Enalean (c) 2013-2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
require_once __DIR__ . '/admin_utils.php';

$request = HTTPRequest::instance();
$request->checkUserIsSuperUser();

$fake_project        = new Project(array('group_id' => -1, 'unix_group_name' => $Language->getText('admin_generic_user', 'unix_name_template'), 'group_name' => $Language->getText('admin_generic_user', 'name_template')));
$sample_project      = new Project(array('group_id' => -1, 'unix_group_name' => 'gpig', 'group_name' => 'Guinea Pig'));
$fake_user           = new PFUser(array());

$generic_user_factory = new GenericUserFactory(
    UserManager::instance(),
    ProjectManager::instance(),
    new GenericUserDao()
);

$fake_generic_user   = $generic_user_factory->getGenericUser($fake_project, $fake_user);
$sample_generic_user = $generic_user_factory->getGenericUser($sample_project, $fake_user);

$name_css = '';
$valid_username_format = new Valid_GenericUserNameSuffix('');
if (! $valid_username_format->validate($sample_generic_user->getUnixName())) {
    $GLOBALS['Response']->addFeedback(Feedback::ERROR, $Language->getText('admin_generic_user', 'invalid_suffix', array(GenericUserFactory::CONFIG_KEY_SUFFIX)));
    $name_css = 'highlight';
}

site_admin_header(array('title' => $Language->getText('admin_generic_user', 'title'), 'main_classes' => array('tlp-framed')));
echo '<h1>' . $Language->getText('admin_generic_user', 'title') . '</h1>
      <p>' . $Language->getText('admin_generic_user', 'help') . '</p>
      <p>' . $Language->getText('admin_generic_user', 'help_update', array(GenericUserFactory::CONFIG_KEY_SUFFIX)) . '</p>
      <p>
        <label><b>' . $Language->getText('account_register', 'realname') . '</b>:</label><br />
        ' . $fake_generic_user->getRealName() . '
      </p>
      <p>
        <label class="' . $name_css . '"><b>' . $Language->getText('account_login', 'name') . '</b>:<br />
        ' . $fake_generic_user->getUnixName() . '
        <div class="help">e.g. ' . $sample_generic_user->getUnixName() . '</div>
      </p>
      <p>
        <label><b>' . $Language->getText('admin_generic_user', 'email') . '</b>:</label><br />
        <div class="help">' . $Language->getText('admin_generic_user', 'set_by_project') . '</div>
     </p>
     <p>
        <label><b>' . $Language->getText('admin_generic_user', 'password') . '</b>:</label><br />
        <div class="help">' . $Language->getText('admin_generic_user', 'set_by_project') . '</div>
     </p>
     <p><a href="/admin">' . $Language->getText('admin_generic_user', 'back') . '</a></p>';

site_admin_footer(array());
