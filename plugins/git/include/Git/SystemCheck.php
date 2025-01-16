<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\Git;

use Git_GitoliteDriver;
use Git_GitoliteHousekeeping_GitoliteHousekeepingGitGc;
use Plugin;
use PluginConfigChecker;

/**
 * I check that everything is alright on the system
 *
 * @see Event::PROCCESS_SYSTEM_CHECK
 */
final readonly class SystemCheck
{
    public function __construct(
        private Git_GitoliteHousekeeping_GitoliteHousekeepingGitGc $gitgc,
        private Git_GitoliteDriver $gitolite,
        private PluginConfigChecker $config_checker,
        private Plugin $git_plugin,
    ) {
    }

    public function process(): void
    {
        $this->gitolite->checkAuthorizedKeys();
        $this->gitgc->cleanUpGitoliteAdminWorkingCopy();

        $this->checkIncFolderAndFileOwnership();
    }

    private function checkIncFolderAndFileOwnership(): void
    {
        $this->config_checker->checkFolder($this->git_plugin);
    }
}
