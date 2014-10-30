<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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

class Git_Mirror_ManifestFileGenerator {

    const FILE_PREFIX = 'manifest_mirror_';

    /** @var string */
    private $manifest_directory;
    private $gladm_path = '/gitolite-admin.git';

    public function __construct($manifest_directory) {
        $this->manifest_directory = $manifest_directory;
    }

    public function addRepositoryToManifestFile(Git_Mirror_Mirror $mirror, GitRepository $repository) {
        $filename = $this->manifest_directory
            . DIRECTORY_SEPARATOR
            . self::FILE_PREFIX . $mirror->id . '.js.gz';

        $list_of_repositories = $this->getListOfRepositoriesFromManifest($filename);
        $this->setCurrentTimeForRepository($list_of_repositories, $repository);

        $this->writeManifest($filename, $list_of_repositories);
    }

    private function setCurrentTimeForRepository(array &$list_of_repositories, GitRepository $repository) {
        $key = $this->getRepositoryKey($repository);
        if (! isset($list_of_repositories[$key])) {
            $this->makeSureThatGitoliteAdminRepositoryIsInTheManifest($list_of_repositories);
            $list_of_repositories[$key] = $this->getRepositoryInformation($repository);
        }
        $list_of_repositories[$key]['modified'] = $_SERVER['REQUEST_TIME'];
    }

    private function makeSureThatGitoliteAdminRepositoryIsInTheManifest(array &$list_of_repositories) {
        if (isset($list_of_repositories[$this->gladm_path])) {
            return;
        }

        $list_of_repositories[$this->gladm_path] = array(
            "owner"       => null,
            "description" => '',
            "reference"   => null,
            'modified'    => $_SERVER['REQUEST_TIME']
        );
    }

    private function getRepositoryInformation(GitRepository $repository) {
        return array(
            "owner"       => null,
            "description" => $repository->getDescription(),
            "reference"   => null
        );
    }

    private function getRepositoryKey(GitRepository $repository) {
        return '/'. $repository->getPath();
    }

    private function getListOfRepositoriesFromManifest($filename) {
        if (! is_file($filename)) {
            return array();
        }

        $content = file_get_contents("compress.zlib://$filename");
        $list_of_repositories = json_decode($content, true);
        if (! $list_of_repositories) {
            return array();
        }

        return $list_of_repositories;
    }

    private function writeManifest($filename, $list_of_repositories) {
        file_put_contents(
            "compress.zlib://$filename",
            json_encode($list_of_repositories)
        );
    }
}
