<?php
/**
  * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
  * Copyright (c) Enalean, 2014. All Rights Reserved.
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
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

/**
 * Description of GitDriverclass
 * @TODO Create Class exception to thro GIT messages
 * @TODO Make this driver compliant with Apache ??
 * @TODO Make sure directories tree to manage forks and repo is a good choice
 */

require_once(__DIR__ . '/../DVCS/DVCSDriver.class.php');

class GitDriver implements DVCSDriver
{

    protected function execGitAction($cmd, $action_name)
    {
        $out = array();
        $ret = -1;
        exec($cmd, $out, $ret);
        if ($ret !== 0) {
            throw new GitDriverErrorException('Git ' . $action_name . ' failed on ' . $ret . ' ' . $cmd . PHP_EOL . implode(PHP_EOL, $out));
        }

        return implode(PHP_EOL, $out);
    }

    private function checkFileExist($file)
    {
        if (!file_exists($file)) {
            throw new GitDriverSourceNotFoundException($file);
        }
    }

    /**
     * Make a clone of a source repository
     * @param string $source      source directory
     * @param string $destination destination directory
     * @param string $option     String of options.
     * @return bool
     */
    private function cloneRepo($source, $destination, $option)
    {
        $this->checkFileExist($source);

        //WARNING : never use --shared/--reference options
        $cmd = 'git clone ' . $option . ' --local --no-hardlinks ' . escapeshellarg($source) . ' ' . escapeshellarg($destination) . ' 2>&1';

        return $this->execGitAction($cmd, "clone");
    }

    public function fork($source, $destination)
    {
        $this->checkFileExist($source);

        $this->cloneRepo($source, $destination, '--bare');

        return $this->setUpFreshRepository($destination);
    }

    public function cloneAtSpecifiqBranch($source, $destination, $branch)
    {
        $this->checkFileExist($source);

        return $this->cloneRepo($source, $destination, '--branch ' . $branch);
    }

    public function add($repositoryPath, $filePathFromRepository)
    {
        $this->checkFileExist($repositoryPath);
        $cmd = 'cd ' . escapeshellarg($repositoryPath) . ' && git add ' . escapeshellarg($filePathFromRepository) . ' 2>&1';

        return $this->execGitAction($cmd, 'add');
    }

    public function commit($repositoryPath, $message)
    {
        $this->checkFileExist($repositoryPath);
        $cmd = 'cd ' . escapeshellarg($repositoryPath) . ' && git commit --allow-empty -m ' . escapeshellarg($message) . ' 2>&1';

        return $this->execGitAction($cmd, 'commit');
    }

    public function mergeAndPush($repositoryPath, $bareURL)
    {
        $this->checkFileExist($repositoryPath);
        $cmd = 'cd ' . escapeshellarg($repositoryPath) . ' && git pull --quiet --rebase && git push ' . $bareURL . ' 2>&1';

        return $this->execGitAction($cmd, 'merge and push');
    }

    public function getInformationsAboutFile($repositoryPath, $filePathFromRepository)
    {
        $this->checkFileExist($repositoryPath);
        $cmd = 'cd ' . escapeshellarg($repositoryPath) . ' && git ls-files --stage ' . escapeshellarg($filePathFromRepository) . ' 2>&1';

        return $this->execGitAction($cmd, 'get informations');
    }

    public function removeRepository($repositoryPath)
    {
        $this->checkFileExist($repositoryPath);
        $cmd = 'rm --recursive --dir --force ' . escapeshellarg($repositoryPath) . ' 2>&1';

        return $this->execGitAction($cmd, 'rm');
    }

    public function getGitVersion()
    {
        $cmd        = 'git --version';
        $cmd_result = $this->execGitAction($cmd, 'version');
        $version    = explode(" ", $cmd_result);

        return $version[2];
    }

    /**
     * Initialize a repository
     * @param bool $bare is a bare a repository
     * @return bool
     */
    public function init($bare = false)
    {
        if ($bare === false) {
            $cmd = 'git init';
            $out = array();
            $ret = -1;
            exec($cmd, $out, $ret);
            if ($ret !== 0) {
                throw new GitDriverErrorException('Git init failed on ' . $cmd . PHP_EOL . implode(PHP_EOL, $out));
            }
            return true;
        }

        $cmd = 'git --bare init --shared=group 2>&1';
        $out = array();
        $ret = -1;
        exec($cmd, $out, $ret);
        if ($ret !== 0) {
            throw new GitDriverErrorException('Git init failed on ' . $cmd . PHP_EOL . implode(PHP_EOL, $out));
        }

        return $this->setUpFreshRepository(getcwd());
    }

    /**
     * Post creation/clone repository setup
     *
     * @param String $path Path to the repository
     *
     * @return bool
     */
    protected function setUpFreshRepository($path)
    {
        $cwd = getcwd();
        chdir($path);

        $cmd = 'git update-server-info';
        $out = array();
        $ret = -1;
        exec($cmd, $out, $ret);
        chdir($cwd);
        if ($ret !== 0) {
            throw new GitDriverErrorException('Git setup failed on ' . $cmd . PHP_EOL . implode(PHP_EOL, $out));
        }

        if (!$this->setDescription($path, 'Default description for this project' . PHP_EOL)) {
            throw new GitDriverErrorException('Git setup failed on description update');
        }

        return $this->setPermissions($path);
    }

    public function delete($path)
    {
        if (empty($path) || !is_writable($path)) {
            throw new GitDriverErrorException('Empty path or permission denied ' . $path);
        }
        $rcode = 0;
        $output = system('rm -fr ' . escapeshellarg($path), $rcode);
        if ($rcode != 0) {
            throw new GitDriverErrorException('Unable to delete path ' . $path);
        }
        return true;
    }

    public function activateHook($hookName, $repoPath, $uid = false, $gid = false)
    {
        //newer version of git
        $hook = $repoPath . '/hooks/' . $hookName;
        if (file_exists($hook . '.sample')) {
            //old git versions do not need this move
            rename($hook . '.sample', $hook);
        }

        //older versions only requires +x for hook activation
        if (!chmod($hook, 0755)) {
            throw new GitDriverErrorException('Unable to make ' . $hook . ' executable');
        }

        if ($uid !== false) {
            if (!chown($hook, $uid)) {
                 throw new GitDriverErrorException('Unable to change ' . $hook . ' owner to ' . $uid);
            }
        }
        if ($gid !== false) {
            if (!chgrp($hook, $gid)) {
                 throw new GitDriverErrorException('Unable to change ' . $hook . ' group to ' . $gid);
            }
        }
        return true;
    }

    public function masterExists($repoPath)
    {
        if (file_exists($repoPath . '/refs/heads/master')) {
            return true;
        }
        return false;
    }

    /**
     *
     * @param string $repoPath
     * @return bool
     */
    public function isRepositoryCreated($repoPath)
    {
        return is_dir($repoPath . '/refs/heads');
    }

    public function setDescription($repoPath, $description)
    {
        if (! file_put_contents($repoPath . '/description', $description)) {
            throw new GitDriverErrorException('Unable to set description');
        }
        return true;
    }

    public function getDescription($repoPath)
    {
        return file_get_contents($repoPath . '/description');
    }

    /**
     * Set one configuration key
     *
     * @param String $repoPath Path to the repository
     * @param String $key      Key to modify
     * @param String $value    Value to set
     */
    public function setConfig($repoPath, $key, $value)
    {
        if ($value === '') {
            $value = "''";
        } else {
            $value = escapeshellarg($value);
        }
        $configFile = $repoPath . '/config';
        $cmd = 'git config --file ' . $configFile . ' --replace-all ' . escapeshellarg($key) . ' ' . $value . ' 2>&1';
        $ret = -1;
        $out = array();
        exec($cmd, $out, $ret);
        if ($ret !== 0) {
            throw new GitDriverErrorException('Unable to set config for repository ' . $repoPath . ':' . PHP_EOL . implode(PHP_EOL, $out));
        }
    }

    /**
     * Control who can access to a repository
     *
     * @param String  $repoPath Path to the repository
     * @param int $access Access level
     *
     * @return bool
     */
    public function setRepositoryAccess($repoPath, $access)
    {
        if ($access == GitRepository::PUBLIC_ACCESS) {
            return chmod($repoPath, 042775);
        } else {
            return chmod($repoPath, 042770);
        }
    }

    /**
     * Ensure repository has the right permissions
     *
     * Pretty useless on repo creation (--shared option is ok for that) but
     * Mandatory for clone as clone doesn't set the right permissions by default.
     *
     * @param String $path Path to the repository
     *
     * @return bool
     */
    protected function setPermissions($path)
    {
        $rcode  = 0;
        $cmd    = 'find ' . $path . ' -type d | xargs chmod u+rwx,g+rwxs ' . $path;
        $output = system($cmd, $rcode);
        if ($rcode != 0) {
            throw new GitDriverErrorException($cmd . ' -> ' . $output);
        }

        if (!chmod($path . DIRECTORY_SEPARATOR . 'HEAD', 0664)) {
            throw new GitDriverErrorException('Unable to set permissions on HEAD');
        }
        return true;
    }
}
