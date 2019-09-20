<?php
/**
 * Copyright (c) Enalean, 2014-2015. All rights reserved
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

class Git_Mirror_ManifestManager
{

    /**
     * @var Git_Mirror_ManifestFileGenerator
     */
    private $generator;

    /**
     * @var Git_Mirror_MirrorDataMapper
     */
    private $data_mapper;

    public function __construct(
        Git_Mirror_MirrorDataMapper $data_mapper,
        Git_Mirror_ManifestFileGenerator $generator
    ) {
        $this->data_mapper = $data_mapper;
        $this->generator   = $generator;
    }

    public function triggerUpdate(GitRepository $repository)
    {
        $repository_mirrors = $this->data_mapper->fetchAllRepositoryMirrors($repository);
        $all_mirrors        = $this->data_mapper->fetchAll();

        foreach ($repository_mirrors as $mirror) {
            $this->generator->addRepositoryToManifestFile($mirror, $repository);
        }

        $not_repository_mirrors = array_diff($all_mirrors, $repository_mirrors);
        foreach ($not_repository_mirrors as $mirror) {
            $this->generator->removeRepositoryFromManifestFile($mirror, $repository->getPath());
        }
    }

    public function triggerUpdateFollowingAGitPush(GitRepository $repository)
    {
        $repository_mirrors = $this->data_mapper->fetchAllRepositoryMirrors($repository);

        foreach ($repository_mirrors as $mirror) {
            $this->generator->updateCurrentTimeOfRepository($mirror, $repository);
        }
    }

    public function checkManifestFiles()
    {
        $gitolite_admin_repository = new GitRepositoryGitoliteAdmin();
        $all_mirrors = $this->data_mapper->fetchAll();
        foreach ($all_mirrors as $mirror) {
            $repositories = $this->data_mapper->fetchRepositoriesForMirror($mirror);
            array_splice($repositories, 0, 0, array($gitolite_admin_repository));
            $this->generator->ensureManifestContainsLatestInfoOfRepositories(
                $mirror,
                $repositories
            );
        }
        $this->forceFileOwnershipToAppUser();
    }

    public function triggerDelete($repository_path)
    {
        $all_mirrors = $this->data_mapper->fetchAll();
        foreach ($all_mirrors as $mirror) {
            $this->generator->removeRepositoryFromManifestFile($mirror, $repository_path);
        }
    }

    private function forceFileOwnershipToAppUser()
    {
        $manifest_directory = $this->generator->getManifestDirectory();
        if (is_dir($manifest_directory)) {
            foreach (glob($manifest_directory . '/' . Git_Mirror_ManifestFileGenerator::FILE_PREFIX . '*') as $file) {
                chown($file, ForgeConfig::get('sys_http_user'));
                chgrp($file, ForgeConfig::get('sys_http_user'));
            }
        }
    }
}
