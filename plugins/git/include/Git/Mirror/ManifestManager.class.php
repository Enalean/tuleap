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
     * @var BackendLogger
     */
    private $logger;

    /**
     * @var Git_Mirror_MirrorDataMapper
     */
    private $data_mapper;
    private $manifest_directory;

    public function __construct(Git_Mirror_MirrorDataMapper $data_mapper, Logger $logger) {
        $this->data_mapper        = $data_mapper;
        $this->logger             = $logger;
        $this->manifest_directory =  Config::get('sys_data_dir').'/gitolite/grokmirror';
    }

    public function triggerUpdateByRoot(GitRepository $repository) {
        $this->triggerUpdate($repository);
        if (is_dir($this->manifest_directory)) {
            foreach (glob($this->manifest_directory . '/' . Git_Mirror_ManifestFileGenerator::FILE_PREFIX . '*') as $file) {
                chown($file, 'gitolite');
                chgrp($file, 'gitolite');
            }
        }
    }

    public function triggerUpdate(GitRepository $repository) {
        $mirrors = $this->data_mapper->fetchAllRepositoryMirrors($repository);
        if (count($mirrors) == 0) {
            $this->logger->debug("[git post receive] no mirrors on which replicate");
            return false;
        }

        $current_directory  = realpath($repository->getFullPath());

        $generator = new Git_Mirror_ManifestFileGenerator($this->manifest_directory);
        foreach ($mirrors as $mirror) {
            $this->logger->debug("[git post receive] update mirror {$mirror->url} (id: {$mirror->id}) manifest for ".$current_directory);
            $generator->addRepositoryToManifestFile($mirror, $repository);
        }
    }
}
