<?php
/**
 * Copyright (c) Enalean, 2019-Present. All rights reserved
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


$HTML->header(['title' => $Language->getText('account_deleted', 'title')]);
list($host,$port) = explode(':', ForgeConfig::get('sys_default_domain'));
?>

<P><B><?php echo $Language->getText('account_deleted', 'title'); ?></B>

<P><?php echo $Language->getText('account_deleted', 'message', [ForgeConfig::get('sys_email_contact'), ForgeConfig::get('sys_email_contact')]); ?>

<?php
$HTML->footer([]);

?>
