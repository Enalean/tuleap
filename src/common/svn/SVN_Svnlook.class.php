<?php
/**
 * Copyright (c) Enalean, 2013 - 2017. All Rights Reserved.
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

require_once 'SVN_SvnlookException.class.php';

class SVN_Svnlook
{
    private $timeout = '/usr/bin/timeout 5s';
    private $svnlook = '/usr/bin/svnlook';

    public function getDirectoryListing(Project $project, $svn_path)
    {
        $command = 'tree --non-recursive --full-paths ' . escapeshellarg($project->getSVNRootPath()) . ' ' . escapeshellarg($svn_path);
        return $this->execute($command);
    }

    public function getTree(Project $project)
    {
        $command = 'tree --full-paths ' . escapeshellarg($project->getSVNRootPath());
        return $this->execute($command);
    }

    /**
     * @throw SVN_SvnlookException
     *
     * @return array
     */
    public function getPathLastHistory(Project $project, $svn_path)
    {
        $command = 'history --limit 1 ' . escapeshellarg($project->getSVNRootPath()) . ' ' . escapeshellarg($svn_path);
        return $this->execute($command);
    }

    /**
     * Returns transaction path
     *
     * @param int $transaction
     *
     * @throw SVN_SvnlookException
     *
     * @return array
     */
    public function getTransactionPath(Project $project, $transaction)
    {
        $command = 'changed -t ' . escapeshellarg($transaction) . ' ' . escapeshellarg($project->getSVNRootPath());
        return $this->execute($command);
    }

    /**
     * Returns revision info for a project E.g. array(
     *      lucky luke,     //author
     *      1545654656,     //datestamp
     *      16,             //log message size (in bytes)
     *      'my message',   //log message
     *  );
     *
     * @param int $revision
     *
     * @throw SVN_SvnlookException
     *
     * @return array
     */
    public function getInfo(Project $project, $revision)
    {
        $command = 'info -r ' . escapeshellarg($revision) . ' ' . escapeshellarg($project->getSVNRootPath());
        return $this->execute($command);
    }

    private function execute($command)
    {
        $output  = array();
        $ret_val = 1;
        exec("$this->timeout $this->svnlook $command 2>&1", $output, $ret_val);
        if ($ret_val == 0) {
            return $output;
        } else {
            throw new SVN_SvnlookException($command, $output, $ret_val);
        }
    }

    /**
     * @return resource|false Returns a process file pointer that should be closed with pclose()
     */
    public function getContent(Project $project, $transaction, $filename)
    {
        $repository_path = escapeshellarg($project->getSVNRootPath());
        $transaction     = escapeshellarg($transaction);
        $filename        = escapeshellarg($filename);

        return popen("$this->svnlook cat -t $transaction $repository_path $filename", 'rb');
    }
}
