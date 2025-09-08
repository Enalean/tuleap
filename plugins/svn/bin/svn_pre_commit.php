<?php
/**
 * Copyright Enalean (c) 2016 - Present. All rights reserved.
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

declare(strict_types=1);

require_once __DIR__ . '/../../../src/www/include/pre.php';
require_once __DIR__ . '/../include/svnPlugin.php';

use Tuleap\SVN\AccessControl\AccessFileHistoryDao;
use Tuleap\SVN\AccessControl\AccessFileHistoryFactory;
use Tuleap\SVN\Commit\CommitMessageValidator;
use Tuleap\SVN\Commit\FileSizeValidator;
use Tuleap\SVN\Commit\ImmutableTagCommitValidator;
use Tuleap\SVN\Repository\Destructor;
use Tuleap\SVN\Admin\ImmutableTagDao;
use Tuleap\SVN\Admin\ImmutableTagFactory;
use Tuleap\SVN\Commit\Svnlook;
use Tuleap\SVN\Dao;
use Tuleap\SVN\Hooks\PreCommit;
use Tuleap\SVN\Repository\HookConfigRetriever;
use Tuleap\SVN\Repository\HookConfigSanitizer;
use Tuleap\SVN\Repository\HookDao;
use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\SVN\SvnAdmin;

$logger          = SvnPlugin::getLogger();
$repository_path = $argv[1];
$transaction     = $argv[2];
try {
    $svnlook            = new Svnlook(new System_Command());
    $repository_manager = new RepositoryManager(
        new Dao(),
        ProjectManager::instance(),
        new SvnAdmin(new System_Command(), $logger, \Tuleap\SVN\BackendSVN::instance()),
        $logger,
        new System_Command(),
        new Destructor(
            new Dao(),
            $logger,
        ),
        EventManager::instance(),
        \Tuleap\SVN\BackendSVN::instance(),
        new AccessFileHistoryFactory(new AccessFileHistoryDao()),
    );
    $hook               = new PreCommit(
        $svnlook,
        $logger,
        new CommitMessageValidator(
            new HookConfigRetriever(
                new HookDao(),
                new HookConfigSanitizer()
            ),
            ReferenceManager::instance(),
        ),
        new ImmutableTagCommitValidator(
            $logger,
            new ImmutableTagFactory(
                new ImmutableTagDao()
            )
        ),
        new FileSizeValidator(
            $svnlook,
            $logger,
        ),
    );

    $hook->assertCommitIsValid(
        $repository_manager->getRepositoryFromSystemPath($repository_path),
        $transaction,
    );

    exit(0);
} catch (Exception $exception) {
    $logger->error($repository_path . ': ' . $exception->getMessage(), ['exception' => $exception]);
    fwrite(STDERR, $exception->getMessage());
    exit(1);
}
