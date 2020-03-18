<?php
/**
 * Copyright Enalean (c) 2013 - Present. All rights reserved.
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

use Tuleap\Svn\SHA1CollisionDetector;

try {
    /** @psalm-suppress MissingFile This file is deployed under /usr/lib/tuleap/, an absolute path is needed */
    require_once '/usr/share/tuleap/src/www/include/pre.php';

    $repository = $argv[1];
    $txn        = $argv[2];

    $svn_hooks      = new SVN_Hooks(ProjectManager::instance(), UserManager::instance());
    $commit_message = $svn_hooks->getMessageFromTransaction($repository, $txn);

    $hook = new SVN_Hook_PreCommit(
        $svn_hooks,
        new SVN_CommitMessageValidator(ReferenceManager::instance()),
        new SVN_Svnlook(),
        new SVN_Immutable_Tags_Handler(new SVN_Immutable_Tags_DAO()),
        new SHA1CollisionDetector(),
        BackendLogger::getDefaultLogger()
    );
    $hook->assertCommitMessageIsValid($repository, $commit_message);
    $hook->assertCommitToTagIsAllowed($repository, $txn);
    $hook->assertCommitDoesNotContainSHA1Collision($repository, $txn);
    exit(0);
} catch (Exception $exeption) {
    fwrite(STDERR, $exeption->getMessage());
    exit(1);
}
