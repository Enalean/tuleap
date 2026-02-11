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

namespace Tuleap\OAuth2ServerCore\App;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AppFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private AppFactory $app_factory;
    private RetrieveAppMatchingClientID&MockObject $app_retriever;
    private \ProjectManager&Stub $project_manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->app_retriever   = $this->createMock(RetrieveAppMatchingClientID::class);
        $this->project_manager = $this->createStub(\ProjectManager::class);
        $this->app_factory     = new AppFactory($this->app_retriever, $this->project_manager);
    }

    public function testGetAppMatchingClientIdThrowsWhenIDNotFoundInDatabase(): void
    {
        $this->app_retriever->expects($this->once())->method('searchByClientId')->willReturn(null);
        $client_id = ClientIdentifier::fromClientId('tlp-client-id-1');

        $this->expectException(OAuth2AppNotFoundException::class);
        $this->app_factory->getAppMatchingClientId($client_id);
    }

    public function testGetAppMatchingClientIdThrowsWhenProjectNotFound(): void
    {
        $this->app_retriever->expects($this->once())->method('searchByClientId')
            ->willReturn(
                ['id' => 1, 'name' => 'Jenkins', 'project_id' => 404, 'redirect_endpoint' => 'https://jenkins.example.com']
            );
        $client_id = ClientIdentifier::fromClientId('tlp-client-id-1');
        $this->project_manager->method('getValidProject')
            ->willThrowException(new \Project_NotFoundException());

        $this->expectException(OAuth2AppNotFoundException::class);
        $this->app_factory->getAppMatchingClientId($client_id);
    }

    public function testGetAppMatchingClientIdReturnsAnApp(): void
    {
        $this->app_retriever->expects($this->once())->method('searchByClientId')
            ->willReturn(
                ['id' => 1, 'name' => 'Jenkins', 'project_id' => 102, 'redirect_endpoint' => 'https://jenkins.example.com', 'use_pkce' => 1]
            );
        $client_id = ClientIdentifier::fromClientId('tlp-client-id-1');
        $project   = ProjectTestBuilder::aProject()->build();
        $this->project_manager->method('getValidProject')
            ->willReturn($project);

        $result = $this->app_factory->getAppMatchingClientId($client_id);
        $this->assertEquals(new OAuth2App(1, 'Jenkins', 'https://jenkins.example.com', true, $project), $result);
    }

    public function testGetSiteLevelAppMatchingClientIdReturnsAnApp(): void
    {
        $this->app_retriever->expects($this->once())->method('searchByClientId')
            ->willReturn(
                ['id' => 1, 'name' => 'Jenkins', 'project_id' => null, 'redirect_endpoint' => 'https://jenkins.example.com', 'use_pkce' => 1]
            );
        $client_id = ClientIdentifier::fromClientId('tlp-client-id-1');

        $result = $this->app_factory->getAppMatchingClientId($client_id);
        $this->assertEquals(new OAuth2App(1, 'Jenkins', 'https://jenkins.example.com', true, null), $result);
    }
}
