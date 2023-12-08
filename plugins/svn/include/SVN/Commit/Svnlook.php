<?php
/**
 * Copyright Enalean (c) 2016 - present. All rights reserved.
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

namespace Tuleap\SVN\Commit;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use System_Command;
use Tuleap\SVN\Admin\ImmutableTagPresenter;
use Tuleap\SVNCore\Repository;

class Svnlook
{
    private $timeout = '/usr/bin/timeout 5s';
    private $svnlook = '/usr/bin/svnlook';
    private $system_commnd;

    public function __construct(System_Command $system_commnd)
    {
        $this->system_commnd = $system_commnd;
    }

    public function getChangedFiles(Repository $repository, $revision)
    {
        $command = $this->svnlook . ' changed -r ' . escapeshellarg($revision) . ' ' . escapeshellarg($repository->getSystemPath());
        return $this->system_commnd->exec('LANG=en_US.UTF-8 ' . $command);
    }

    public function getChangedDirectories(Repository $repository, $revision)
    {
        $command = $this->svnlook . ' dirs-changed -r ' . escapeshellarg($revision) . ' ' . escapeshellarg($repository->getSystemPath());
        return $this->system_commnd->exec('LANG=en_US.UTF-8 ' . $command);
    }

    public function getInfo(Repository $repository, $revision)
    {
        $command = $this->svnlook . ' info -r ' . escapeshellarg($revision) . ' ' . escapeshellarg($repository->getSystemPath());
        return $this->system_commnd->exec('LANG=en_US.UTF-8 ' . $command);
    }

    public function getTree(Repository $repository)
    {
        $command = $this->timeout . ' ' . $this->svnlook . ' tree --full-paths ' .
            escapeshellarg($repository->getSystemPath()) . ' | head -n' .
            escapeshellarg((string) (ImmutableTagPresenter::MAX_NUMBER_OF_FOLDERS + 1));

        return $this->system_commnd->exec('LANG=en_US.UTF-8 ' . $command);
    }

    public function getTransactionPath(Repository $repository, $transaction)
    {
        $command = $this->svnlook . ' changed -t ' . escapeshellarg($transaction) . ' ' . escapeshellarg($repository->getSystemPath());
        return $this->system_commnd->exec('LANG=en_US.UTF-8 ' . $command);
    }

    /**
     * @return array the commit message split on new lines
     */
    public function getMessageFromTransaction(Repository $repository, $transaction)
    {
        $arg_txn  = escapeshellarg($transaction);
        $arg_repo = escapeshellarg($repository->getSystemPath());
        return $this->system_commnd->exec("LANG=en_US.UTF-8 {$this->svnlook} log -t $arg_txn $arg_repo");
    }

    /**
     * @return resource|false Returns a process file pointer that should be closed with pclose()
     */
    public function getContent(Repository $repository, $transaction, $filename)
    {
        $repository_path = escapeshellarg($repository->getSystemPath());
        $transaction     = escapeshellarg($transaction);
        $filename        = escapeshellarg($filename);

        return popen("LANG=en_US.UTF-8 $this->svnlook cat -t $transaction $repository_path $filename", 'rb');
    }

    /**
     * @param resource $resource
     */
    public function closeContentResource($resource): void
    {
        pclose($resource);
    }

    /**
     * @throws ProcessFailedException
     */
    public function getFileSize(Repository $repository, string $transaction, string $path_into_repository): int
    {
        $process = new Process([$this->svnlook, 'filesize', '-t', $transaction, $repository->getSystemPath(), $path_into_repository], null, ['LANG' => 'en_US.UTF-8']);
        return (int) $process->mustRun()->getOutput();
    }
}
