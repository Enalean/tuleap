<?php
/**
 * Copyright (c) Enalean, 2013-2019. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
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

header("Cache-Control: no-cache, no-store, must-revalidate");

require_once __DIR__ . '/../include/pre.php';


$em = EventManager::instance();
$em->processEvent('before_lostpw', array());

$HTML->header(array('title' => $Language->getText('account_lostpw', 'title')));

?>

<h2><?php echo $Language->getText('account_lostpw', 'title'); ?></h2>
<P><?php echo $Language->getText('account_lostpw', 'message'); ?></P>

<FORM action="lostpw-confirm.php" method="post" class="form-inline">
<P>
Login Name:
<INPUT type="text" name="form_loginname" autocomplete="username">
<INPUT class="btn btn-primary" type="submit" name="Send Lost Password Hash" value="<?php echo $Language->getText('account_lostpw', 'send_hash'); ?>">
</FORM>

<P><A href="/">[<?php echo $Language->getText('global', 'back_home'); ?>]</A>

<?php
$HTML->footer(array());
