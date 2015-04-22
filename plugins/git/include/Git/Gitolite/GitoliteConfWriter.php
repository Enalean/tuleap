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

class Git_Gitolite_GitoliteConfWriter {

    const GITOLITE_CONF_FILE = "conf/gitolite.conf";

    /** @var Git_Gitolite_GitoliteRCReader */
    private $gitoliterc_reader;

    /** @var Git_Gitolite_ConfigPermissionsSerializer */
    private $permissions_serializer;

    /** @var string */
    private $gitolite_administration_path;

    public function __construct(
        Git_Gitolite_ConfigPermissionsSerializer $permissions_serializer,
        Git_Gitolite_GitoliteRCReader $gitoliterc_reader,
        $gitolite_administration_path
    ) {
        $this->gitoliterc_reader            = $gitoliterc_reader;
        $this->permissions_serializer       = $permissions_serializer;
        $this->gitolite_administration_path = $gitolite_administration_path;
    }

    public function writeGitoliteConfiguration() {
        $git_modifications = new Git_Gitolite_GitModifications();
        $hostname          = $this->gitoliterc_reader->getHostname();


        if ($hostname) {
            $this->writeGitoliteConfigurationOnDisk($this->permissions_serializer->getGitoliteDotConfForHostname($this->getProjectList()), $git_modifications);
            $this->writeGitoliteIncludesInHostnameFile($hostname, $git_modifications);

            return $git_modifications;
        }

        $this->writeGitoliteConfigurationOnDisk($this->permissions_serializer->getGitoliteDotConf($this->getProjectList()), $git_modifications);

        return $git_modifications;
    }

    public function renameProject($old_name, $new_name) {
        $git_modifications = new Git_Gitolite_GitModifications();

        $this->moveProjectFiles($old_name, $new_name, $git_modifications);
        $this->modifyProjectConf($old_name, $new_name, $git_modifications);
        $this->modifyIncludersConf($old_name, $new_name, $git_modifications);

        return $git_modifications;
    }

    private function writeGitoliteConfigurationOnDisk($content, Git_Gitolite_GitModifications $git_modifications) {
        file_put_contents($this->getGitoliteConfFilePath(), $content);
        $git_modifications->add(self::GITOLITE_CONF_FILE);
    }

    private function getGitoliteConfFilePath() {
        return $this->gitolite_administration_path . '/' . self::GITOLITE_CONF_FILE;
    }

    private function modifyIncludersConf($old_name, $new_name, Git_Gitolite_GitModifications $git_modifications) {
        $file_path = $this->getFullConfigFilePathFromHostname($this->gitoliterc_reader->getHostname());

        $orig = file_get_contents($file_path);
        $dest = str_replace('include "projects/'. $old_name .'.conf"', 'include "projects/'. $new_name .'.conf"', $orig);
        file_put_contents($file_path, $dest);

        $git_modifications->add($file_path);
    }

    private function modifyProjectConf($old_name, $new_name, Git_Gitolite_GitModifications $git_modifications) {
        $original_file = 'conf/projects/'. $old_name .'.conf';

        //conf/projects/newone.conf
        $orig = file_get_contents($original_file);
        $dest = preg_replace('`(^|\n)repo '. preg_quote($old_name) .'/`', '$1repo '. $new_name .'/', $orig);
        $dest = str_replace('@'. $old_name .'_project_', '@'. $new_name .'_project_', $dest);
        $dest = preg_replace("%$old_name/(.*) = \"%", "$new_name/$1 = \"", $dest);
        file_put_contents($original_file, $dest);

        $git_modifications->add($original_file);
    }

    private function moveProjectFiles($old_name, $new_name, Git_Gitolite_GitModifications $git_modifications) {
        $old_file = 'conf/projects/'.$old_name.'.conf';
        $new_file = 'conf/projects/'.$new_name.'.conf';

        if (is_file($old_file))
        {
            $git_modifications->move($old_file, $new_file);
        }

    }

    private function writeGitoliteIncludesInHostnameFile($hostname, Git_Gitolite_GitModifications $git_modifications) {
        $hostname_config_file = $this->getFullConfigFilePathFromHostname($hostname);

        file_put_contents($hostname_config_file ,$this->permissions_serializer->getAllIncludes($this->getProjectList()));
        $git_modifications->add($this->getRelativeConfigFilePathFromHostname($hostname));
    }

    private function getFullConfigFilePathFromHostname($hostname) {
        if ($hostname) {
            return dirname($this->getGitoliteConfFilePath()).'/'.$hostname.'.conf';
        }

        return $this->getGitoliteConfFilePath();
    }

    private function getRelativeConfigFilePathFromHostname($hostname) {
        return 'conf/'.$hostname.'.conf';
    }

    private function getProjectList() {
        $project_names = array();
        $dir = new DirectoryIterator(dirname($this->getGitoliteConfFilePath()).'/projects');
        foreach ($dir as $file) {
            if (! $file->isDot()) {
                $project_names[] = basename($file->getFilename(), '.conf');
            }
        }
        return $project_names;
    }
}