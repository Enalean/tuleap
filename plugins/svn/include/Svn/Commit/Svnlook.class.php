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

namespace Tuleap\Svn\Commit;
use System_Command;


use Tuleap\Svn\Repository\Repository;

class Svnlook {
    private $svnlook = '/usr/bin/svnlook';
    private $system_commnd;

    public function __construct(System_Command $system_commnd) {
        $this->system_commnd = $system_commnd;
    }

    public function getChangedFiles(Repository $repository, $revision) {
        $command = $this->svnlook.' changed -r ' . escapeshellarg($revision) . ' ' . escapeshellarg($repository->getSystemPath());
        return $this->system_commnd->exec($command);
    }

    public function getChangedDirectories(Repository $repository, $revision) {
        $command = $this->svnlook.' dirs-changed -r ' . escapeshellarg($revision) . ' ' . escapeshellarg($repository->getSystemPath());
        return $this->system_commnd->exec($command);
    }

    public function getInfo(Repository $repository, $revision) {
        $command = $this->svnlook.' info -r ' . escapeshellarg($revision) . ' ' . escapeshellarg($repository->getSystemPath());
        return $this->system_commnd->exec($command);
    }

    public function getTree(Repository $repository) {
        $command = $this->svnlook.' tree --full-paths '.escapeshellarg($repository->getSystemPath());
        return $this->system_commnd->exec($command);
    }

    public function getTransactionPath(Repository $repository, $transaction) {
        $command = $this->svnlook.' changed -t ' . escapeshellarg($transaction) . ' ' .escapeshellarg($repository->getSystemPath());
        return $this->system_commnd->exec($command);
    }
}