<?php
/**
 * Copyright Enalean (c) 2016. All rights reserved.
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

use Tuleap\Svn\Dao;
use Tuleap\Svn\Hooks\PreCommit;
use Tuleap\Svn\Admin\ImmutableTagFactory;
use Tuleap\Svn\Admin\ImmutableTagDao;
use Tuleap\Svn\Repository\RepositoryManager;
use Tuleap\Svn\Commit\CommitInfoEnhancer;
use Tuleap\Svn\Commit\SVNLook;
use Tuleap\Svn\Commit\CommitInfo;
use Tuleap\Svn\SvnLogger;

try {
    require_once 'pre.php';

    $repository_path = $argv[1];
    $transaction     = $argv[2];

    $hook = new PreCommit(
        new ImmutableTagFactory(new ImmutableTagDao()),
        new RepositoryManager(new Dao(), ProjectManager::instance()),
        new CommitInfoEnhancer(new SVNLook(new System_Command()), new CommitInfo()),
        new SvnLogger()
    );

    $hook->assertCommitToTagIsAllowed($repository_path, $transaction);
    exit(0);
} catch (Exception $exeption) {
    fwrite (STDERR, $exeption->getMessage());
    exit(1);
}
