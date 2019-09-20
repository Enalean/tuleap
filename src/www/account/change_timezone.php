<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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
require_once __DIR__ . '/../include/timezones.php';

$em = EventManager::instance();
$em->processEvent('before_change_timezone', array());


$request = HTTPRequest::instance();
$csrf    = new CSRFSynchronizerToken('/account/change_timezone.php');
if (!user_isloggedin()) {
    exit_not_logged_in();
}

if ($request->isPost()) {
    $csrf->check();
    if (! $request->existAndNonEmpty('timezone')) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('account_change_timezone', 'no_update'));
    } elseif (! is_valid_timezone($request->get('timezone'))) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('account_change_timezone', 'choose_tz'));
    } else {
        // if we got this far, it must be good
        db_query("UPDATE user SET timezone='" . db_es($request->get('timezone')) . "' WHERE user_id=" . db_ei(UserManager::instance()->getCurrentUser()->getId()));
        session_redirect("/account/");
    }
}

$HTML->header(array('title'=>$Language->getText('account_change_timezone', 'title')));

?>
<h2><?php echo $Language->getText('account_change_timezone', 'title2'); ?></h2>
<P>
<?php echo $Language->getText('account_change_timezone', 'message', array($GLOBALS['sys_name'])); ?>
<P>
<form action="change_timezone.php" method="post">
<?php
echo $csrf->fetchHTMLInput();
echo html_get_timezone_popup(user_get_timezone());

?>
<br>
<input type="submit" class="btn btn-primary" name="submit" value="<?php echo $Language->getText('global', 'btn_update'); ?>">
</form>

<?php

$HTML->footer(array());
