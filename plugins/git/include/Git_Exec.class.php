<?php
/**
 * Copyright (c) Enalean, 2012-2019. All Rights Reserved.
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

/**
 * Wrap access to git commands
 */
class Git_Exec
{
    public const GIT212_PATH = '/opt/rh/sclo-git212/root';

    public const TRANSPORT_EXT  = 'ext';
    public const TRANSPORT_FILE = 'file';

    private $work_tree;
    private $git_dir;
    private $git_cmd;
    private $allowedTransports = [];

    /**
     * @param String $work_tree The git repository path where we should operate
     */
    public function __construct($work_tree, $git_dir = null)
    {
        if (! $git_dir) {
            $this->setWorkTree($work_tree);
        } else {
            $this->setWorkTreeAndGitDir($work_tree, $git_dir);
        }
        $this->git_cmd = self::getGitCommand();
    }

    /**
     * @param String $work_tree
     */
    public function setWorkTree($work_tree)
    {
        $this->work_tree = $work_tree;
        $this->git_dir   = $this->work_tree . '/.git';
    }

    /**
     * @param String $work_tree
     * @param String $git_dir
     */
    public function setWorkTreeAndGitDir($work_tree, $git_dir)
    {
        $this->work_tree = $work_tree;
        $this->git_dir   = $git_dir;
    }

    public static function buildFromRepository(GitRepository $repository)
    {
        return new static($repository->getFullPath(), $repository->getFullPath());
    }

    public static function isGit212Installed()
    {
        return is_file(self::GIT212_PATH . '/usr/bin/git');
    }

    /**
     * Whitelist usage of 'ext' protocol
     *
     * Starting git 2.12 exotic protocols are disabled by default by git client. 'ext' (used by gerrit to manage the
     * choice of ssh keys @see Git_RemoteServer_GerritServer::getCloneSSHUrl) should be explicitely whitelisted then.
     *
     * @see https://github.com/git/git/commit/f1762d772e9b415a3163abf5f217fc3b71a3b40e
     */
    public function allowUsageOfExtProtocol()
    {
        $this->allowedTransports[] = self::TRANSPORT_EXT;
    }

    public function allowUsageOfFileProtocol()
    {
        $this->allowedTransports[] = self::TRANSPORT_FILE;
    }

    public function init()
    {
        $this->gitCmd('init');
    }

    public function setLocalCommiter($name, $email)
    {
        $this->gitCmd('config --add user.name ' . escapeshellarg($name));
        $this->gitCmd('config --add user.email ' . escapeshellarg($email));
    }

    public function remoteAdd($remote)
    {
        $this->gitCmd('remote add origin ' . escapeshellarg($remote));
    }

    public function pullBranch($remote, $branch)
    {
        $this->gitCmd('pull --quiet ' . $remote . ' ' . $branch);
    }

    public function checkoutBranch($branch)
    {
        $this->gitCmd('checkout --quiet ' . $branch);
    }

    public function configFile($file, $config)
    {
        $this->gitCmd('config -f ' . escapeshellarg($file) . ' ' . $config);
    }

    /**
     * git help mv
     *
     * @param string $from
     * @param string $to
     *
     * @return bool
     * @throw Git_Command_Exception
     */
    public function mv($from, $to)
    {
        $to_name = basename($to);
        $to_path = realpath(dirname($to)) . '/' . $to_name;
        $cmd     = 'mv ' . escapeshellarg(realpath($from)) . ' ' . escapeshellarg($to_path);
        return $this->gitCmd($cmd);
    }

    /**
     * git help add
     *
     * @param string $file
     *
     * @return bool
     * @throw Git_Command_Exception
     */
    public function add($file)
    {
        $cmd = 'add ' . escapeshellarg(realpath($file));
        return $this->gitCmd($cmd);
    }

    /**
     * git help rm
     *
     * @param string $file
     *
     * @return bool
     * @throw Git_Command_Exception
     */
    public function rm($file)
    {
        if ($this->canRemove($file)) {
            $cmd = 'rm ' . escapeshellarg(realpath($file));
            return $this->gitCmd($cmd);
        }
        return true;
    }

    public function recursiveRm($file)
    {
        if ($this->canRemove($file)) {
            $cmd = 'rm -r ' . escapeshellarg(realpath($file));
            return $this->gitCmd($cmd);
        }
        return true;
    }

    private function canRemove($file)
    {
        $output = array();
        $this->gitCmdWithOutput('status --porcelain ' . escapeshellarg(realpath($file)), $output);
        return count($output) == 0;
    }

    /**
     * List all commits between the two revisions
     *
     * @param String $oldrev
     * @param String $newrev
     *
     * @return array of String sha1 of each commit
     */
    public function revList($oldrev, $newrev)
    {
        $output = array();
        $this->gitCmdWithOutput('rev-list ' . escapeshellarg($oldrev) . '..' . escapeshellarg($newrev), $output);
        return $output;
    }

    /**
     * List all commits in the new branch that doesnt already belong to the repository
     *
     * @param String $refname Branch name
     * @param String $newrev  Commit sha1
     *
     * @return array of String sha1 of each commit
     */
    public function revListSinceStart($refname, $newrev)
    {
        $output = array();
        $other_branches = implode(' ', array_map('escapeshellarg', $this->getOtherBranches($refname)));
        $this->gitCmdWithOutput('rev-parse --not ' . $other_branches . ' | git rev-list --stdin ' . escapeshellarg($newrev), $output);
        return $output;
    }

    private function getOtherBranches($refname)
    {
        $branches = $this->getAllBranches();
        foreach ($branches as $key => $branch) {
            if ($branch == $refname) {
                unset($branches[$key]);
            }
        }
        return $branches;
    }

    private function getAllBranches(): array
    {
        $output = array();
        $this->gitCmdWithOutput("for-each-ref --format='%(refname)' refs/heads", $output);
        return $output;
    }

    /**
     * Return content of an object
     *
     * @param String $rev
     *
     * @return String
     */
    public function catFile($rev)
    {
        $output = array();
        $this->gitCmdWithOutput('cat-file -p ' . escapeshellarg($rev), $output);
        return implode(PHP_EOL, $output);
    }

    /**
     * Return the object type (commit, tag, etc);
     *
     * @param String $rev
     * @throws Git_Command_UnknownObjectTypeException
     * @return String
     */
    public function getObjectType($rev)
    {
        $output = array();
        $this->gitCmdWithOutput('cat-file -t ' . escapeshellarg($rev), $output);
        if (count($output) == 1) {
            return $output[0];
        }
        throw new Git_Command_UnknownObjectTypeException();
    }

    public function doesObjectExists($rev)
    {
        $output = array();
        try {
            $this->gitCmdWithOutput('cat-file -e ' . escapeshellarg($rev), $output);
        } catch (Git_Command_Exception $exception) {
            return false;
        }
        return true;
    }

    /**
     * @return int
     */
    public function getLargestObjectSize()
    {
        $output = [];
        $this->gitCmdWithOutput(
            'cat-file --batch-check=\'%(objectsize)\' --batch-all-objects | sort -rn 2> /dev/null | head -1',
            $output
        );

        if (empty($output)) {
            return 0;
        }
        return (int) $output[0];
    }

    /**
     * git help commit
     *
     * Commit only if there is something to commit
     *
     * @param string $message
     *
     * @return bool
     * @throw Git_Command_Exception
     */
    public function commit($message)
    {
        if ($this->isThereAnythingToCommit()) {
            $cmd = 'commit -m ' . escapeshellarg($message);
            return $this->gitCmd($cmd);
        }
        return true;
    }

    /**
     * git help push
     *
     * @return bool
     * @throw Git_Command_Exception
     */
    public function push($origin = 'origin master')
    {
        $cmd = 'push --porcelain ' . $origin;
        return $this->gitCmd($cmd);
    }

    /**
     * @throws Git_Command_Exception
     */
    public function exportBranchesAndTags($destination_url)
    {
        $destination_url = escapeshellarg($destination_url);

        $push_heads = "push $destination_url refs/heads/*:refs/heads/*";
        $push_tags  = "push $destination_url refs/tags/*:refs/tags/*";

        $this->gitCmd($push_heads);
        $this->gitCmd($push_tags);
    }

    /**
     * Return true if working directory is clean (nothing to commit)
     *
     * @return bool
     * @throw Git_Command_Exception
     */
    public function isThereAnythingToCommit()
    {
        $output = array();
        $this->gitCmdWithOutput('status --porcelain', $output);
        foreach ($output as $status_line) {
            if (preg_match('/^[ADMR]/', $status_line)) {
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @return string The git repository path where we operate
     */
    public function getPath()
    {
        return $this->work_tree;
    }

    public static function getGitCommand()
    {
        if (self::isGit212Installed()) {
            return self::GIT212_PATH . '/usr/bin/git';
        }
        return 'git';
    }

    public function getGitDir()
    {
        return $this->git_dir;
    }

    protected function gitCmd($cmd)
    {
        $output = array();
        return $this->gitCmdWithOutput($cmd, $output);
    }

    /**
     * @return bool
     * @throws Git_Command_Exception
     */
    protected function gitCmdWithOutput($cmd, &$output)
    {
        return $this->execInPath($cmd, $output);
    }

    /**
     * @param $cmd
     * @param $output
     * @return bool
     * @throws Git_Command_Exception
     */
    protected function execInPath($cmd, &$output)
    {
        $git = $this->getAllowedProtocolEnvVariable();
        $git .= $this->git_cmd . ' --work-tree=' . escapeshellarg($this->work_tree) . ' --git-dir=' . escapeshellarg($this->git_dir);
        $git .= ' ' . $cmd;
        try {
            $command = new System_Command();
            $output = $command->exec($git);
            return true;
        } catch (System_Command_CommandException $exception) {
            throw new Git_Command_Exception($exception->getCommand(), $exception->getOutput(), $exception->getReturnValue());
        }
    }

    private function getAllowedProtocolEnvVariable()
    {
        if (count($this->allowedTransports) > 0) {
            return 'GIT_ALLOW_PROTOCOL=' . implode(':', $this->allowedTransports) . ' ';
        }
        return '';
    }
}
