<?php
/**
 * Copyright (c) Enalean, 2014 - 2016. All Rights Reserved.
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
class Git_SystemCheck
{

    /**
     * @var Plugin
     */
    private $git_plugin;

    /**
     * @var PluginConfigChecker
     */
    private $config_checker;

    /**
     *  @var Git_SystemEventManager
     */
    private $system_event_manager;

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
        Git_SystemEventManager $system_event_manager,
        PluginConfigChecker $config_checker,
        Plugin $git_plugin
    ) {
        $this->gitgc                = $gitgc;
        $this->gitolite             = $gitolite;
        $this->system_event_manager = $system_event_manager;
        $this->config_checker       = $config_checker;
        $this->git_plugin           = $git_plugin;
    }

    public function process()
    {
        $this->gitolite->checkAuthorizedKeys();
        $this->gitgc->cleanUpGitoliteAdminWorkingCopy();
        $this->system_event_manager->queueGrokMirrorManifestCheck();

        $this->checkIncFolderAndFileOwnership();
    }

    private function checkIncFolderAndFileOwnership()
    {
        $this->config_checker->checkFolder($this->git_plugin);
        $this->config_checker->checkIncFile($this->getIncFile());
    }

    private function getIncFile()
    {
        return $this->git_plugin->getPluginEtcRoot() . '/config.inc';
    }
}
