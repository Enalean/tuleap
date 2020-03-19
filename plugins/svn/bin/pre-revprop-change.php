#!/usr/share/tuleap/src/utils/php-launcher.sh
<?php
/**
 * Copyright Enalean (c) 2016 - 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
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

require_once __DIR__ . '/../../../src/www/include/pre.php';
require_once __DIR__ . '/../include/svnPlugin.php';

use Tuleap\SVN\AccessControl\AccessFileHistoryDao;
use Tuleap\SVN\AccessControl\AccessFileHistoryFactory;
use Tuleap\SVN\Repository\Destructor;
use Tuleap\SVN\Dao;
use Tuleap\SVN\Hooks\PreRevpropChange;
use Tuleap\SVN\Repository\HookConfigRetriever;
use Tuleap\SVN\Repository\HookConfigSanitizer;
use Tuleap\SVN\Repository\HookDao;
use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\SVN\SvnAdmin;

try {
    $repository         = $argv[1];
    $propname           = $argv[4];
    $action             = $argv[5];
    $new_commit_message = stream_get_contents(STDIN);

    $hook = new PreRevpropChange(
        $repository,
        $action,
        $propname,
        $new_commit_message,
        new RepositoryManager(
            new Dao(),
            ProjectManager::instance(),
            new SvnAdmin(new System_Command(), SvnPlugin::getLogger(), Backend::instance(Backend::SVN)),
            SvnPlugin::getLogger(),
            new System_Command(),
            new Destructor(
                new Dao(),
                SvnPlugin::getLogger()
            ),
            EventManager::instance(),
            Backend::instance(Backend::SVN),
            new AccessFileHistoryFactory(new AccessFileHistoryDao())
        ),
        new HookConfigRetriever(new HookDao(), new HookConfigSanitizer())
    );

    $hook->checkAuthorized(ReferenceManager::instance());

    exit(0);
} catch (Exception $exception) {
    fwrite(STDERR, $exception->getMessage());
    exit(1);
}
