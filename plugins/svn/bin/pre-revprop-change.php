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

require_once 'pre.php';
require_once __DIR__.'/../include/svnPlugin.class.php';

use Tuleap\Svn\AccessControl\AccessFileHistoryDao;
use Tuleap\Svn\AccessControl\AccessFileHistoryFactory;
use Tuleap\Svn\Admin\Destructor;
use Tuleap\Svn\Dao;
use Tuleap\Svn\Hooks\PreRevpropChange;
use Tuleap\Svn\Repository\HookConfigRetriever;
use Tuleap\Svn\Repository\HookConfigSanitizer;
use Tuleap\Svn\Repository\HookDao;
use Tuleap\Svn\Repository\RepositoryManager;
use Tuleap\Svn\SvnAdmin;
use Tuleap\Svn\SvnLogger;

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
            new SvnAdmin(new System_Command(), new SvnLogger(), Backend::instance(Backend::SVN)),
            new SvnLogger(),
            new System_Command(),
            new Destructor(
                new Dao(),
                new SvnLogger()
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
    fwrite (STDERR, $exception->getMessage());
    exit(1);
}
