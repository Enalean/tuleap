<?php
/**
 * Copyright (c) Enalean, 2014 - 2015. All rights reserved
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

class Git_Mirror_ManifestFileGenerator
{

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var string */
    private $manifest_directory;

    /** @var string */
    private $gladm_path = '/gitolite-admin.git';

    public const FILE_PREFIX = 'manifest_mirror_';

    public function __construct(\Psr\Log\LoggerInterface $logger, $manifest_directory)
    {
        $this->manifest_directory = $manifest_directory;
        $this->logger             = $logger;
    }

    public function getManifestDirectory()
    {
        return $this->manifest_directory;
    }

    public function updateCurrentTimeOfRepository(Git_Mirror_Mirror $mirror, GitRepository $repository)
    {
        $filename = $this->getManifestFilenameForMirror($mirror);

        $list_of_repositories = $this->getListOfRepositoriesFromManifest($filename);
        $this->setCurrentTimeForRepository($mirror, $list_of_repositories, $repository);

        $this->writeManifest($filename, $list_of_repositories);
    }

    public function addRepositoryToManifestFile(Git_Mirror_Mirror $mirror, GitRepository $repository)
    {
        $filename = $this->getManifestFilenameForMirror($mirror);

        $list_of_repositories = $this->getListOfRepositoriesFromManifest($filename);
        $this->setCurrentTimeForRepository($mirror, $list_of_repositories, $repository);
        $this->setCurrentTimeForGitoliteAdminRepository($mirror, $list_of_repositories);

        $this->writeManifest($filename, $list_of_repositories);
    }

    public function removeRepositoryFromManifestFile(Git_Mirror_Mirror $mirror, $repository_path)
    {
        $filename = $this->getManifestFilenameForMirror($mirror);

        $list_of_repositories = $this->getListOfRepositoriesFromManifest($filename);
        $key = $this->getRepositoryKeyFromPathName($repository_path);
        if (isset($list_of_repositories[$key])) {
            $this->removeRepository($mirror, $list_of_repositories, $key);
            $this->setCurrentTimeForGitoliteAdminRepository($mirror, $list_of_repositories);

            $this->writeManifest($filename, $list_of_repositories);
        }
    }

    public function ensureManifestContainsLatestInfoOfRepositories(
        Git_Mirror_Mirror $mirror,
        array $expected_repositories
    ) {
        $filename = $this->getManifestFilenameForMirror($mirror);

        $list_of_repositories = $this->getListOfRepositoriesFromManifest($filename);
        foreach ($expected_repositories as $repository) {
            $key = $this->getRepositoryKey($repository);
            if (! isset($list_of_repositories[$key])) {
                $this->addRepository($mirror, $list_of_repositories, $repository);
            }
        }

        $expected_keys = array_flip(array_map(array($this, 'getRepositoryKey'), $expected_repositories));
        foreach ($list_of_repositories as $key => $nop) {
            if (! isset($expected_keys[$key])) {
                $this->removeRepository($mirror, $list_of_repositories, $key);
            }
        }

        $this->writeManifest($filename, $list_of_repositories);
    }

    private function getManifestFilenameForMirror(Git_Mirror_Mirror $mirror)
    {
        return $this->manifest_directory
            . DIRECTORY_SEPARATOR
            . self::FILE_PREFIX . $mirror->id . '.js.gz';
    }

    private function setCurrentTimeForGitoliteAdminRepository(
        Git_Mirror_Mirror $mirror,
        array &$list_of_repositories
    ) {
        $repository = new GitRepositoryGitoliteAdmin();

        $this->setCurrentTimeForRepository($mirror, $list_of_repositories, $repository);
    }

    private function setCurrentTimeForRepository(
        Git_Mirror_Mirror $mirror,
        array &$list_of_repositories,
        GitRepository $repository
    ) {
        $key = $this->getRepositoryKey($repository);
        if (isset($list_of_repositories[$key])) {
            $this->logger->debug("updating {$key} in manifest of mirror {$mirror->url} (id: {$mirror->id})");
            $list_of_repositories[$key]['modified'] = $_SERVER['REQUEST_TIME'];
        } else {
            $this->addRepository($mirror, $list_of_repositories, $repository);
        }
    }

    private function addRepository(
        Git_Mirror_Mirror $mirror,
        array &$list_of_repositories,
        GitRepository $repository
    ) {
        $key = $this->getRepositoryKey($repository);
        $this->logger->debug("adding {$key} to manifest of mirror {$mirror->url} (id: {$mirror->id})");
        $this->makeSureThatGitoliteAdminRepositoryIsInTheManifest($list_of_repositories);
        $list_of_repositories[$key] = $this->getRepositoryInformation($repository);
    }

    private function removeRepository(
        Git_Mirror_Mirror $mirror,
        array &$list_of_repositories,
        $repository_key
    ) {
        $this->logger->debug("removing {$repository_key} from manifest of mirror {$mirror->url} (id: {$mirror->id})");
        unset($list_of_repositories[$repository_key]);
    }

    private function makeSureThatGitoliteAdminRepositoryIsInTheManifest(array &$list_of_repositories)
    {
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

    private function getRepositoryInformation(GitRepository $repository)
    {
        return array(
            "owner"       => null,
            "description" => $repository->getDescription(),
            "reference"   => null,
            'modified'    => $_SERVER['REQUEST_TIME']
        );
    }

    private function getRepositoryKey(GitRepository $repository)
    {
        return '/' . $repository->getPath();
    }

    private function getRepositoryKeyFromPathName($path_name)
    {
        return '/' . $path_name;
    }

    private function getListOfRepositoriesFromManifest($filename)
    {
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

    private function writeManifest($filename, $list_of_repositories)
    {
        file_put_contents(
            "compress.zlib://$filename",
            json_encode($list_of_repositories)
        );
    }
}
