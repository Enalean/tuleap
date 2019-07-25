<?php
/**
 * Copyright (c) Enalean, 2013-2019. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

require_once __DIR__ . '/../include/pre.php';

$em      = EventManager::instance();
$um      = UserManager::instance();
$request = HTTPRequest::instance();

$em->processEvent('before_change_realname', array());

$csrf = new CSRFSynchronizerToken('/account/change_realname.php');

$user = $um->getCurrentUser();

if ($request->isPost() && $request->existAndNonEmpty('form_realname')) {
    $csrf->check();

    $user->setRealName($request->get('form_realname'));
    $um->updateDb($user);

    $GLOBALS['Response']->redirect("/account/");
    exit;
}

$HTML->header(array('title'=>$Language->getText('account_change_realname', 'title')));

$hp = Codendi_HTMLPurifier::instance();
?>
<h2><?php echo $Language->getText('account_change_realname', 'title'); ?></h2>
<form action="change_realname.php" method="post">
<?php
echo $csrf->fetchHTMLInput();
echo $Language->getText('account_change_realname', 'new_name'); ?>:
<br><input type="text" name="form_realname" class="textfield_medium" value="<?php echo $hp->purify($user->getRealname(), CODENDI_PURIFIER_CONVERT_HTML) ?>" autocomplete="name"/>
<br>
<input class="btn btn-primary" type="submit" name="Update" value="<?php echo $Language->getText('global', 'btn_update'); ?>">
</form>

<?php

$HTML->footer(array());
