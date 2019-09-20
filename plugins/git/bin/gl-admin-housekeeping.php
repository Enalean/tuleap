<?php
/**
 * Copyright (c) Enalean, 2013-2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once __DIR__ . '/../../../src/www/include/pre.php';
require_once __DIR__ . '/../include/gitPlugin.php';

$gitolite_var_path       = $GLOBALS['sys_data_dir'] . '/gitolite';
$remote_admin_repository = 'gitolite@gl-adm:gitolite-admin';

$git_plugin = PluginManager::instance()->getPluginByName('git');
$logger     = $git_plugin->getLogger();

$runner = new Git_GitoliteHousekeeping_GitoliteHousekeepingRunner(
    new SystemEventProcessManager(),
    new SystemEventProcessRootDefaultQueue(),
    new Git_GitoliteHousekeeping_GitoliteHousekeepingDao(),
    new Git_GitoliteHousekeeping_GitoliteHousekeepingResponse($logger),
    new BackendService(),
    $gitolite_var_path,
    $remote_admin_repository
);

$runner->run();
