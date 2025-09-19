<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All rights reserved
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
    public const string GITOLITE_CONF_FILE = 'conf/gitolite.conf';

    /** @var Git_Gitolite_GitoliteRCReader */
    private $gitoliterc_reader;

    /** @var Git_Gitolite_ConfigPermissionsSerializer */
    private $permissions_serializer;

    /** @var Git_Gitolite_ProjectSerializer */
    private $project_serializer;

    /** @var string */
    private $gitolite_administration_path;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var ProjectManager */
    private $project_manager;

    public function __construct(
        Git_Gitolite_ConfigPermissionsSerializer $permissions_serializer,
        Git_Gitolite_ProjectSerializer $project_serializer,
        Git_Gitolite_GitoliteRCReader $gitoliterc_reader,
        \Psr\Log\LoggerInterface $logger,
        ProjectManager $project_manager,
        $gitolite_administration_path,
    ) {
        $this->gitoliterc_reader            = $gitoliterc_reader;
        $this->permissions_serializer       = $permissions_serializer;
        $this->project_serializer           = $project_serializer;
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

        $config_file_content = $this->project_serializer->dumpSuspendedProjectRepositoriesConfiguration($project);
        $this->modifyGitConfigurationFileInGitolite($project, $git_modifications, $config_file_content);

        return $git_modifications;
    }

    private function modifyGitConfigurationFileInGitolite(
        Project $project,
        Git_Gitolite_GitModifications $git_modifications,
        $config_file_content,
    ) {
        $this->logger->debug('Get Project Permission Conf File: ' . $project->getUnixName() . '...');
        $config_file = $this->getProjectPermissionConfFile($project);
        $this->logger->debug('Get Project Permission Conf File: ' . $project->getUnixName() . ': done');

        $this->logger->debug('Write Git config: ' . $project->getUnixName() . '...');
        $this->writeGitConfig($config_file, $config_file_content, $git_modifications);
        $this->logger->debug('Write Git config: ' . $project->getUnixName() . ': done');
    }

    private function getProjectPermissionConfFile(Project $project)
    {
        $prjConfDir = 'conf/projects';
        if (! is_dir($prjConfDir)) {
            mkdir($prjConfDir);
        }
        return $prjConfDir . '/' . $project->getUnixName() . '.conf';
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
    }

    private function proceedToRenameInSpecifiedProjectFile($project_file_path, $old_name, $new_name)
    {
        $orig = file_get_contents($project_file_path);

        $dest = preg_replace('`(^|\n)repo ' . preg_quote($old_name, '`') . '/`', '$1repo ' . $new_name . '/', $orig);
        $dest = str_replace('@' . $old_name . '_project_', '@' . $new_name . '_project_', $dest);
        $dest = preg_replace('%' . preg_quote($old_name, '%') . '/(.*) = "%', "$new_name/$1 = \"", $dest);
        file_put_contents($project_file_path, $dest);
    }

    private function moveProjectFiles($old_name, $new_name, Git_Gitolite_GitModifications $git_modifications, Project $project)
    {
        $old_file = 'conf/projects/' . $old_name . '.conf';
        $new_file = 'conf/projects/' . $new_name . '.conf';

        $this->proceedToFileMove($old_file, $new_file, $git_modifications);
    }

    private function proceedToFileMove($old_file, $new_file, Git_Gitolite_GitModifications $git_modifications)
    {
        if (is_file($old_file)) {
            $git_modifications->move($old_file, $new_file);
        }
    }

    private function writeGitoliteIncludesInHostnameFile($hostname, Git_Gitolite_GitModifications $git_modifications, array $project_list)
    {
        $hostname_config_file = $this->getFullConfigFilePathFromHostname($hostname);

        file_put_contents($hostname_config_file, $this->permissions_serializer->getAllIncludes($project_list));
        $git_modifications->add($this->getRelativeConfigFilePathFromHostname($hostname));
    }

    private function getFullConfigFilePathFromHostname($hostname)
    {
        if ($hostname) {
            return dirname($this->getGitoliteConfFilePath()) . '/' . self::getHostnameToUseAsPartOfAFileName($hostname) . '.conf';
        }

        return $this->getGitoliteConfFilePath();
    }

    private function getRelativeConfigFilePathFromHostname($hostname)
    {
        return 'conf/' . self::getHostnameToUseAsPartOfAFileName($hostname) . '.conf';
    }

    /**
     * @psalm-taint-escape file
     */
    private static function getHostnameToUseAsPartOfAFileName(string $hostname): string
    {
        return str_replace('/', '', $hostname);
    }

    private function getProjectList()
    {
        $dir_path = dirname($this->getGitoliteConfFilePath()) . '/projects';
        return $this->readProjectListFromPath($dir_path);
    }

    private function readProjectListFromPath($dir_path)
    {
        $project_names = [];

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
