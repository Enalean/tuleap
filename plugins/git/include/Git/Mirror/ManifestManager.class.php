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

class Git_Mirror_ManifestManager {

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

    public function triggerUpdateByRoot(GitRepository $repository) {
        $this->triggerUpdate($repository);

        $manifest_directory = $this->generator->getManifestDirectory();
        if (is_dir($manifest_directory)) {
            foreach (glob($manifest_directory . '/' . Git_Mirror_ManifestFileGenerator::FILE_PREFIX . '*') as $file) {
                chown($file, 'gitolite');
                chgrp($file, 'gitolite');
            }
        }
    }

    public function triggerUpdate(GitRepository $repository) {
        $all_mirrors        = $this->data_mapper->fetchAll();
        $repository_mirrors = $this->data_mapper->fetchAllRepositoryMirrors($repository);

        foreach ($repository_mirrors as $mirror) {
            $this->generator->addRepositoryToManifestFile($mirror, $repository);
        }

        $not_repository_mirrors = array_diff($all_mirrors, $repository_mirrors);
        foreach ($not_repository_mirrors as $mirror) {
            $this->generator->removeRepositoryFromManifestFile($mirror, $repository);
        }
    }
}
