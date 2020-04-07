<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 *
 */

class BackendSystem extends Backend
{


    protected $needRefreshUserCache = false;
    protected $needRefreshGroupCache = false;


    /**
     * Warn system that the user lists has been updated (new user)
     * This is required so that nscd (name service caching daemon) knows that it needs
     * to update its user data.
     *
     * NOTE: Don't need to update cache on deleted user since shadow information are not cached,
     * so even if the cache is not refreshed, a deleted user won't be able to login
     *
     * @return true on success, false otherwise
     */
    public function refreshUserCache()
    {
        if (! ForgeConfig::areUnixUsersAvailableOnSystem()) {
            $this->log('No homedir_prefix, refreshUserCache skipped', Backend::LOG_INFO);
            return true;
        }

        return (system("/usr/sbin/nscd --invalidate=passwd") !== false);
    }

    /**
     * set if we need to refresh the user cache
     *
     * @return null
     */
    public function setNeedRefreshUserCache()
    {
        $this->needRefreshUserCache = true;
    }

    /**
     * Return if we need to refresh the user cache
     *
     * @return bool
     */
    public function getNeedRefreshUserCache()
    {
        return $this->needRefreshUserCache;
    }

    /**
     * Warn system that the group lists has been updated (new group, new member to group or memeber removed from group)
     * This is required so that nscd (name service caching daemon) knows that it needs
     * to update its group data.
     *
     * NOTE: Currently, we don't update group cache on deleted group, new user and deleted user
     *
     * @return true on success, false otherwise
     */
    public function refreshGroupCache()
    {
        if (! ForgeConfig::areUnixGroupsAvailableOnSystem()) {
            $this->log('No grpdir_prefix, refreshGroupCache skipped', Backend::LOG_INFO);
            return true;
        }

        return (system("/usr/sbin/nscd --invalidate=group") !== false);
    }

    /**
     * set if we need to refresh the group cache
     *
     * @return null
    */
    public function setNeedRefreshGroupCache()
    {
        $this->needRefreshGroupCache = true;
    }

    /**
     * Return if we need to refresh the groupo cache
     *
     * @return bool
     */
    public function getNeedRefreshGroupCache()
    {
        return $this->needRefreshGroupCache;
    }

    /**
     * Hard reset of system related stuff (nscd for uid/gid and fs cache).
     *
     * Should be used before modification of system (new user, project, etc)
     *
     * @return null
     */
    public function flushNscdAndFsCache()
    {
        $this->refreshGroupCache();
        $this->refreshUserCache();
        clearstatcache();
    }

    /**
     * Ensure user home directory is created and has the right uid
     *
     * @param PFUser $user the user we want to sanitize his home
     *
     * @return null
     */
    public function userHomeSanityCheck(PFUser $user)
    {
        if (! ForgeConfig::areUnixUsersAvailableOnSystem()) {
            $this->log('No homedir_prefix, userHomeSanityCheck skipped', Backend::LOG_INFO);
            return true;
        }

        if (! $this->userHomeExists($user->getUserName())) {
            $this->createUserHome($user);
        }
        if (! $this->isUserHomeOwnedByUser($user)) {
            $this->setUserHomeOwnership($user);
        }
    }

    /**
     * Create user home directory
     *
     * Also copy files from the skel directory to the new home directory.
     * If the directory already exists, nothing is done.
     *
     * @param PFUser $user the user we want to create a home
     *
     * @return bool true if directory is successfully created, false otherwise
     */
    public function createUserHome(PFUser $user)
    {
        if (! ForgeConfig::areUnixUsersAvailableOnSystem()) {
            $this->log('No homedir_prefix, createUserHome skipped', Backend::LOG_INFO);
            return true;
        }

        $homedir = $user->getUnixHomeDir();

        if (!is_dir($homedir)) {
            if (mkdir($homedir, 0751)) {
                // copy the contents of the $codendi_shell_skel dir into homedir
                if (is_dir($GLOBALS['codendi_shell_skel'])) {
                    system("cd " . $GLOBALS['codendi_shell_skel'] . "; tar cf - . | (cd  $homedir ; tar xf - )");
                }
                touch($homedir);
                $this->setUserHomeOwnership($user);
            } else {
                $this->log("Can't create user home: $homedir", Backend::LOG_ERROR);
                return false;
            }
        } else {
            $this->log("User home already exists: $homedir", Backend::LOG_WARNING);
        }
        return true;
    }

    /**
     * Verify if given name exists as user home directory
     *
     * @param String $username the user name to test if home exists
     *
     * @return bool
     */
    public function userHomeExists($username)
    {
        return (is_dir(ForgeConfig::get('homedir_prefix') . "/" . $username));
    }

    /**
     * Verify is user home directory has the right uid
     *
     * @param PFUser $user the user needed to verify his home directory
     *
     * @return bool
     */
    private function isUserHomeOwnedByUser(PFUser $user)
    {
        $stat = stat($user->getUnixHomeDir());
        if ($stat) {
            if ($stat['uid'] != $user->getRealUnixUID()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Set user's uid/gid on its home directory (recursively)
     *
     * @param PFUser $user user to set uid/gid
     *
     * @return null
     */
    private function setUserHomeOwnership(PFUser $user)
    {
        $no_filter_file_extension = array();
        $this->recurseChownChgrp(
            $user->getUnixHomeDir(),
            $user->getUserName(),
            $user->getUserName(),
            $no_filter_file_extension
        );
    }

    /**
     * Create project home directory
     * If the directory already exists, nothing is done.
     *
     * @param int $group_id a group id
     *
     * @return bool true if directory is successfully created, false otherwise
     */
    public function createProjectHome($group_id)
    {
        $project = $this->getProjectManager()->getProject($group_id);
        if (! $project || $project->isDeleted()) {
            return false;
        }

        if (ForgeConfig::areUnixGroupsAvailableOnSystem()) {
            $this->createProjectWebDirectory($project);
            $this->createProjectAnonymousFTP($project);
        } else {
            $this->log('No "grpdir_prefix", skip web directory and anonymous ftp', Backend::LOG_INFO);
        }

        return $this->createProjectFRSDirectory($project);
    }

    private function createProjectWebDirectory(Project $project)
    {
        $unix_group_name = $project->getUnixNameMixedCase();
        $projdir         = ForgeConfig::get('grpdir_prefix') . "/" . $unix_group_name;
        $ht_dir          = $projdir . "/htdocs";
        $private_dir     = $projdir . '/private';

        if (!is_dir($projdir)) {
            // Lets create the group's homedir.
            // (put the SGID sticky bit on all dir so that all files
            // in there are owned by the project group and not
            // the user own group
            // Moreover, we need to chmod after mkdir because the umask may not allow the precised mode
            if (mkdir($projdir, 0775)) {
                $this->chown($projdir, "dummy");
                $this->chgrp($projdir, $unix_group_name);
                $this->chmod($projdir, 02775);
            } else {
                $this->log("Can't create project home: $projdir", Backend::LOG_ERROR);
                return false;
            }
        } else {
            // Get directory stat
            $stat = stat("$projdir");
            if ($stat && $stat['gid'] != $project->getUnixGID()) {
                $this->log("Restoring ownership on project dir: $projdir", Backend::LOG_WARNING);
                $no_filter_file_extension = array();
                $this->recurseChgrp($projdir, $unix_group_name, $no_filter_file_extension);
            }
        }

        // /!\ Be careful not to lowercase the whole path as it will also modify
        // parents case and will lead to errors. Eg:
        // /Toto/home/groups/TestProj -> /toto/home/groups/testproj
        // instead of
        // /Toto/home/groups/TestProj -> /Toto/home/groups/testproj
        $lcProjectDir = ForgeConfig::get('grpdir_prefix') . "/" . $project->getUnixName(true);
        if ($projdir != $lcProjectDir) {
            $lcprojlnk = $lcProjectDir;
            if (!is_link($lcprojlnk)) {
                if (!symlink($projdir, $lcprojlnk)) {
                    $this->log("Can't create project link: $lcprojlnk", Backend::LOG_ERROR);
                }
            }
        }

        if (!is_dir($ht_dir)) {
            // Project web site directory
            if (mkdir($ht_dir, 0775)) {
                $this->chown($ht_dir, "dummy");
                $this->chgrp($ht_dir, $unix_group_name);
                chmod($ht_dir, 02775);

                // Copy custom homepage template for project web site if any
                $dest_homepage    = '';
                $custom_homepage  = ForgeConfig::get('sys_custom_incdir') . '/en_US/others/default_page.php';
                $default_homepage = ForgeConfig::get('sys_incdir') . '/en_US/others/default_page.html';
                if (is_file($custom_homepage)) {
                    $dest_homepage = $ht_dir . '/index.php';
                    copy($custom_homepage, $dest_homepage);
                } elseif (is_file($default_homepage)) {
                    $dest_homepage = $ht_dir . '/index.html';
                    copy($default_homepage, $dest_homepage);
                }
                if (is_file($dest_homepage)) {
                    $this->chown($dest_homepage, "dummy");
                    $this->chgrp($dest_homepage, $unix_group_name);
                    chmod($dest_homepage, 0664);
                }
            } else {
                $this->log("Can't create project web root: $ht_dir", Backend::LOG_ERROR);
                return false;
            }
        }

        if (!is_dir($private_dir)) {
            if (mkdir($private_dir, 0770)) {
                $this->chmod($private_dir, 02770);
                $this->chown($private_dir, "dummy");
                $this->chgrp($private_dir, $unix_group_name);
            } else {
                $this->log("Can't create project private dir: $private_dir", Backend::LOG_ERROR);
                return false;
            }
        } else {
            // Check that perms are OK
            $perms = fileperms($private_dir);
            // 'others' should have no right on the repository
            if (($perms & 0x0004) || ($perms & 0x0002) || ($perms & 0x0001) || ($perms & 0x0200)) {
                $this->chmod($private_dir, 02770);
            }
            // Get directory stat
            $stat = stat("$private_dir");
            if ($stat) {
                $dummy_user = posix_getpwnam('dummy');
                if (
                    ($stat['uid'] != $dummy_user['uid'])
                    || ($stat['gid'] != $project->getUnixGID())
                ) {
                    $this->log("Restoring privacy on private dir: $private_dir", Backend::LOG_WARNING);
                    $this->chown($private_dir, "dummy");
                    $this->chgrp($private_dir, $unix_group_name);
                }
            }
        }

        return true;
    }

    private function createProjectAnonymousFTP(Project $project)
    {
        $unix_group_name = $project->getUnixNameMixedCase();
        $ftp_anon_dir    = ForgeConfig::get('ftp_anon_dir_prefix') . "/" . $unix_group_name;

        if (is_dir(ForgeConfig::get('ftp_anon_dir_prefix'))) {
            if (!is_dir($ftp_anon_dir)) {
                // Now lets create the group's ftp homedir for anonymous ftp space
                // This one must be owned by the project gid so that all project
                // admins can work on it (upload, delete, etc...)
                if (mkdir($ftp_anon_dir, 02775)) {
                    $this->chown($ftp_anon_dir, "dummy");
                    $this->chgrp($ftp_anon_dir, $unix_group_name);
                    chmod($ftp_anon_dir, 02775);
                } else {
                    $this->log("Can't create project public ftp dir: $ftp_anon_dir", Backend::LOG_ERROR);
                    return false;
                }
            }
        } else {
            $this->log("Skip create project public ftp dir: $ftp_anon_dir", Backend::LOG_INFO);
        }
        return true;
    }

    private function createProjectFRSDirectory(Project $project)
    {
        $unix_group_name = $project->getUnixNameMixedCase();
        $ftp_frs_dir     = ForgeConfig::get('ftp_frs_dir_prefix') . "/" . $unix_group_name;

        if (is_dir(ForgeConfig::get('ftp_frs_dir_prefix'))) {
            if (!is_dir($ftp_frs_dir)) {
                // Now lets create the group's ftp homedir for anonymous ftp space
                // This one must be owned by the project gid so that all project
                // admins can work on it (upload, delete, etc...)
                if (mkdir($ftp_frs_dir, 0771)) {
                    chmod($ftp_frs_dir, 0771);
                    $this->chown($ftp_frs_dir, "dummy");
                    $this->chgrp($ftp_frs_dir, $this->getUnixGroupNameForProject($project));
                } else {
                    $this->log("Can't create project file release dir: $ftp_frs_dir", Backend::LOG_ERROR);
                    return false;
                }
            }
        } else {
            $this->log("Skip create project file release dir: $ftp_frs_dir", Backend::LOG_INFO);
        }
        return true;
    }

    /**
     * Archive the user home directory
     *
     * @param int $user_id a user id needed to find the home dir to archive
     *
     * @return bool true if directory is successfully archived, false otherwise
     */
    public function archiveUserHome($user_id)
    {
        if (! ForgeConfig::areUnixUsersAvailableOnSystem()) {
            $this->log('No homedir_prefix, archiveUserHome skipped', Backend::LOG_INFO);
            return true;
        }

        $user = $this->getUserManager()->getUserById($user_id);
        if (!$user) {
            return false;
        }
        $homedir = ForgeConfig::get('homedir_prefix') . "/" . $user->getUserName();
        $backupfile = ForgeConfig::get('sys_project_backup_path') . "/" . $user->getUserName() . ".tgz";

        if (is_dir($homedir)) {
            system("cd " . ForgeConfig::get('homedir_prefix') . "; tar cfz $backupfile " . $user->getUserName());
            chmod($backupfile, 0600);
            $this->recurseDeleteInDir($homedir);
            rmdir($homedir);
        }
        return true;
    }


    /**
     * Archive the project directory
     *
     * @param int $group_id the group id used to find the home directory to archive
     *
     * @return bool true if directory is successfully archived, false otherwise
     */
    public function archiveProjectHome($group_id)
    {
        if (! ForgeConfig::areUnixGroupsAvailableOnSystem()) {
            $this->log('No grpdir_prefix, archiveProjectHome skipped', Backend::LOG_INFO);
            return true;
        }

        $project = $this->getProjectManager()->getProject($group_id);
        if (!$project) {
            return false;
        }
        $mydir = ForgeConfig::get('grpdir_prefix') . "/" . $project->getUnixName(false);
        $backupfile = ForgeConfig::get('sys_project_backup_path') . "/" . $project->getUnixName(false) . ".tgz";

        if (is_dir($mydir)) {
            system("cd " . ForgeConfig::get('grpdir_prefix') . "; tar cfz $backupfile " . $project->getUnixName(false));
            chmod($backupfile, 0600);
            $this->recurseDeleteInDir($mydir);
            rmdir($mydir);

            // Remove lower-case symlink if it exists
            if ($project->getUnixName(true) != $project->getUnixName(false)) {
                if (is_link(ForgeConfig::get('grpdir_prefix') . "/" . $project->getUnixName(true))) {
                    unlink(ForgeConfig::get('grpdir_prefix') . "/" . $project->getUnixName(true));
                }
            }
        }
        return true;
    }

    /**
     * Archive ftp elements for a given project.
     * It would delete FTP directory content of the project
     * and create a Tarball in temp dir.
     *
     * @param int $group_id the group id
     *
     * @return bool
     */
    public function archiveProjectFtp($group_id)
    {
        if (! is_dir(ForgeConfig::get('ftp_anon_dir_prefix'))) {
            $this->log('No ftp_anon_dir_prefix, archiveProjectFtp skipped', Backend::LOG_INFO);
            return true;
        }

        $project = $this->getProjectManager()->getProject($group_id);
        if (!$project) {
            return false;
        }
        $anonymousFTP = ForgeConfig::get('ftp_anon_dir_prefix') . "/" . $project->getUnixName(false);
        $backupfile   = ForgeConfig::get('sys_project_backup_path') . "/" . $project->getUnixName(false) . "-ftp.tgz";
        if (is_dir($anonymousFTP)) {
            system("cd " . ForgeConfig::get('ftp_anon_dir_prefix') . "; tar cfz $backupfile " . $project->getUnixName(false));
            chmod($backupfile, 0600);
            $this->recurseDeleteInDir($anonymousFTP);
        }
        return true;
    }

    /**
     * Remove deleted releases and released files
     *
     * @return bool the status
     */
    public function cleanupFRS()
    {
        $status = true;
        // Purge all deleted files older than 3 days old
        $delay = (int) ForgeConfig::get('sys_file_deletion_delay', 3);
        $time  = $_SERVER['REQUEST_TIME'] - (3600 * 24 * $delay);

        if (is_dir(ForgeConfig::get('ftp_frs_dir_prefix'))) {
            $frs = $this->getFRSFileFactory();
            $status =  $frs->moveFiles($time, $this);
        }

        if (is_dir(ForgeConfig::get('sys_wiki_attachment_data_dir'))) {
            $wiki   = $this->getWikiAttachment();
            $status = $status && $wiki->purgeAttachments($time);
        }

        $em = EventManager::instance();
        $em->processEvent('backend_system_purge_files', array('time' => $time));

        return $status;
    }

    /**
     * dumps SSH authorized_keys into all users homedirs
     *
     * @return bool always true
     */
    public function dumpSSHKeys()
    {
        if (ForgeConfig::areUnixUsersAvailableOnSystem()) {
            $sshkey_dumper = new User_SSHKeyDumper($this);
            $user_manager  = $this->getUserManager();
            foreach ($user_manager->getUsersWithSshKey() as $user) {
                $sshkey_dumper->writeSSHKeys($user);
            }
        }
        EventManager::instance()->processEvent(Event::DUMP_SSH_KEYS, array());
        return true;
    }

    /**
     * dumps SSH authorized_keys for a user in its homedir
     *
     * @param PFUser $user the user we want to dump his key
     * @param string $original_keys the original keys of the user
     *
     * @return bool if the ssh key was written
     */
    public function dumpSSHKeysForUser(PFUser $user, $original_keys)
    {
        $sshkey_dumper = new User_SSHKeyDumper($this);
        return $sshkey_dumper->writeSSHKeys($user);
    }

    /**
     * Check if repository of given project exists
     *
     * @param Project $project project to test if home exist
     *
     * @return bool true is repository already exists, false otherwise
     */
    public function projectHomeExists($project)
    {
        $unix_group_name = $project->getUnixName(false); // May contain upper-case letters
        $home_dir = ForgeConfig::get('grpdir_prefix') . "/" . $unix_group_name;
        if (is_dir($home_dir)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if given name is not used by a repository or a file or a link under project directories
     *
     * Return false if repository or file  or link already exists:
     **  with the same name under the grp_dir
     **  with its lower case name under the grp_dir
     **  under FRS
     **  under ftp anon
     * true otherwise
     *
     * @param String $name the project name to test
     *
     * @return bool
     */
    public function isProjectNameAvailable($name)
    {
        if (! ForgeConfig::areUnixGroupsAvailableOnSystem()) {
            return true;
        }
        $dir = ForgeConfig::get('grpdir_prefix') . "/" . $name;
        $frs = $GLOBALS['ftp_frs_dir_prefix'] . "/" . $name;
        $ftp = ForgeConfig::get('ftp_anon_dir_prefix') . "/" . $name;

        if ($this->fileExists($dir)) {
            return false;
        } elseif ($name != strtolower($name)) {
            $link = ForgeConfig::get('grpdir_prefix') . "/" . strtolower($name);
            if ($this->fileExists($link)) {
                return false;
            }
        }
        if ($this->fileExists($frs)) {
            return false;
        } elseif ($this->fileExists($ftp)) {
            return false;
        }
        return true;
    }

    /**
     * Check if given name is not used by a repository or a file or a link under user directory
     *
     * @param String $name a user name to test availability
     *
     * @return bool false if repository or file  or link already exists, true otherwise
     */
    public function isUserNameAvailable($name)
    {
        if (! ForgeConfig::areUnixUsersAvailableOnSystem()) {
            return true;
        }
        $path = ForgeConfig::get('homedir_prefix') . "/" . $name;
        return (!$this->fileExists($path));
    }


    /**
     * Rename project home directory (following project unix_name change)
     *
     * @param Project $project a project to rename
     * @param String  $newName the new name of the project
     *
     * @return bool
     */
    public function renameProjectHomeDirectory($project, $newName)
    {
        if (! ForgeConfig::areUnixGroupsAvailableOnSystem()) {
            $this->log('No grpdir_prefix, renameProjectHomeDirectory skipped', Backend::LOG_INFO);
            return true;
        }

        if (is_link(ForgeConfig::get('grpdir_prefix') . '/' . $newName)) {
            unlink(ForgeConfig::get('grpdir_prefix') . '/' . $newName);
            return rename(ForgeConfig::get('grpdir_prefix') . '/' . $project->getUnixName(false), ForgeConfig::get('grpdir_prefix') . '/' . $newName);
        } else {
            $renamed = rename(ForgeConfig::get('grpdir_prefix') . '/' . $project->getUnixName(false), ForgeConfig::get('grpdir_prefix') . '/' . $newName);
            if ($renamed) {
                if (is_link(ForgeConfig::get('grpdir_prefix') . '/' . $project->getUnixName(true))) {
                    unlink(ForgeConfig::get('grpdir_prefix') . '/' . $project->getUnixName(true));
                }
                if (strtolower($newName) != $newName) {
                    return symlink(ForgeConfig::get('grpdir_prefix') . '/' . $newName, ForgeConfig::get('grpdir_prefix') . '/' . strtolower($newName));
                } else {
                    return true;
                }
            }
            return $renamed;
        }
    }

    /**
     * Rename Directory where the released files are located (following project unix_name change)
     *
     * @param Project $project a project
     * @param String  $newName a new name
     *
     * @return bool
     */
    public function renameFileReleasedDirectory($project, $newName)
    {
        if (is_dir($GLOBALS['ftp_frs_dir_prefix'] . '/' . $project->getUnixName(false))) {
            return rename($GLOBALS['ftp_frs_dir_prefix'] . '/' . $project->getUnixName(false), $GLOBALS['ftp_frs_dir_prefix'] . '/' . $newName);
        } else {
            return true;
        }
    }

    /**
     * Rename anon ftp project homedir (following project unix_name change)
     *
     * @param Project $project a project
     * @param String  $newName a new name
     *
     * @return bool
     */
    public function renameAnonFtpDirectory($project, $newName)
    {
        if (! is_dir(ForgeConfig::get('ftp_anon_dir_prefix'))) {
            $this->log('No ftp_anon_dir_prefix, renameAnonFtpDirectory skipped', Backend::LOG_INFO);
            return true;
        }

        if (is_dir(ForgeConfig::get('ftp_anon_dir_prefix') . '/' . $project->getUnixName(false))) {
            return rename(ForgeConfig::get('ftp_anon_dir_prefix') . '/' . $project->getUnixName(false), ForgeConfig::get('ftp_anon_dir_prefix') . '/' . $newName);
        } else {
            return true;
        }
    }

    /**
     * Rename User home directory
     *
     * @param PFUser    $user    a user
     * @param String  $newName the new name of user home directory
     *
     * @return bool
     */
    public function renameUserHomeDirectory($user, $newName)
    {
        if (! ForgeConfig::areUnixUsersAvailableOnSystem()) {
            $this->log('No homedir_prefix, renameUserHomeDirectory skipped', Backend::LOG_INFO);
            return true;
        }

        return rename(ForgeConfig::get('homedir_prefix') . '/' . $user->getUserName(), ForgeConfig::get('homedir_prefix') . '/' . $newName);
    }

    /**
     * Wrapper for getFRSFileFactory
     *
     * @return FRSFileFactory
     */
    protected function getFRSFileFactory()
    {
        return new FRSFileFactory();
    }

    /**
     * Wrapper for getWikiAttachment
     *
     * @return WikiAttachment
     */
    protected function getWikiAttachment()
    {
        return new WikiAttachment();
    }
}
