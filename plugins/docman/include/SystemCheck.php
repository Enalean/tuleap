<?php
/**
 * Copyright (c) Enalean, 2015 - 2016. All Rights Reserved.
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
class Docman_SystemCheck
{

    /** @var Plugin */
    private $docman_plugin;

    /** @var Docman_SystemCheckProjectRetriever */
    private $retriever;

    /** @var Backend */
    private $backend;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * @var PluginConfigChecker
     */
    private $config_checker;

    public function __construct(
        Plugin $docman_plugin,
        Docman_SystemCheckProjectRetriever $retriever,
        Backend $backend,
        PluginConfigChecker $config_checker,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->retriever      = $retriever;
        $this->docman_plugin  = $docman_plugin;
        $this->backend        = $backend;
        $this->config_checker = $config_checker;
        $this->logger         = $logger;
    }

    public function process()
    {
        $this->checkIncFolderAndFileOwnership();

        foreach ($this->retriever->getActiveProjectUnixNamesThatUseDocman() as $project_unix_name) {
            $folder_path = $this->getDocmanRootPath() . $project_unix_name;

            if (! is_dir($folder_path)) {
                $this->logger->info("Docman root folder for project $project_unix_name is missing");

                $this->createFolderWithRightAccessRights($folder_path, $project_unix_name);
            }
        }

        return true;
    }

    private function checkIncFolderAndFileOwnership()
    {
        $this->config_checker->checkFolder($this->docman_plugin);
        $this->config_checker->checkIncFile($this->getIncFile());
    }

    private function getIncFile()
    {
        return $this->docman_plugin->getPluginEtcRoot() . '/docman.inc';
    }

    /**
     * @throws Docman_FolderNotCreatedException
     */
    private function createFolderWithRightAccessRights($folder_path, $project_unix_name)
    {
        if (! mkdir($folder_path)) {
            throw new Docman_FolderNotCreatedException("Folder $folder_path not created");
        }

        $user = ForgeConfig::get('sys_http_user');

        $this->backend->changeOwnerGroupMode($folder_path, $user, $user, 0700);

        $this->logger->info("Docman root folder for project $project_unix_name created");
    }

    private function getDocmanRootPath()
    {
        return $this->docman_plugin->getPluginInfo()->getPropertyValueForName('docman_root') . '/';
    }
}
