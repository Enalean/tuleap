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
    private $grokmanifest_path;

    public function __construct(Git_Mirror_MirrorDataMapper $data_mapper, Logger $logger, $grokmanifest_path) {
        $this->data_mapper       = $data_mapper;
        $this->logger            = $logger;
        $this->grokmanifest_path = $grokmanifest_path;
    }

    public function triggerUpdate(GitRepository $repository) {
        if (! is_executable($this->grokmanifest_path)) {
            $this->logger->debug("[git post receive] no grokmirror-manifest executable found. Check git config");
            return false;
        }
        $mirrors = $this->data_mapper->fetchAllRepositoryMirrors($repository->getId());
        if (count($mirrors) == 0) {
            $this->logger->debug("[git post receive] no mirrors on which replicate");
            return false;
        }

        $repositories_base_path = realpath(Config::get('sys_data_dir').'/gitolite/repositories');
        $current_directory      = realpath($repository->getFullPath());

        foreach ($mirrors as $mirror) {
            $this->logger->debug("[git post receive] update manifest for ".$repositories_base_path);
            $manifest_path = escapeshellarg($this->getManifestPathForMirror($mirror));
            $this->exec("{$this->grokmanifest_path} -m $manifest_path -t $repositories_base_path -n $current_directory");
        }
    }

    private function getManifestPathForMirror(Git_Mirror_Mirror $mirror) {
        return Config::get('sys_data_dir')."/gitolite/grokmirror/manifest_mirror_{$mirror->id}.js.gz";
    }

    private function exec($cmd) {
        $output       = array();
        $return_value = 1;
        exec("$cmd 2>&1", $output, $return_value);
        if ($return_value == 0) {
            return true;
        } else {
            $this->logger->error("[git post receive] an error was raised by grokmirror command: $cmd\n".implode("\n", $output));
        }

    }
}
