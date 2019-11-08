<?php
/**
 * Copyright (c) Enalean, 2014 - 2017. All rights reserved
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

use Tuleap\Git\GerritServerResourceRestrictor;
use Tuleap\Git\RestrictedGerritServerDao;

class GitDataBuilder extends REST_TestDataBuilder
{

    public const PROJECT_TEST_GIT_SHORTNAME = 'test-git';
    public const REPOSITORY_GIT_ID          = 1;

    public function setUp()
    {
        $project = $this->project_manager->getProjectByUnixName(self::PROJECT_TEST_GIT_SHORTNAME);

        $this->addGerritServers($project);
    }

    private function addGerritServers(Project $project)
    {
        echo "Creating Gerrit servers\n";

        $server_01 = new Git_RemoteServer_GerritServer(
            0,
            'localhost',
            29418,
            8080,
            'gerrit-adm',
            '',
            '',
            true,
            Git_RemoteServer_GerritServer::GERRIT_VERSION_2_8_PLUS,
            '',
            '',
            'Digest'
        );

        $server_02 = new Git_RemoteServer_GerritServer(
            0,
            'otherhost',
            29418,
            8080,
            'gerrit-adm',
            '',
            '',
            false,
            Git_RemoteServer_GerritServer::GERRIT_VERSION_2_5,
            '',
            '',
            'Digest'
        );

        $server_03 = new Git_RemoteServer_GerritServer(
            0,
            'restricted',
            29418,
            8080,
            'gerrit-adm',
            '',
            '',
            false,
            Git_RemoteServer_GerritServer::GERRIT_VERSION_2_5,
            '',
            '',
            'Digest'
        );

        $server_factory = new Git_RemoteServer_GerritServerFactory(
            new Git_RemoteServer_Dao(),
            new GitDao(),
            new Git_SystemEventManager(
                SystemEventManager::instance(),
                new GitRepositoryFactory(
                    new GitDao(),
                    $this->project_manager
                )
            ),
            $this->project_manager
        );
        $server_factory->save($server_01);
        $server_factory->save($server_02);
        $server_factory->save($server_03);

        $gerrit_server_restrictor = new GerritServerResourceRestrictor(
            new RestrictedGerritServerDao()
        );
        $gerrit_server_restrictor->setRestricted($server_03);
        $gerrit_server_restrictor->allowProject($server_03, $project);
    }
}
