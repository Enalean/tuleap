<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015-2017. All Rights Reserved.
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

class BackendCVS extends Backend
{

    protected $CVSRootListNeedUpdate;
    protected $UseCVSNT;

    /**
     * Return an instance of ServiceDao
     *
     * @return ServiceDao
     */
    public function _getServiceDao()
    {
        return new ServiceDao(CodendiDataAccess::instance());
    }


    /**
     * Return true if server uses CVS NT, or false if it uses GNU CVS
     *
     * @return bool
     */
    public function useCVSNT()
    {
        if (isset($this->UseCVSNT)) {
            return $this->UseCVSNT;
        }
        if (is_file("/usr/bin/cvsnt")) {
            $this->UseCVSNT = true;
        } else {
            $this->UseCVSNT = false;
        }
        return $this->UseCVSNT;
    }

    /**
     * Check if repository of given project exists
     *
     * @param Project $project Project for wich repository will be checked
     *
     * @return bool true is repository already exists, false otherwise
     */
    public function repositoryExists($project)
    {
        $unix_group_name = $project->getUnixName(false); // May contain upper-case letters
        $cvs_dir = $GLOBALS['cvs_prefix'] . "/" . $unix_group_name;
        if (is_dir($cvs_dir)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Create project CVS repository
     * If the directory already exists, nothing is done.
     *
     * @param int $group_id project id for wic CVS repository will be created
     *
     * @return bool true if repo is successfully created, false otherwise
     */
    public function createProjectCVS($group_id)
    {
        $project = $this->getProjectManager()->getProject($group_id);
        if (!$project) {
            return false;
        }

        $unix_group_name = $project->getUnixName(false);
        $cvs_dir         = $GLOBALS['cvs_prefix'] . "/" . $unix_group_name;

        if (! is_dir($cvs_dir)) {
            // Let's create a CVS repository for this group
            if (! mkdir($cvs_dir)) {
                $this->log("Can't create project CVS dir: $cvs_dir", Backend::LOG_ERROR);
                return false;
            }

            $return_code = 0;
            $output      = '';
            $cvs_command = $GLOBALS['cvs_cmd'];

            if (! file_exists($cvs_command)) {
                $this->log("CVS command not found", Backend::LOG_ERROR);
                return false;
            }

            if ($this->useCVSNT()) {
                // Tell cvsnt not to update /etc/cvsnt/PServer: this is done later by this the script.
                $output = $this->system("$cvs_command -d$cvs_dir init -n ", $return_code);
            } else {
                $output = $this->system("$cvs_command -d$cvs_dir init", $return_code);
            }

            if ($return_code > 0) {
                $this->log("CVS init command return: $output", Backend::LOG_ERROR);
                return false;
            }

            if (! is_dir("$cvs_dir/CVSROOT")) {
                $this->log("Folder $cvs_dir/CVSROOT does not exist", Backend::LOG_ERROR);
                return false;
            }

            // Turn off pserver writers, on anonymous readers
            // See CVS writers update below. Just create an
            // empty writers file so that we can set up the appropriate
            // ownership right below. We will put names in writers
            // later in the script

            $return_code_turn_off = 0;

            $output_turn_off = $this->system("echo '' > $cvs_dir/CVSROOT/writers", $return_code_turn_off);

            if ($return_code_turn_off > 0) {
                $this->log("Echo in /CVSROOT/writers returns: $output_turn_off", Backend::LOG_ERROR);
                return false;
            }

            if (! $this->useCVSNT()) {
                // But to allow checkout/update to registered users we
                // need to setup a world writable directory for CVS lock files
                $lockdir = $GLOBALS['cvslock_prefix'] . "/$unix_group_name";
                $filename = "$cvs_dir/CVSROOT/config";
                $this->_RcsCheckout($filename);
                $this->system("echo  >> $filename");
                $this->system("echo '# !!! Codendi Specific !!! DO NOT REMOVE' >> $filename");
                $this->system("echo '# Put all CVS lock files in a single directory world writable' >> $filename");
                $this->system("echo '# directory so that any registered user can checkout/update' >> $filename");
                $this->system("echo '# without having write permission on the entire cvs tree.' >> $filename");
                $this->system("echo 'LockDir=$lockdir' >> $filename");
                // commit changes to config file (directly with RCS)
                $this->_RcsCommit($filename);

                // Create lock dir
                $this->createLockDirIfMissing($project);
            }

            // put an empty line in in the valid tag cache (means no tag yet)
            // (this file is not under version control so don't check it in)
            $this->system("echo \"\" > $cvs_dir/CVSROOT/val-tags");
            $this->system("chmod 0664 $cvs_dir/CVSROOT/val-tags");

            // set group ownership, http user
            $this->changeRepoOwnership($cvs_dir, $unix_group_name);
            $this->recursiveSgidOnDirectories($cvs_dir);
        }

        // Create writer file
        if (! $this->updateCVSwriters($group_id)) {
            $this->log("Error while updating CVS Writers", Backend::LOG_ERROR);
            return false;
        }

        // history was deleted (or not created)? Recreate it.
        if ($this->useCVSNT()) {
            // Create history file (not created by default by cvsnt)
            $this->system("touch $cvs_dir/CVSROOT/history");
            // Must be writable
            $this->system("chmod 0666 $cvs_dir/CVSROOT/history");
            $no_filter_file_extension = array();
            $this->recurseChownChgrp(
                $cvs_dir . "/CVSROOT",
                $this->getHTTPUser(),
                $unix_group_name,
                $no_filter_file_extension
            );
        }

        // Update post-commit hooks
        if (! $this->updatePostCommit($project)) {
            return false;
        }

        // Update watch mode
        if (! $this->updateCVSWatchMode($group_id)) {
            return false;
        }

        return true;
    }

    protected function recursiveSgidOnDirectories($root)
    {
        $this->system('find ' . $root . ' -type d -exec chmod g+rws {} \;');
    }

    /**
     * Create lock dir if missing
     *
     * @param Project $project project for wich the lock dir will be created
     *
     * @return bool
     */
    public function createLockDirIfMissing($project)
    {
        // Lockdir does not exist? (Re)create it.
        if (!$this->useCVSNT()) {
            $lockdir = $GLOBALS['cvslock_prefix'] . "/" . $project->getUnixName(false);
            if (! is_dir($lockdir)) {
                if (!mkdir("$lockdir", 02777)) {
                    $this->log("Can't create project CVS lock dir: $lockdir", Backend::LOG_ERROR);
                    return false;
                }
                $this->system("chmod 02777 $lockdir"); // overwrite umask value
            }
        }
        return true;
    }

    /**
     * Update post-commit hooks
     *
     * @param Project $project project for wich post-commit hooks will be updated
     *
     * @return bool
     */
    public function updatePostCommit($project)
    {
        $unix_group_name = $project->getUnixName(false); // May contain upper-case letters
        $cvs_dir = $GLOBALS['cvs_prefix'] . "/" . $unix_group_name;
        if ($project->isCVSTracked()) {
            // hook for commit tracking in cvs loginfo file
            $filename = "$cvs_dir/CVSROOT/loginfo";
            $file_array = file($filename);
            if (!in_array($this->block_marker_start, $file_array)) {
                if ($this->useCVSNT()) {
                    $command = "ALL " . $GLOBALS['codendi_bin_prefix'] . "/log_accum -T $unix_group_name -C $unix_group_name -s %{sVv}";
                } else {
                    $command = "ALL (" . $GLOBALS['codendi_bin_prefix'] . "/log_accum -T $unix_group_name -C $unix_group_name -s %{sVv})>/dev/null 2>&1";
                }
                $this->_RcsCheckout($filename);
                $this->addBlock($filename, $command);
                $this->_RcsCommit($filename);
                $no_filter_file_extension = array();
                $this->recurseChownChgrp(
                    $cvs_dir . "/CVSROOT",
                    $this->getHTTPUser(),
                    $unix_group_name,
                    $no_filter_file_extension
                );
            }

            // hook for commit tracking in cvs commitinfo file
            $filename = "$cvs_dir/CVSROOT/commitinfo";
            $file_array = file($filename);
            if (!in_array($this->block_marker_start, $file_array)) {
                $this->_RcsCheckout($filename);
                $this->addBlock($filename, "ALL " . $GLOBALS['codendi_bin_prefix'] . "/commit_prep -T $unix_group_name -r");
                $this->_RcsCommit($filename);
                $no_filter_file_extension = array();
                $this->recurseChownChgrp(
                    $cvs_dir . "/CVSROOT",
                    $this->getHTTPUser(),
                    $unix_group_name,
                    $no_filter_file_extension
                );
            }
        } else {
            // Remove Codendi blocks if needed
            $filename = "$cvs_dir/CVSROOT/loginfo";
            $file_array = file($filename);
            if (in_array($this->block_marker_start, $file_array)) {
                $this->removeBlock($filename);
            }
            $filename = "$cvs_dir/CVSROOT/commitinfo";
            $file_array = file($filename);
            if (in_array($this->block_marker_start, $file_array)) {
                $this->removeBlock($filename);
            }
        }
        return true;
    }

    /**
     * Update (or create) file CVSROOT/writers that should contain project members
     *
     * On Codendi writers go through pserver as well so put
     * group members in writers file. Do not write anything
     * in the CVS passwd file. The pserver protocol will fallback
     * on /etc/passwd (or NSS) for user authentication
     *
     * @param int $group_id Project id for which committers will be updated
     *
     * @return bool
     */
    public function updateCVSwriters($group_id)
    {
        $project = $this->getProjectManager()->getProject($group_id);
        if (! $project) {
            return false;
        }

        $unix_group_name = $project->getUnixName(false); // May contain upper-case letters
        $cvs_dir         = $GLOBALS['cvs_prefix'] . "/" . $unix_group_name;
        $cvswriters_file = "$cvs_dir/CVSROOT/writers";

        // Get list of project members (Unix names)
        $members_id_array   = $project->getMembersUserNames();
        $members_name_array = array();
        foreach ($members_id_array as $member) {
            $members_name_array[] = strtolower($member['user_name']) . "\n";
        }

        return $this->writeArrayToFile($members_name_array, $cvswriters_file);
    }
    /**
     * Update CVS writers into all projects that given user belongs to
     *
     * @param PFUser $user member to add as committer
     *
     * @return bool
     */
    public function updateCVSWritersForGivenMember($user)
    {
        $projects = $user->getProjects();
        if (isset($projects)) {
            $pm = $this->getProjectManager();
            foreach ($projects as $groupId) {
                $project = $pm->getProject($groupId);
                if ($project->usesCVS() === true && $this->repositoryExists($project)) {
                    if (!$this->updateCVSwriters($groupId)) {
                        return false;
                    }
                }
            }
        }
        return true;
    }


    /**
     * Update CVS Watch Mode
     *
     * @param int $group_id Project id for wich watch mode will be updated
     *
     * @return bool
     */
    public function updateCVSWatchMode($group_id)
    {
        $project = $this->getProjectManager()->getProject($group_id);
        if (!$project) {
            $this->log("Project not found: $group_id", Backend::LOG_ERROR);
            return false;
        }

        $unix_group_name = $project->getUnixName(false); // May contain upper-case letters
        $cvs_dir = $GLOBALS['cvs_prefix'] . "/" . $unix_group_name;
        $filename = "$cvs_dir/CVSROOT/notify";
        //If notify file does not exist, we should raise error in log
        //and return false
        if (!file_exists($filename)) {
            $this->log("No such file: $filename", Backend::LOG_ERROR);
            return false;
        }
        $file_array = file($filename);

        // Add notify command if cvs_watch_mode is on
        if ($project->getCVSWatchMode()) {
            if (!in_array($this->block_marker_start, $file_array)) {
                $this->_RcsCheckout($filename);
                $this->addBlock($filename, 'ALL mail %s -s "CVS notification"');
                $this->_RcsCommit($filename);

                // Apply cvs watch on only if cvs_watch_mode changed to on
                $this->CVSWatch($cvs_dir, $unix_group_name, 1);
                $this->changeRepoOwnership($cvs_dir, $unix_group_name);
                $this->system("chmod g+rws $cvs_dir");
            }
        } else {
            // Remove notify command if cvs_watch_mode is off.
            if (in_array($this->block_marker_start, $file_array)) {
                // Switch to cvs watch off
                $this->_RcsCheckout($filename);
                $this->removeBlock($filename);
                $this->_RcsCommit($filename);
                $no_filter_file_extension = array();
                $this->recurseChownChgrp(
                    $cvs_dir . "/CVSROOT",
                    $this->getHTTPUser(),
                    $unix_group_name,
                    $no_filter_file_extension
                );
                $this->CVSWatch($cvs_dir, $unix_group_name, 0);
            }
        }
        return true;
    }

    /**
     * Setup the watch mode on the CVS repository
     *
     * @param String  $cvs_dir         CVS root directory
     * @param String  $unix_group_name name of the project
     * @param int $watch_mode defines the watch mode
     *
     * @return bool
     */
    public function CVSWatch($cvs_dir, $unix_group_name, $watch_mode)
    {
        $sandbox_dir =  $GLOBALS['tmp_dir'] . "/" . $unix_group_name . ".cvs_watch_sandbox";
        if (is_dir($sandbox_dir)) {
            return false;
        } else {
            mkdir("$sandbox_dir", 0700);
            $this->system("chmod 0700 $sandbox_dir"); // overwrite umask value
        }
        if ($watch_mode == 1) {
            $this->system("cd $sandbox_dir;cvs -d$cvs_dir co . 2>/dev/null 1>&2;cvs -d$cvs_dir watch on 2>/dev/null 1>&2;");
        } else {
            $this->system("cd $sandbox_dir;cvs -d$cvs_dir co . 2>/dev/null 1>&2;cvs -d$cvs_dir watch off 2>/dev/null 1>&2;");
        }
        $this->system("rm -rf $sandbox_dir;");
        return true;
    }

    /**
     * Checkout the file
     *
     * @param File $file file to checkout
     *
     * @return void
     */
    public function _RcsCheckout($file, &$output = '')
    {
        $rcode = 0;
        $output = $this->system("co -q -l $file", $rcode);
        return $rcode;
    }

    /**
     * Commit the file
     *
     * @param File $file file to be committed
     *
     * @return void
     */
    public function _RcsCommit($file, &$output = '')
    {
        $rcode  = 0;
        $output = $this->system("/usr/bin/rcs -q -l $file; ci -q -m\"Codendi modification\" $file; co -q $file", $rcode);
        return $rcode;
    }

    /**
     * Archive CVS repository: stores a tgz in temp dir, and remove the directory
     *
     * @param int $group_id id of the project for which CVS repository will be archived
     *
     * @return bool
     */
    public function archiveProjectCVS($group_id)
    {
        $project = $this->getProjectManager()->getProject($group_id);
        if (!$project) {
            return false;
        }
        $mydir = $GLOBALS['cvs_prefix'] . "/" . $project->getUnixName(false);
        $backupfile = ForgeConfig::get('sys_project_backup_path') . "/" . $project->getUnixName(false) . "-cvs.tgz";

        if (is_dir($mydir)) {
            $this->system("cd " . $GLOBALS['cvs_prefix'] . "; tar cfz $backupfile " . $project->getUnixName(false));
            chmod($backupfile, 0600);
            $this->recurseDeleteInDir($mydir);
            rmdir($mydir);
        }
        return true;
    }

    /**
     * Update the "cvs_root_allow" file that contains the list of authorised CVS repositories
     *
     * @return bool
     */
    public function CVSRootListUpdate()
    {
        $cvs_root_allow_array = array();
        $projlist = array();
        $repolist = array();

        $service_dao = $this->_getServiceDao();
        $dar = $service_dao->searchActiveUnixGroupByUsedService('cvs');
        foreach ($dar as $row) {
            $repolist[] = "/cvsroot/" . $row['unix_group_name'];
        }

        if ($this->useCVSNT()) {
            $config_file = $GLOBALS['cvsnt_config_file'];
            $cvsnt_marker = "DON'T EDIT THIS LINE - END OF CODENDI BLOCK";
        } else {
            $config_file = $GLOBALS['cvs_root_allow_file'];
        }
        $config_file_old = $config_file . ".old";
        $config_file_new = $config_file . ".new";

        if (is_file($config_file)) {
            $cvs_config_array = file($config_file);
        }

        $fp = fopen($config_file_new, 'w');

        if ($this->useCVSNT()) {
            fwrite($fp, "# Codendi CVSROOT directory list: do not edit this list!\n");

            $num = 0;
            foreach ($repolist as $reponame) {
                fwrite($fp, "Repository$num=$reponame\n");
                $num++;
            }
            fwrite($fp, "# End of Codendi CVSROOT directory list: you may change options below $cvsnt_marker\n");

            // and recopy other configuration instructions
            $configlines = 0;
            foreach ($cvs_config_array as $line) {
                if ($configlines) {
                    fwrite($fp, $line);
                }
                if (strpos($line, $cvsnt_marker)) {
                    $configlines = 1;
                }
            }
        } else {
            // CVS: simple list of allowed CVS roots
            foreach ($repolist as $reponame) {
                fwrite($fp, "$reponame\n");
            }
        }
        fclose($fp);

        // Backup existing file and install new one if they are different
        $this->installNewFileVersion($config_file_new, $config_file, $config_file_old);

        return true;
    }

    /**
     * set whether CVS root need to be updated or not
     *
     * @return void
     */
    public function setCVSRootListNeedUpdate()
    {
        $this->CVSRootListNeedUpdate = true;
    }

    /**
     * Check if CVS root need update
     *
     * @return bool
     */
    public function getCVSRootListNeedUpdate()
    {
        return $this->CVSRootListNeedUpdate;
    }

    /**
     * Make the cvs repository of the project private or public
     *
     * @param Project $project    project for which project privacy is set
     * @param bool $is_private true if the repository is private
     *
     * @return bool true if success
     */
    public function setCVSPrivacy($project, $is_private)
    {
        $perms = $is_private ? 02770 : 02775;
        $cvsroot = $GLOBALS['cvs_prefix'] . '/' . $project->getUnixName(false);
        return is_dir($cvsroot) && $this->chmod($cvsroot, $perms);
    }


    /**
     * Check ownership/mode/privacy of repository
     *
     * @param Project $project The project to work on
     *
     * @return bool true if success
     */
    public function checkCVSMode($project)
    {
        $unix_group_name =  $project->getUnixName(false);
        $cvsroot = $GLOBALS['cvs_prefix'] . '/' . $unix_group_name;
        $is_private = !$project->isPublic() || $project->isCVSPrivate();
        if ($is_private) {
            $perms = fileperms($cvsroot);
            // 'others' should have no right on the repository
            if (($perms & 0x0004) || ($perms & 0x0002) || ($perms & 0x0001) || ($perms & 0x0200)) {
                $this->log("Restoring privacy on CVS dir: $cvsroot", Backend::LOG_WARNING);
                $this->setCVSPrivacy($project, $is_private);
            }
        }
        // Sometimes, there might be a bad ownership on file (e.g. chmod failed, maintenance done as root...)
        $files_to_check = array('CVSROOT/loginfo', 'CVSROOT/commitinfo', 'CVSROOT/config');
        $need_owner_update = false;
        foreach ($files_to_check as $file) {
            if (file_exists($cvsroot . '/' . $file)) {
                // Get file stat
                $stat = stat("$cvsroot/$file");
                if ($stat) {
                    if (($stat['uid'] != $this->getHTTPUserUID()) || ($stat['gid'] != $project->getUnixGID())) {
                        $need_owner_update = true;
                    }
                }
            } else {
                $this->log("File not found in cvsroot: $cvsroot/$file", Backend::LOG_WARNING);
            }
        }
        if ($need_owner_update) {
            $this->log("Restoring ownership on CVS dir: $cvsroot", Backend::LOG_INFO);
            $this->changeRepoOwnership($cvsroot, $unix_group_name);
            $this->system('chmod g+rws ' . $cvsroot);
        }

        return true;
    }

    public function changeRepoOwnership($repo_path, $unix_group_name)
    {
            return $this->system("chown -R {$this->getHTTPUser()}:{$unix_group_name} $repo_path");
    }

    /**
     * Deleting files older than 2 hours in /var/run/log_accum that contain 'files'
     * (they have not been deleted due to commit abort)
     *
     * @return void
     */
    public function cleanup()
    {
        // TODO: test!
        $filelist = shell_exec("/usr/bin/find " . $GLOBALS['cvs_hook_tmp_dir'] . ' -name "*.files.*" -amin +120;');
        $files = explode("\n", $filelist);
        // Remove last (empty) element
        array_pop($files);

        foreach ($files as $file) {
            unlink($file);
        }
    }

    /**
     * Check if given name is not used by a repository or a file or a link
     *
     * @param String $name checked filename
     *
     * @return false if repository or file  or link already exists, true otherwise
     */
    public function isNameAvailable($name)
    {
        $path = $GLOBALS['cvs_prefix'] . "/" . $name;
        return  (!$this->fileExists($path));
    }


    /**
     * Rename cvs repository (following project unix_name change)
     *
     * @param Project $project Project to rename
     * @param String  $newName New name
     *
     * @return bool
     */
    public function renameCVSRepository($project, $newName)
    {
        if (rename($GLOBALS['cvs_prefix'] . '/' . $project->getUnixName(false), $GLOBALS['cvs_prefix'] . '/' . $newName)) {
            $this->renameLockDir($project, $newName);
            $this->renameLogInfoFile($project, $newName);
            $this->renameCommitInfoFile($project, $newName);
            return true;
        }
        return false;
    }

    /**
     * Rename CVS lock dir and corresponding file in repository
     *
     * @param Project $project Project to rename
     * @param String  $newName New name
     *
     * @return bool
     */
    public function renameLockDir($project, $newName)
    {
        $oldLockDir = $GLOBALS['cvslock_prefix'] . '/' . $project->getUnixName(false);
        $newLockDir = $GLOBALS['cvslock_prefix'] . '/' . $newName;
        if (is_dir($oldLockDir)) {
            rename($oldLockDir, $newLockDir);
        }

        $filename = $GLOBALS['cvs_prefix'] . '/' . $newName . '/CVSROOT/config';
        $this->_RcsCheckout($filename);
        $file = file_get_contents($filename);
        $file = preg_replace('%' . preg_quote($oldLockDir, '%') . '%m', $newLockDir, $file);
        file_put_contents($filename, $file);
        $this->_RcsCommit($filename);

        return true;
    }

    /**
     * Rename all project occurrences in the loginfo file
     *
     * @param Project $project Project to rename
     * @param String  $newName New name
     *
     * @return bool
     */
    public function renameLogInfoFile($project, $newName)
    {
        $filename = $GLOBALS['cvs_prefix'] . '/' . $newName . '/CVSROOT/loginfo';
        $this->_RcsCheckout($filename);
        $file = file_get_contents($filename);
        $file = preg_replace('%(\s+)' . preg_quote($project->getUnixName(false), '%') . '(\s+)%m', '$1' . $newName . '$2', $file);
        $file = preg_replace('%' . preg_quote($GLOBALS['cvs_prefix'] . '/' . $project->getUnixName(false), '%') . '%m', $GLOBALS['cvs_prefix'] . '/' . $newName, $file);
        file_put_contents($filename, $file);
        $this->_RcsCommit($filename);
        return true;
    }

    /**
     * Rename all project occurrences in the commit file
     *
     * @param Project $project Project to rename
     * @param String  $newName New name
     *
     * @return bool
     */
    public function renameCommitInfoFile($project, $newName)
    {
        $filename = $GLOBALS['cvs_prefix'] . '/' . $newName . '/CVSROOT/commitinfo';
        $this->_RcsCheckout($filename);
        $file = file_get_contents($filename);
        $file = preg_replace('%(\s+)' . preg_quote($project->getUnixName(false), '%') . '(\s+)%m', '$1' . $newName . '$2', $file);
        file_put_contents($filename, $file);
        $this->_RcsCommit($filename);
        return true;
    }
}
