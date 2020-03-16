<?php
/**
 * Copyright (c) Enalean, 2015. All rights reserved
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>
 */

class Git_Gitolite_GitoliteConfWriter
{

    public const GITOLITE_CONF_FILE = "conf/gitolite.conf";

    /** @var Git_Gitolite_GitoliteRCReader */
    private $gitoliterc_reader;

    /** @var Git_Gitolite_ConfigPermissionsSerializer */
    private $permissions_serializer;

    /** @var Git_Gitolite_ProjectSerializer */
    private $project_serializer;

    /** @var string */
    private $gitolite_administration_path;

    /** @var Git_Mirror_MirrorDataMapper */
    private $mirror_data_mapper;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var ProjectManager */
    private $project_manager;

    public function __construct(
        Git_Gitolite_ConfigPermissionsSerializer $permissions_serializer,
        Git_Gitolite_ProjectSerializer $project_serializer,
        Git_Gitolite_GitoliteRCReader $gitoliterc_reader,
        Git_Mirror_MirrorDataMapper $mirror_data_mapper,
        \Psr\Log\LoggerInterface $logger,
        ProjectManager $project_manager,
        $gitolite_administration_path
    ) {
        $this->gitoliterc_reader            = $gitoliterc_reader;
        $this->permissions_serializer       = $permissions_serializer;
        $this->project_serializer           = $project_serializer;
        $this->mirror_data_mapper           = $mirror_data_mapper;
        $this->logger                       = $logger;
        $this->gitolite_administration_path = $gitolite_administration_path;
        $this->project_manager              = $project_manager;
    }

    public function writeGitoliteConfiguration()
    {
        $git_modifications = new Git_Gitolite_GitModifications();
        $hostname          = $this->gitoliterc_reader->getHostname();

        if ($hostname) {
            $this->writeGitoliteConfigurationOnDisk($this->permissions_serializer->getGitoliteDotConfForHostname($this->getProjectList()), $git_modifications);
            $this->writeGitoliteIncludesInHostnameFile($hostname, $git_modifications, $this->getProjectList());

            return $git_modifications;
        }

        $this->writeGitoliteConfigurationOnDisk($this->permissions_serializer->getGitoliteDotConf($this->getProjectList()), $git_modifications);

        return $git_modifications;
    }

    public function renameProject($old_name, $new_name)
    {
        $git_modifications = new Git_Gitolite_GitModifications();
        $project           = $this->project_manager->getProjectByUnixName($new_name);

        $this->moveProjectFiles($old_name, $new_name, $git_modifications, $project);
        $this->modifyProjectConf($old_name, $new_name, $git_modifications, $project);
        $this->modifyIncludersConf($old_name, $new_name, $git_modifications, $project);

        return $git_modifications;
    }

    /**
     * @return Git_Gitolite_GitModifications
     */
    public function dumpProjectRepoConf(Project $project)
    {
        $git_modifications = new Git_Gitolite_GitModifications();
        $hostname          = $this->gitoliterc_reader->getHostname();

        if ($hostname) {
            $this->dumpProjectRepoConfForMirrors($project, $git_modifications);
        }

        $config_file_content = $this->project_serializer->dumpProjectRepoConf($project);
        $this->modifyGitConfigurationFileInGitolite($project, $git_modifications, $config_file_content);

        return $git_modifications;
    }

    /**
     * @return Git_Gitolite_GitModifications
     */
    public function dumpSuspendedProjectRepositoriesConfiguration(Project $project)
    {
        $git_modifications = new Git_Gitolite_GitModifications();
        $hostname          = $this->gitoliterc_reader->getHostname();

        if ($hostname) {
            $this->dumpSuspendedProjectRepositoriesConfigurationForMirrors($project, $git_modifications);
        }

        $config_file_content = $this->project_serializer->dumpSuspendedProjectRepositoriesConfiguration($project);
        $this->modifyGitConfigurationFileInGitolite($project, $git_modifications, $config_file_content);

        return $git_modifications;
    }

    private function modifyGitConfigurationFileInGitolite(
        Project $project,
        Git_Gitolite_GitModifications $git_modifications,
        $config_file_content
    ) {
        $this->logger->debug("Get Project Permission Conf File: " . $project->getUnixName() . "...");
        $config_file = $this->getProjectPermissionConfFile($project);
        $this->logger->debug("Get Project Permission Conf File: " . $project->getUnixName() . ": done");

        $this->logger->debug("Write Git config: " . $project->getUnixName() . "...");
        $this->writeGitConfig($config_file, $config_file_content, $git_modifications);
        $this->logger->debug("Write Git config: " . $project->getUnixName() . ": done");
    }

    public function updateMirror($mirror_id, $old_hostname)
    {
        $git_modifications = new Git_Gitolite_GitModifications();

        if (! $this->gitoliterc_reader->getHostname()) {
            return $git_modifications;
        }

        $mirror = $this->mirror_data_mapper->fetch($mirror_id);

        if ($old_hostname && ($old_hostname != $mirror->hostname)) {
            $this->removeMirrorConfiguration($old_hostname, $git_modifications);
        }

        if ($mirror->hostname) {
            $this->dumpConfForMirror($mirror, $git_modifications);
        }

        return $git_modifications;
    }

    public function deleteMirror($hostname)
    {
        $git_modifications = new Git_Gitolite_GitModifications();

        if (! ($this->gitoliterc_reader->getHostname() && $hostname)) {
            return $git_modifications;
        }

        $this->removeMirrorConfiguration($hostname, $git_modifications);

        return $git_modifications;
    }

    private function dumpConfForMirror(Git_Mirror_Mirror $mirror, Git_Gitolite_GitModifications $git_modifications)
    {
        $projects = $this->mirror_data_mapper->fetchAllProjectsConcernedByAMirror($mirror);

        foreach ($projects as $project) {
            $this->dumpProjectRepoConfForAGivenMirror($project, $mirror, $git_modifications);
        }

        $this->updateMirrorIncludes($mirror, $git_modifications);
    }

    private function removeMirrorConfiguration($hostname, Git_Gitolite_GitModifications $git_modifications)
    {
        $git_modifications->remove($this->getConfFolderForHostname($hostname));
        $git_modifications->remove($this->getRelativeConfigFilePathFromHostname($hostname));
    }

    private function dumpProjectRepoConfForMirrors(Project $project, Git_Gitolite_GitModifications $git_modifications)
    {
        $mirrors = $this->mirror_data_mapper->fetchAllForProject($project);
        foreach ($mirrors as $mirror) {
            $this->dumpProjectRepoConfForAGivenMirror($project, $mirror, $git_modifications);
            $this->updateMirrorIncludes($mirror, $git_modifications);
        }
    }

    private function dumpSuspendedProjectRepositoriesConfigurationForMirrors(
        Project $project,
        Git_Gitolite_GitModifications $git_modifications
    ) {
        $mirrors = $this->mirror_data_mapper->fetchAllForProject($project);
        foreach ($mirrors as $mirror) {
            $this->dumpSuspendedProjectRepositoriesConfigurationForAGivenMirror($project, $mirror, $git_modifications);
            $this->updateMirrorIncludes($mirror, $git_modifications);
        }
    }

    private function updateMirrorIncludes(Git_Mirror_Mirror $mirror, Git_Gitolite_GitModifications $git_modifications)
    {
        if (empty($mirror->hostname)) {
            return;
        }

        $projects_list = $this->getProjectsListForMirror($mirror);
        $this->writeGitoliteMirrorIncludes($mirror->hostname, $git_modifications, $projects_list);
    }

    private function dumpProjectRepoConfForAGivenMirror(Project $project, Git_Mirror_Mirror $mirror, Git_Gitolite_GitModifications $git_modifications)
    {
        if (empty($mirror->hostname)) {
            return;
        }

        $repositories = $this->mirror_data_mapper->fetchAllProjectRepositoriesForMirror($mirror, array($project->getGroupId()));
        $this->createConfFolderForMirrorIfNeeded($mirror);

        $config_file  = $this->getProjectPermissionConfFileForMirror($project, $mirror);
        $this->writeGitConfig($config_file, $this->project_serializer->dumpPartialProjectRepoConf($project, $repositories), $git_modifications);
    }

    private function dumpSuspendedProjectRepositoriesConfigurationForAGivenMirror(
        Project $project,
        Git_Mirror_Mirror $mirror,
        Git_Gitolite_GitModifications $git_modifications
    ) {
        if (empty($mirror->hostname)) {
            return;
        }

        $repositories = $this->mirror_data_mapper->fetchAllProjectRepositoriesForMirror($mirror, array($project->getGroupId()));
        $this->createConfFolderForMirrorIfNeeded($mirror);

        $config_file  = $this->getProjectPermissionConfFileForMirror($project, $mirror);
        $this->writeGitConfig(
            $config_file,
            $this->project_serializer->dumpPartialSuspendedProjectRepositoriesConfiguration(
                $project,
                $repositories
            ),
            $git_modifications
        );
    }

    private function getProjectPermissionConfFile(Project $project)
    {
        $prjConfDir = 'conf/projects';
        if (!is_dir($prjConfDir)) {
            mkdir($prjConfDir);
        }
        return $prjConfDir . '/' . $project->getUnixName() . '.conf';
    }

    private function getProjectPermissionConfFileForMirror(Project $project, Git_Mirror_Mirror $mirror)
    {
        return $this->getConfFolderForMirror($mirror) . '/' . $project->getUnixName() . '.conf';
    }

    private function createConfFolderForMirrorIfNeeded(Git_Mirror_Mirror $mirror)
    {
        if (!is_dir($this->getConfFolderForMirror($mirror))) {
            mkdir($this->getConfFolderForMirror($mirror));
        }
    }

    private function getConfFolderForMirror(Git_Mirror_Mirror $mirror)
    {
        return $this->getConfFolderForHostname($mirror->hostname);
    }

    private function getConfFolderForHostname($hostname)
    {
        return 'conf/' . $hostname;
    }

    private function writeGitConfig($config_file, $config_datas, Git_Gitolite_GitModifications $git_modifications)
    {
        file_put_contents($config_file, $config_datas);
        $git_modifications->add($config_file);
    }

    private function writeGitoliteConfigurationOnDisk($content, Git_Gitolite_GitModifications $git_modifications)
    {
        file_put_contents($this->getGitoliteConfFilePath(), $content);
        $git_modifications->add(self::GITOLITE_CONF_FILE);
    }

    private function getGitoliteConfFilePath()
    {
        return $this->gitolite_administration_path . '/' . self::GITOLITE_CONF_FILE;
    }

    private function modifyIncludersConf($old_name, $new_name, Git_Gitolite_GitModifications $git_modifications, Project $project)
    {
        $file_path = $this->getFullConfigFilePathFromHostname($this->gitoliterc_reader->getHostname());
        $this->proceedRenameInIncluderConf($file_path, $old_name, $new_name);
        $git_modifications->add($file_path);

        if (! $this->gitoliterc_reader->getHostname()) {
            return;
        }

        $mirrors = $this->mirror_data_mapper->fetchAllForProject($project);

        foreach ($mirrors as $mirror) {
            $this->modifyIncludersConfForMirror($old_name, $new_name, $git_modifications, $mirror);
        }
    }

    private function modifyIncludersConfForMirror($old_name, $new_name, Git_Gitolite_GitModifications $git_modifications, Git_Mirror_Mirror $mirror)
    {
        if (empty($mirror->hostname)) {
            return;
        }

        $file_path = $this->getFullConfigFilePathFromHostname($mirror->hostname);
        $this->proceedRenameInMirrorIncluderConf($mirror->hostname, $file_path, $old_name, $new_name);
        $git_modifications->add($file_path);
    }

    private function proceedRenameInMirrorIncluderConf($hostname, $file_path, $old_name, $new_name)
    {
        $orig = file_get_contents($file_path);
        $dest = str_replace('include "' . $hostname . '/' . $old_name . '.conf"', 'include "' . $hostname . '/' . $new_name . '.conf"', $orig);
        file_put_contents($file_path, $dest);
    }

    private function proceedRenameInIncluderConf($file_path, $old_name, $new_name)
    {
        $orig = file_get_contents($file_path);
        $dest = str_replace('include "projects/' . $old_name . '.conf"', 'include "projects/' . $new_name . '.conf"', $orig);
        file_put_contents($file_path, $dest);
    }

    private function modifyProjectConf($old_name, $new_name, Git_Gitolite_GitModifications $git_modifications, Project $project)
    {
        $original_file = 'conf/projects/' . $old_name . '.conf';

        $this->proceedToRenameInSpecifiedProjectFile($original_file, $old_name, $new_name);
        $git_modifications->add($original_file);

        if (! $this->gitoliterc_reader->getHostname()) {
            return;
        }

        $mirrors = $this->mirror_data_mapper->fetchAllForProject($project);

        foreach ($mirrors as $mirror) {
            $this->modifyProjectConfForMirror($old_name, $new_name, $git_modifications, $mirror, $project);
        }
    }

    private function modifyProjectConfForMirror($old_name, $new_name, Git_Gitolite_GitModifications $git_modifications, Git_Mirror_Mirror $mirror, Project $project)
    {
        if (empty($mirror->hostname)) {
            return;
        }
        $original_file = dirname($this->getProjectPermissionConfFileForMirror($project, $mirror)) . '/' . $old_name . '.conf';
        $this->proceedToRenameInSpecifiedProjectFile($original_file, $old_name, $new_name);

        $git_modifications->add($original_file);
    }

    private function proceedToRenameInSpecifiedProjectFile($project_file_path, $old_name, $new_name)
    {
        $orig = file_get_contents($project_file_path);

        $dest = preg_replace('`(^|\n)repo ' . preg_quote($old_name, '`') . '/`', '$1repo ' . $new_name . '/', $orig);
        $dest = str_replace('@' . $old_name . '_project_', '@' . $new_name . '_project_', $dest);
        $dest = preg_replace("%" . preg_quote($old_name, '%') . "/(.*) = \"%", "$new_name/$1 = \"", $dest);
        file_put_contents($project_file_path, $dest);
    }

    private function moveProjectFiles($old_name, $new_name, Git_Gitolite_GitModifications $git_modifications, Project $project)
    {
        $old_file = 'conf/projects/' . $old_name . '.conf';
        $new_file = 'conf/projects/' . $new_name . '.conf';

        $this->proceedToFileMove($old_file, $new_file, $git_modifications);

        if (! $this->gitoliterc_reader->getHostname()) {
            return;
        }

        $mirrors = $this->mirror_data_mapper->fetchAllForProject($project);

        foreach ($mirrors as $mirror) {
            $this->moveProjectFileForMirror($old_name, $new_name, $git_modifications, $mirror);
        }
    }

    private function proceedToFileMove($old_file, $new_file, Git_Gitolite_GitModifications $git_modifications)
    {
        if (is_file($old_file)) {
            $git_modifications->move($old_file, $new_file);
        }
    }

    private function moveProjectFileForMirror($old_name, $new_name, Git_Gitolite_GitModifications $git_modifications, Git_Mirror_Mirror $mirror)
    {
        if (empty($mirror->hostname)) {
            return;
        }

        $old_file = $this->getConfFolderForMirror($mirror) . '/' . $old_name . '.conf';
        $new_file = $this->getConfFolderForMirror($mirror) . '/' . $new_name . '.conf';

        $this->proceedToFileMove($old_file, $new_file, $git_modifications);
    }

    private function writeGitoliteIncludesInHostnameFile($hostname, Git_Gitolite_GitModifications $git_modifications, array $project_list)
    {
        $hostname_config_file = $this->getFullConfigFilePathFromHostname($hostname);

        file_put_contents($hostname_config_file, $this->permissions_serializer->getAllIncludes($project_list));
        $git_modifications->add($this->getRelativeConfigFilePathFromHostname($hostname));
    }

    private function writeGitoliteMirrorIncludes($hostname, Git_Gitolite_GitModifications $git_modifications, array $project_list)
    {
        $hostname_config_file = $this->getFullConfigFilePathFromHostname($hostname);

        file_put_contents($hostname_config_file, $this->permissions_serializer->getAllIncludesForHostname($hostname, $project_list));
        $git_modifications->add($this->getRelativeConfigFilePathFromHostname($hostname));
    }

    private function getFullConfigFilePathFromHostname($hostname)
    {
        if ($hostname) {
            return dirname($this->getGitoliteConfFilePath()) . '/' . $hostname . '.conf';
        }

        return $this->getGitoliteConfFilePath();
    }

    private function getRelativeConfigFilePathFromHostname($hostname)
    {
        return 'conf/' . $hostname . '.conf';
    }

    private function getProjectList()
    {
        $dir_path = dirname($this->getGitoliteConfFilePath()) . '/projects';
        return $this->readProjectListFromPath($dir_path);
    }

    private function getProjectsListForMirror(Git_Mirror_Mirror $mirror)
    {
        $dir_path = dirname($this->getGitoliteConfFilePath()) . '/' . $mirror->hostname;
        return $this->readProjectListFromPath($dir_path);
    }

    private function readProjectListFromPath($dir_path)
    {
        $project_names = array();

        if (! is_dir($dir_path)) {
            return $project_names;
        }

        $dir = new DirectoryIterator($dir_path);
        foreach ($dir as $file) {
            if (! $file->isDot()) {
                $project_names[] = basename($file->getFilename(), '.conf');
            }
        }
        return $project_names;
    }
}
