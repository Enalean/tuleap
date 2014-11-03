<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

/**
 * I check that everything is alright on the system
 *
 * @see Event::PROCCESS_SYSTEM_CHECK
 */
class Git_SystemCheck {

    /**
     * @var Git_Mirror_ManifestManager
     */
    private $manifest_manager;

    /**
     * @var Git_GitoliteDriver
     */
    private $gitolite;

    /**
     * @var Git_GitoliteHousekeeping_GitoliteHousekeepingGitGc
     */
    private $gitgc;

    public function __construct(
        Git_GitoliteHousekeeping_GitoliteHousekeepingGitGc $gitgc,
        Git_GitoliteDriver $gitolite,
        Git_Mirror_ManifestManager $manifest_manager
    ) {
        $this->gitgc    = $gitgc;
        $this->gitolite = $gitolite;
        $this->manifest_manager = $manifest_manager;
    }

    public function process() {
        $this->gitolite->checkAuthorizedKeys();
        $this->gitgc->cleanUpGitoliteAdminWorkingCopy();
        $this->manifest_manager->checkManifestFiles();
    }

}
