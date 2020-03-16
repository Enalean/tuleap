<?php
/**
  * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
  * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * Description of GitBackend
 */
class GitBackend extends Backend implements Git_Backend_Interface, GitRepositoryCreator
{

    private $driver;
    //path MUST end with a '/'
    private $gitRootPath;

    /** @var Git_GitRepositoryUrlManager */
    private $url_manager;

    public const DEFAULT_DIR_MODE = '770';

    protected function __construct()
    {
        $this->gitRootPath  = '';
        $this->driver       = new GitDriver();
        $this->dao          = new GitDao();
        //WARN : this is much safer to set it to an absolute path
        $this->gitRootPath  = Git_Backend_Interface::GIT_ROOT_PATH;
        $this->gitBackupDir = PluginManager::instance()->getPluginByName('git')->getPluginInfo()->getPropVal('git_backup_dir');
    }

    public function setUp(Git_GitRepositoryUrlManager $url_manager)
    {
        $this->url_manager  = $url_manager;
    }

    public function setGitBackupDir($dir)
    {
        $this->gitBackupDir = $dir;
    }

    public function setGitRootPath($gitRootPath)
    {
        $this->gitRootPath = $gitRootPath;
    }

    public function getGitRootPath()
    {
        return $this->gitRootPath;
    }

    public function getDao()
    {
        return $this->dao;
    }
    public function getDriver()
    {
        return $this->driver;
    }

    public function canBeDeleted(GitRepository $repository)
    {
        return ($this->getDao()->hasChild($repository) !== true);
    }

    public function markAsDeleted(GitRepository $repository)
    {
        $this->getDao()->delete($repository);
    }

    public function delete(GitRepository $repository)
    {
        $path = $this->getGitRootPath() . DIRECTORY_SEPARATOR . $repository->getPath();
        $this->archive($repository);
        $this->getDriver()->delete($path);
    }

    public function save($repository)
    {
        $path          = Git_Backend_Interface::GIT_ROOT_PATH . '/' . $repository->getPath();
        $fsDescription = $this->getDriver()->getDescription($path);
        $description   = $repository->getDescription();
        if ($description != $fsDescription) {
            $this->getDriver()->setDescription($path, $description);
        }
        $this->getDao()->save($repository);
    }

    public function renameProject(Project $project, $newName)
    {
        if (is_dir(Git_Backend_Interface::GIT_ROOT_PATH . '/' . $project->getUnixName())) {
            return rename(Git_Backend_Interface::GIT_ROOT_PATH . '/' . $project->getUnixName(), Git_Backend_Interface::GIT_ROOT_PATH . '/' . $newName);
        }
        return true;
    }

    public function isInitialized(GitRepository $repository)
    {
        $masterExists = $this->getDriver()->masterExists($this->getGitRootPath() . '/' . $repository->getPath());
        if ($masterExists) {
            $this->getDao()->initialize($repository->getId());
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @param GitRepository $respository
     * @return bool
     */
    public function isCreated(GitRepository $repository)
    {
        return $this->getDriver()->isRepositoryCreated($this->getGitRootPath() . '/' . $repository->getPath());
    }

    public function changeRepositoryAccess($repository)
    {
        $access   = $repository->getAccess();
        $repoPath = $repository->getPath();
        $path     = Git_Backend_Interface::GIT_ROOT_PATH . '/' . $repoPath;
        $this->getDriver()->setRepositoryAccess($path, $access);
        $this->getDao()->save($repository);
        return true;
    }

    /**
     * Allow the update of the mail prefix
     *
     * @param GitRepository $repository
     */
    public function changeRepositoryMailPrefix($repository)
    {
        if ($this->getDao()->save($repository)) {
            $path = $this->getGitRootPath() . $repository->getPath();
            $this->getDriver()->setConfig($path, 'hooks.emailprefix', $repository->getMailPrefix());
            $this->setUpMailingHook($repository);
            return true;
        }
        return false;
    }

    /**
     * Update list of people notified by post-receive-email hook
     *
     * @param GitRepository $repository
     */
    public function changeRepositoryMailingList($repository)
    {
        $path = $this->getGitRootPath() . $repository->getPath();
        $this->getDriver()->setConfig($path, 'hooks.mailinglist', implode(',', $repository->getNotifiedMails()));
        $this->setUpMailingHook($repository);
        return true;
    }

    /**
     * Deploy post-receive hook into the target file
     *
     * @param String $path Path to the repository root
     *
     * @return void
     */
    public function deployPostReceive($path)
    {
        $this->getDriver()->activateHook('post-receive', $path);
        $hook = '. ' . $GLOBALS['sys_pluginsroot'] . 'git/hooks/post-receive 2>/dev/null';
        $this->addBlock($path . '/hooks/post-receive', $hook);
    }

    /**
     * Configure mail output to link commit to gitweb
     *
     * @param GitRepository $repository
     */
    public function setUpMailingHook($repository)
    {
        $path     = $this->getGitRootPath() . $repository->getPath();
        $show_rev = $repository->getPostReceiveShowRev($this->url_manager);
        $this->getDriver()->setConfig($path, 'hooks.showrev', $show_rev);
    }


    /**
     * INTERNAL METHODS
     */

    protected function setRepositoryPermissions($repository)
    {
        $path = $this->getGitRootPath() . DIRECTORY_SEPARATOR . $repository->getPath();
        $no_filter_file_extension = array();
        $this->recurseChownChgrp(
            $path,
            'codendiadm',
            $repository->getProject()->getUnixName(),
            $no_filter_file_extension
        );
        return true;
    }

    protected function createGitRoot()
    {
        $gitRootPath    = $this->getGitRootPath();
        //create the gitroot directory
        if (!is_dir($gitRootPath)) {
            if (!mkdir($gitRootPath, 0755)) {
                throw new GitBackendException(dgettext('tuleap-git', 'Error while creating root for Git repositories (Contact the site admin)') . ' -> ' . $gitRootPath);
            }
        }
        return true;
    }

    //TODO : public project
    protected function createProjectRoot($repository)
    {
        $gitProjectPath = $this->getGitRootPath() . DIRECTORY_SEPARATOR . $repository->getRootPath();
        $groupName      = $repository->getProject()->getUnixName();
        if (!is_dir($gitProjectPath)) {
            if (!mkdir($gitProjectPath, 0775, true)) {
                throw new GitBackendException(dgettext('tuleap-git', 'Error while creating project root for Git repository (Contact the site admin)') . ' -> ' . $gitProjectPath);
            }

            if (!$this->chgrp($gitProjectPath, $groupName)) {
                throw new GitBackendException(dgettext('tuleap-git', 'Error while setting project root permissions (Contact the site admin)') . $gitProjectPath . ' group=' . $groupName);
            }
        }
        return true;
    }

    /**
     *@todo move the archive to another directory
     * @return true
     */
    public function archive(GitRepository $repository)
    {
        chdir($this->getGitRootPath());
        $path = $repository->getPath();
        $archiveName = $repository->getBackupPath() . '.tar.gz';
        $cmd    = ' tar czf ' . $archiveName . ' ' . $path . ' 2>&1';
        $rcode  = 0;
        $output = $this->system($cmd, $rcode);
        if ($rcode != 0) {
            throw new GitBackendException($cmd . ' -> ' . $output);
        }
        if (!empty($this->gitBackupDir) && is_dir($this->gitBackupDir)) {
            $this->system('mv ' . $this->getGitRootPath() . '/' . $archiveName . ' ' . $this->gitBackupDir . '/' . $archiveName);
        }
        return true;
    }

    /**
     * Verify if given name is not already reserved on filesystem
     */
    public function isNameAvailable($newName)
    {
        return ! $this->fileExists(Git_Backend_Interface::GIT_ROOT_PATH . '/' . $newName);
    }

    /**
     * Return URL to access the respository for remote git commands
     *
     * @return array
     */
    public function getAccessURL(GitRepository $repository)
    {
        $serverName  = $_SERVER['SERVER_NAME'];
        $user = UserManager::instance()->getCurrentUser();
        return array('ssh' => $user->getUserName() . '@' . $serverName . ':/gitroot/' . $repository->getProject()->getUnixName() . '/' . $repository->getName() . '.git');
    }

    /**
     * Test is user can read the content of this repository and metadata
     *
     * @param PFUser          $user       The user to test
     * @param GitRepository $repository The repository to test
     *
     * @return bool
     */
    public function userCanRead($user, $repository)
    {
        if ($repository->isPrivate() && $user->isMember($repository->getProjectId())) {
            return true;
        }
        if ($repository->isPublic()) {
            if ($user->isRestricted() && $user->isMember($repository->getProjectId())) {
                return true;
            }
            if (!$user->isAnonymous()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Obtain statistics about backend format for CSV export
     *
     * @param Statistics_Formatter $formatter instance of statistics formatter class
     *
     * @return String
     */
    public function getBackendStatistics(Statistics_Formatter $formatter)
    {
        $formatter->clearContent();
        $formatter->addEmptyLine();
        $formatter->addHeader('Git');
        $gitShellIndex[]       = $GLOBALS['Language']->getText('plugin_statistics', 'scm_month');
        $gitShell[]            = "Git shell created repositories";
        $gitShellActiveIndex[] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_month');
        $gitShellActive[]      = "Git shell created repositories (still active)";
        $gitoliteIndex[]       = $GLOBALS['Language']->getText('plugin_statistics', 'scm_month');
        $gitolite[]            = "Gitolite created repositories";
        $gitoliteActiveIndex[] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_month');
        $gitoliteActive[]      = "Gitolite created repositories (still active)";
        $this->fillBackendStatisticsByType($formatter, 'gitshell', $gitShellIndex, $gitShell, false);
        $this->fillBackendStatisticsByType($formatter, 'gitshell', $gitShellActiveIndex, $gitShellActive, true);
        $this->fillBackendStatisticsByType($formatter, 'gitolite', $gitoliteIndex, $gitolite, false);
        $this->fillBackendStatisticsByType($formatter, 'gitolite', $gitoliteActiveIndex, $gitoliteActive, true);
        $this->retrieveLoggedPushesStatistics($formatter);
        $this->retrieveReadAccessStatistics($formatter);
        $content = $formatter->getCsvContent();
        $formatter->clearContent();
        return $content;
    }

    /**
     * Fill statistics by Backend type
     *
     * @param Statistics_Formatter $formatter   instance of statistics formatter class
     * @param String               $type        backend type
     * @param Array                $typeIndex   backend type index
     * @param Array                $typeArray   backend type array
     * @param bool $keepedAlive keep only reposirtories that still active
     *
     * @return Void
     */
    private function fillBackendStatisticsByType(Statistics_Formatter $formatter, $type, $typeIndex, $typeArray, $keepedAlive)
    {
        $dao  = $this->getDao();
        $rows = $dao->getBackendStatistics($type, $formatter->startDate, $formatter->endDate, $formatter->groupId, $keepedAlive);
        if (count($rows) > 0) {
            foreach ($rows as $row) {
                $typeIndex[] = $row['month'] . " " . $row['year'];
                $typeArray[]      = intval($row['count']);
            }
            $formatter->addLine($typeIndex);
            $formatter->addLine($typeArray);
            $formatter->addEmptyLine();
        }
    }

    /**
     * Retrieve logged pushes statistics for CSV export
     *
     * @param Statistics_Formatter $formatter instance of statistics formatter class
     *
     * @return Void
     */
    private function retrieveLoggedPushesStatistics(Statistics_Formatter $formatter)
    {
        $gitIndex[]   = $GLOBALS['Language']->getText('plugin_statistics', 'scm_month');
        $gitPushes[]  = $GLOBALS['Language']->getText('plugin_statistics', 'scm_git_total_pushes');
        $gitCommits[] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_git_total_commits');
        $gitUsers[]   = $GLOBALS['Language']->getText('plugin_statistics', 'scm_git_users');
        $gitRepo[]    = $GLOBALS['Language']->getText('plugin_statistics', 'scm_git_repositories');

        $gitLogDao = new Git_LogDao();
        $rows      = $gitLogDao->totalPushes($formatter->startDate, $formatter->endDate, $formatter->groupId);
        foreach ($rows as $row) {
            $gitIndex[]   = $row['month'] . " " . $row['year'];
            $gitPushes[]  = intval($row['pushes_count']);
            $gitCommits[] = intval($row['commits_count']);
            $gitUsers[]   = intval($row['users']);
            $gitRepo[]    = intval($row['repositories']);
        }
        $formatter->addLine($gitIndex);
        $formatter->addLine($gitPushes);
        $formatter->addLine($gitCommits);
        $formatter->addLine($gitUsers);
        $formatter->addLine($gitRepo);
        $formatter->addEmptyLine();
    }

    private function retrieveReadAccessStatistics(Statistics_Formatter $formatter)
    {
        $dao   = new Tuleap\Git\History\Dao();
        $stats = $dao->searchStatistics($formatter->startDate, $formatter->endDate, $formatter->groupId);
        if (count($stats) === 0) {
            return;
        }

        $header = array($GLOBALS['Language']->getText('plugin_statistics', 'scm_month'));
        $line   = array(dgettext('tuleap-git', 'Total number of git read access'));
        foreach ($stats as $row) {
            $header[] = $row['month'] . " " . $row['year'];
            $line[]   = intval($row['nb']);
        }

        $formatter->addLine($header);
        $formatter->addLine($line);
        $formatter->addEmptyLine();
    }

    public function getAllowedCharsInNamePattern()
    {
        throw new Exception('not implemented');
    }

    public function isNameValid($name)
    {
        throw new Exception('not implemented');
    }

    public function deleteArchivedRepository(GitRepository $repository)
    {
    }

    /**
     * Move the archived gitolite repositories to the archiving area before purge
     *
     */
    public function archiveBeforePurge(GitRepository $repository)
    {
        throw new Exception('not implemented');
    }
}
