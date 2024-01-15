<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
 */

use Tuleap\SVNCore\AccessFileReader;
use Tuleap\SVNCore\GetAllRepositories;
use Tuleap\SVNCore\SVNAccessFile;
use Tuleap\SVNCore\SvnAccessFileDefaultBlockGenerator;
use Tuleap\SVNCore\Exception\SVNRepositoryCreationException;
use Tuleap\SVNCore\Exception\SVNRepositoryLayoutInitializationException;
use Tuleap\SVNCore\Cache\ParameterDao;
use Tuleap\SVNCore\Cache\ParameterRetriever;
use Tuleap\URI\URIModifier;

class BackendSVN extends Backend
{
    public const PRE_COMMIT_HOOK          = 'pre-commit';
    public const POST_COMMIT_HOOK         = 'post-commit';
    public const PRE_REVPROP_CHANGE_HOOK  = 'pre-revprop-change';
    public const POST_REVPROP_CHANGE_HOOK = 'post-revprop-change';

    protected $SVNApacheConfNeedUpdate;

    /**
      * Protected for testing purpose
      */
    protected function getSvnDao()
    {
        return new SVN_DAO();
    }

    /**
     * Wrapper for Config
     *
     * @return ForgeConfig
     */
    protected function getConfig($var)
    {
        return ForgeConfig::get($var);
    }

    /**
     * @throws SVNRepositoryCreationException
     * @throws SVNRepositoryLayoutInitializationException
     */
    public function createRepositorySVN($project_id, $svn_dir, $hook_commit_path, PFUser $user, array $initial_layout)
    {
        if (! $this->createRepository($project_id, $svn_dir)) {
            throw new SVNRepositoryCreationException(_('Could not create/initialize SVN repository'));
        }

        $exception = null;
        try {
            $this->createDirectoryLayout($project_id, $svn_dir, $user, $initial_layout);
        } catch (SVNRepositoryLayoutInitializationException $layout_initialization_exception) {
            $exception = $layout_initialization_exception;
        }

        $params = [
            'project_id' => $project_id,
        ];

        EventManager::instance()->processEvent(
            Event::SVN_REPOSITORY_CREATED,
            $params
        );

        $project = $this->getProjectManager()->getProject($project_id);

        if (
            ! $this->updateHooks(
                $project,
                $svn_dir,
                true,
                $hook_commit_path,
                'svn_post_commit.php',
                ForgeConfig::get('tuleap_dir') . '/src/utils/php-launcher.sh',
                'svn_pre_commit.php'
            )
        ) {
            throw new SVNRepositoryCreationException(_('Could not update hooks of the SVN repository'));
        }

        if (! $this->createSVNAccessFile($project_id, $svn_dir)) {
            throw new SVNRepositoryCreationException(_('Could not the access file of the SVN repository'));
        }

        $this->forceUpdateApacheConf();

        if ($exception !== null) {
            throw $exception;
        }
    }

    private function createRepository($group_id, $system_path)
    {
        $project = $this->getProjectManager()->getProject($group_id);
        if (! $project) {
            return false;
        }

        if (! is_dir($system_path)) {
            // Let's create a SVN repository for this group
            if (! mkdir($system_path, 0775, true)) {
                $this->log("Can't create project SVN dir: $system_path", Backend::LOG_ERROR);
                return false;
            }
            system(ForgeConfig::get('svnadmin_cmd') . " create " . escapeshellarg($system_path) . " --fs-type fsfs");

            $this->setUserAndGroup($project, $system_path);
            system("chmod g+rw " . escapeshellarg($system_path));
        }

        return true;
    }

    private function createSVNAccessFile($group_id, $system_path)
    {
        $project = $this->getProjectManager()->getProject($group_id);

        if (! $this->updateSVNAccessForRepository($project, $system_path, null, null)) {
            $this->log("Can't update SVN access file", Backend::LOG_ERROR);
            return false;
        }

        return true;
    }

    private function forceUpdateApacheConf()
    {
        $this->setSVNApacheConfNeedUpdate();
    }

    /**
     * @throws SVNRepositoryLayoutInitializationException
     */
    private function createDirectoryLayout($project_id, $system_path, PFUser $user, array $initial_layout)
    {
        if (empty($initial_layout)) {
            return;
        }

        $filtered_layout = [];
        foreach ($initial_layout as $requested_path) {
            $path_to_create = URIModifier::removeDotSegments(
                URIModifier::removeEmptySegments($system_path . DIRECTORY_SEPARATOR . $requested_path)
            );
            if (strpos($path_to_create, $system_path) !== 0) {
                throw new SVNRepositoryLayoutInitializationException(sprintf(_('The directory %s is not valid'), $requested_path));
            }

            $path_to_create_encoded = URIModifier::normalizePercentEncoding($path_to_create);
            $filtered_layout[]      = escapeshellarg('file://' . $path_to_create_encoded);
        }

        $locale_switcher = new \Tuleap\Language\LocaleSwitcher();
        $locale_switcher->setLocaleForSpecificExecutionContext(
            $user->getLocale(),
            function () use ($project_id, $user, $filtered_layout): void {
                $user_name = $this->getUsernameUsableInSVN($project_id, $user);

                $result = $this->system('svn mkdir --username=' . escapeshellarg($user_name) .
                    ' --message ' . escapeshellarg(_('Initial layout creation')) . ' --parents ' . implode(' ', $filtered_layout));

                if ($result === false) {
                    throw new SVNRepositoryLayoutInitializationException(_('Could not commit repository initial layout'));
                }
            }
        );
    }

    /**
     * @return string
     */
    private function getUsernameUsableInSVN($project_id, PFUser $user)
    {
        $intro_information = false;
        EventManager::instance()->processEvent(Event::SVN_INTRO, [
            'svn_intro_in_plugin' => false,
            'svn_intro_info'      => &$intro_information,
            'group_id'            => $project_id,
            'user_id'             => $user->getId(),
        ]);
        $user_name = $user->getUserName();
        if ($intro_information !== false) {
            $user_name = $intro_information->getLogin();
        }

        return strtolower($user_name);
    }

    /**
     * Put in place the svn post-commit hook for email notification
     * if not present (if the file does not exist it is created)
     *
     * @param Project $project The project to work on
     *
     * @return bool true on success or false on failure
     */
    public function updateHooks(Project $project, $system_path, $can_change_svn_log, $hook_commit_path, $post_commit_file, $post_commit_launcher, $pre_commit_file)
    {
        $filename    = "$system_path/hooks/" . self::POST_COMMIT_HOOK;
        $update_hook = false;
        if (! is_file($filename)) {
            // File header
            $fp = @fopen($filename, 'w');
            if ($fp !== false) {
                fwrite($fp, "#!/bin/sh\n");
                fwrite($fp, "# POST-COMMIT HOOK\n");
                fwrite($fp, "#\n");
                fwrite($fp, "# The post-commit hook is invoked after a commit.  Subversion runs\n");
                fwrite($fp, "# this hook by invoking a program (script, executable, binary, etc.)\n");
                fwrite($fp, "# named 'post-commit' (for which this file is a template) with the \n");
                fwrite($fp, "# following ordered arguments:\n");
                fwrite($fp, "#\n");
                fwrite($fp, "#   [1] REPOS-PATH   (the path to this repository)\n");
                fwrite($fp, "#   [2] REV          (the number of the revision just committed)\n\n");
                fclose($fp);
                $update_hook = true;
            }
        } else {
            $file_array = file($filename);
            if (! in_array($this->block_marker_start, $file_array)) {
                $update_hook = true;
            }
        }
        if ($update_hook) {
            $command  = 'REPOS="$1"' . "\n";
            $command .= 'REV="$2"' . "\n";

            $command .= $post_commit_launcher . ' ' . $hook_commit_path . '/' . $post_commit_file . ' "$REPOS" "$REV" >/dev/null';

            $this->addBlock($filename, $command);
        }
        $this->chown($filename, $this->getHTTPUser());
        $this->chgrp($filename, $this->getSvnFilesUnixGroupName($project));
        chmod("$filename", 0775);

        // Put in place the Codendi svn pre-commit hook
        // if not present (if the file does not exist it is created)
        $filename    = "$system_path/hooks/" . self::PRE_COMMIT_HOOK;
        $update_hook = false;
        if (! is_file($filename)) {
            // File header
            $fp = @fopen($filename, 'w');
            if ($fp !== false) {
                fwrite($fp, "#!/bin/sh\n\n");
                fwrite($fp, "# PRE-COMMIT HOOK\n");
                fwrite($fp, "#\n");
                fwrite($fp, "# The pre-commit hook is invoked before a Subversion txn is\n");
                fwrite($fp, "# committed.  Subversion runs this hook by invoking a program\n");
                fwrite($fp, "# (script, executable, binary, etc.) named 'pre-commit' (for which\n");
                fwrite($fp, "# this file is a template), with the following ordered arguments:\n");
                fwrite($fp, "#\n");
                fwrite($fp, "#   [1] REPOS-PATH   (the path to this repository)\n");
                fwrite($fp, "#   [2] TXN-NAME     (the name of the txn about to be committed)\n");
                $update_hook = true;
            }
        } else {
            $file_array = file($filename);
            if (! in_array($this->block_marker_start, $file_array)) {
                $update_hook = true;
            }
        }
        if ($update_hook) {
            $command  = 'REPOS="$1"' . "\n";
            $command .= 'TXN="$2"' . "\n";
            $command .= ForgeConfig::get('codendi_dir') . '/src/utils/php-launcher.sh ' . $hook_commit_path . '/' . $pre_commit_file . ' "$REPOS" "$TXN" || exit 1';
            $this->addBlock($filename, $command);
        }
        $this->chown($filename, $this->getHTTPUser());
        $this->chgrp($filename, $this->getSvnFilesUnixGroupName($project));
        chmod("$filename", 0775);

        if ($can_change_svn_log) {
            try {
                $this->enableCommitMessageUpdate($system_path, $hook_commit_path);
            } catch (BackendSVNFileForSimlinkAlreadyExistsException $exception) {
                throw $exception;
            }
        } else {
            $this->disableCommitMessageUpdate($system_path);
        }

        return true;
    }

    public function updateSVNAccessForRepository(Project $project, $system_path, $ugroup_name, $ugroup_old_name)
    {
        $contents = $this->getCustomPermission($system_path);

        return $this->updateCustomSVNAccessForRepository($project, $system_path, $ugroup_name, $ugroup_old_name, $contents);
    }

    public function updateCustomSVNAccessForRepository(Project $project, $system_path, $ugroup_name, $ugroup_old_name, $contents)
    {
        $svn_access_file = new SVNAccessFile(SvnAccessFileDefaultBlockGenerator::instance()->getDefaultBlock($project));

        $custom_perms = $this->getCustomPermissionForProject($svn_access_file, $contents, $ugroup_name, $ugroup_old_name);

        return $this->updateSVNAccessFile($system_path, $custom_perms, $project);
    }

    private function getSvnAccessFile($system_path)
    {
        return $system_path . "/.SVNAccessFile";
    }

    private function getDefaultBlocEnd(): string
    {
        return AccessFileReader::END_MARKER . "\n";
    }

    private function getDefaultBlockStart(): string
    {
        // if you change these block markers also change them in src/www/svn/svn_utils.php
        return AccessFileReader::BEGIN_MARKER . "\n";
    }

    private function getCustomPermission($system_path): string
    {
        $contents = '';
        if (is_file($this->getSvnAccessFile($system_path))) {
            $svnaccess_array = file($this->getSvnAccessFile($system_path));
            $configlines     = false;

            while ($line = array_shift($svnaccess_array)) {
                if ($configlines) {
                    $contents .= $line;
                }
                if (strcmp($line, $this->getDefaultBlocEnd()) == 0) {
                    $configlines = 1;
                }
            }
        }

        return $contents;
    }

    private function getDefaultBlock(Project $project): string
    {
        return SvnAccessFileDefaultBlockGenerator::instance()->getDefaultBlock($project)->content;
    }

    private function getCustomPermissionForProject(SVNAccessFile $svn_access_file, $contents, $ugroup_name, $ugroup_old_name): string
    {
        $svn_access_file->setRenamedGroup($ugroup_name, $ugroup_old_name);
        return $svn_access_file->parseGroupLines($contents)->contents;
    }

    private function updateSVNAccessFile($system_path, $custom_perms, Project $project)
    {
        if (! is_dir($system_path)) {
            $this->log("Can't update SVN Access file: project SVN repo is missing: " . $system_path, Backend::LOG_ERROR);
            return false;
        }

        $svnaccess_file     = $this->getSvnAccessFile($system_path);
        $svnaccess_file_old = $this->getSvnAccessFile($system_path) . ".old";
        $svnaccess_file_new = $this->getSvnAccessFile($system_path) . ".new";


        // Retrieve custom permissions, if any
        $fp = fopen($svnaccess_file_new, 'w');

        // Codendi specifc
        fwrite($fp, $this->getDefaultBlockStart());
        fwrite($fp, $this->getDefaultBlock($project));
        fwrite($fp, $this->getDefaultBlocEnd());

        // Custom permissions
        if ($custom_perms) {
            fwrite($fp, $custom_perms);
        }
        fclose($fp);

        // Backup existing file and install new one if they are different
        $this->installNewFileVersion($svnaccess_file_new, $svnaccess_file, $svnaccess_file_old);

        // set group ownership, admin user as owner so that
        // PHP scripts can write to it directly
        $this->chown($svnaccess_file, $this->getHTTPUser());
        $this->chgrp($svnaccess_file, $this->getSvnFilesUnixGroupName($project));
        chmod("$svnaccess_file", 0775);

         return true;
    }

    /**
     * Force apache conf update
     *
     * @return void
     */
    public function setSVNApacheConfNeedUpdate()
    {
        $this->SVNApacheConfNeedUpdate = true;
    }

    /**
     * Say if apache conf need update
     *
     * @return bool
     */
    public function getSVNApacheConfNeedUpdate()
    {
        return $this->SVNApacheConfNeedUpdate;
    }

    /**
     * Add Subversion DAV definition for all projects in a dedicated Apache
     * configuration file
     *
     * @return bool true on success or false on failure
     */
    public function generateSVNApacheConf()
    {
        $svn_root_file     = ForgeConfig::get('svn_root_file');
        $svn_root_file_old = $svn_root_file . ".old";
        $svn_root_file_new = $svn_root_file . ".new";
        try {
            $conf = $this->getApacheConf();
        } catch (Exception $ex) {
            return false;
        }

        if (file_put_contents($svn_root_file_new, $conf) !== strlen($conf)) {
            $this->log("Error while writing to $svn_root_file_new", Backend::LOG_ERROR);
            return false;
        }

        $this->chown("$svn_root_file_new", $this->getHTTPUser());
        $this->chgrp("$svn_root_file_new", $this->getHTTPUser());
        chmod("$svn_root_file_new", 0640);

        // Backup existing file and install new one

        return $this->installNewFileVersion($svn_root_file_new, $svn_root_file, $svn_root_file_old, true);
    }

    /**
     * public for testing purpose
     */
    public function getApacheConf(): string
    {
        $get_all_repositories = EventManager::instance()->dispatch(
            new GetAllRepositories(
                $this->getSvnDao(),
                ProjectManager::instance()
            )
        );
        assert($get_all_repositories instanceof GetAllRepositories);

        $conf = new SVN_Apache_SvnrootConf(new SVN_Apache(), $get_all_repositories->getRepositories());

        return $conf->getFullConf();
    }

    /**
     * Make the svn repository of the project private or public
     *
     * @param Project $project    The project to work on
     * @param bool $is_private true if the repository is private
     *
     * @return bool true if success
     */
    public function setSVNPrivacy(Project $project, $is_private)
    {
        $perms   = $is_private ? 0770 : 0775;
        $svnroot = $project->getSVNRootPath();
        return is_dir($svnroot) && $this->chmod($svnroot, $perms);
    }

    public function setUserAndGroup(Project $project, $svnroot)
    {
        $group                    = $this->getSvnFilesUnixGroupName($project);
        $no_filter_file_extension = [];
        $this->recurseChownChgrp($svnroot, $this->getHTTPUser(), $group, $no_filter_file_extension);
        $this->chown($svnroot, $this->getHTTPUser());
        $this->chgrp($svnroot, $group);
    }

    private function getSvnFilesUnixGroupName(Project $project)
    {
        return $this->getUnixGroupNameForProject($project);
    }

    /**
     * Check if given name is not used by a repository or a file or a link
     *
     * @param String $name
     *
     * @return bool false if repository or file  or link already exists, true otherwise
     */
    public function isNameAvailable($name)
    {
        $path = ForgeConfig::get('svn_prefix') . "/" . $name;
        return (! $this->fileExists($path));
    }

    private function enableCommitMessageUpdate($project_svnroot, $hooks_path)
    {
        $hook_names = [self::PRE_REVPROP_CHANGE_HOOK, self::POST_REVPROP_CHANGE_HOOK];
        $hook_error = [];

        foreach ($hook_names as $hook_name) {
            if (! $this->enableHook($project_svnroot, $hook_name, "$hooks_path/$hook_name.php")) {
                $hook_error[] = $this->getHookPath($project_svnroot, $hook_name);
            }
        }

        if (! empty($hook_error)) {
            $exception_message = $this->buildExceptionMessage($hook_error);
            throw new BackendSVNFileForSimlinkAlreadyExistsException($exception_message);
        }
    }

    private function buildExceptionMessage(array $hook_error)
    {
        if (count($hook_error) > 1) {
            $exception_message = 'Files ' . implode(', ', $hook_error) . ' already exist';
        } else {
             $exception_message = 'File ' . implode($hook_error) . ' already exists';
        }

        return $exception_message;
    }

    private function enableHook($project_svnroot, $hook_name, $source_tool)
    {
        $path = $this->getHookPath($project_svnroot, $hook_name);

        if (file_exists($path) && ! $this->isLinkToTool($source_tool, $path)) {
            $message = "file $path already exists";

            $this->log($message, Backend::LOG_WARNING);
            return false;
        }

        if (! is_link($path)) {
            symlink($source_tool, $path);
        }

        return true;
    }

    private function isLinkToTool($tool_reference_path, $path)
    {
        return is_link($path) && realpath($tool_reference_path) == realpath(readlink($path));
    }

    private function disableCommitMessageUpdate($project_svnroot)
    {
        $this->deleteHook($project_svnroot, self::PRE_REVPROP_CHANGE_HOOK);
        $this->deleteHook($project_svnroot, self::POST_REVPROP_CHANGE_HOOK);
    }

    private function deleteHook($project_svnroot, $hook_name)
    {
        $path = $this->getHookPath($project_svnroot, $hook_name);
        if (is_link($path)) {
            unlink($path);
        }
    }

    private function getHookPath($project_svnroot, $hook_name)
    {
        return $project_svnroot . '/hooks/' . $hook_name;
    }

    /**
     * @return \Tuleap\SVNCore\Cache\Parameters
     */
    protected function getSVNCacheParameters()
    {
        $parameter_manager = new ParameterRetriever(new ParameterDao());
        return $parameter_manager->getParameters();
    }

    public function systemCheck(Project $project): void
    {
        // If no codendi_svnroot.conf file, force recreate.
        if (! is_file(ForgeConfig::get('svn_root_file'))) {
            $this->setSVNApacheConfNeedUpdate();
        }
    }
}
