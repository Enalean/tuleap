<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\HudsonGit;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;

class GitJenkinsAdministrationServerDeleterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var GitJenkinsAdministrationServerDeleter
     */
    private $deleter;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitJenkinsAdministrationServerDao
     */
    private $git_jenkins_administration_server_dao;

    /**
     * @var GitJenkinsAdministrationServer
     */
    private $jenkins_server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->git_jenkins_administration_server_dao = Mockery::mock(GitJenkinsAdministrationServerDao::class);

        $this->deleter = new GitJenkinsAdministrationServerDeleter(
            $this->git_jenkins_administration_server_dao
        );

        $this->jenkins_server = new GitJenkinsAdministrationServer(
            1,
            'url',
            Mockery::mock(Project::class)
        );
    }

    public function testItDeletesAJenkinsServer(): void
    {
        $this->git_jenkins_administration_server_dao->shouldReceive('deleteJenkinsServer')->once();

        $this->deleter->deleteServer($this->jenkins_server);
    }
}
