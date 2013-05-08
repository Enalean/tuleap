<?php
/**
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
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

try {
    require_once 'pre.php';
    require_once 'common/reference/ReferenceManager.class.php';
    require_once 'common/svn/SVN_Hooks.class.php';

    $repository = $argv[1];
    $txn        = $argv[2];

    $svn_hooks = new SVN_Hooks(ProjectManager::instance(), UserManager::instance());
    $project   = $svn_hooks->getProjectFromRepositoryPath($repository);

    if ($project->isSVNMandatoryRef()) {
        $reference_manager = ReferenceManager::instance();
        $commit_message    = $svn_hooks->getMessageFromTransaction($repository, $txn);
        if (! $reference_manager->stringContainsReferences($commit_message, $project)) {
            fwrite(STDERR, "You must make at least one reference in the commit message");
            exit(1);
        }
    }
    exit(0);
} catch (Exception $exeption) {
    fwrite (STDERR, $exeption->getMessage());
    exit(1);
}
?>
