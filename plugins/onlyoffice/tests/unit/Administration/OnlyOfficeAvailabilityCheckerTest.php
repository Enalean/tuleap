<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Administration;

use Psr\Log\LoggerInterface;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\ForgeConfigSandbox;
use Tuleap\OnlyOffice\DocumentServer\DocumentServer;
use Tuleap\OnlyOffice\Stubs\IRetrieveDocumentServersStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

class OnlyOfficeAvailabilityCheckerTest extends TestCase
{
    use ForgeConfigSandbox;

    private const PROJECT_ID = 101;

    public function testItLogsThatServerUrlIsNotConfigured(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $checker = new OnlyOfficeAvailabilityChecker(
            $this->createMock(\PluginManager::class),
            new \onlyofficePlugin(null),
            $logger,
            IRetrieveDocumentServersStub::buildWithoutServer(),
        );

        $project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();

        $logger->expects(self::once())
            ->method('debug')
            ->with('No document server with existing secret key has been defined');

        self::assertFalse($checker->isOnlyOfficeIntegrationAvailableForProject($project));
    }

    public function testItLogsThatServerSecretIsNotConfigured(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $checker = new OnlyOfficeAvailabilityChecker(
            $this->createMock(\PluginManager::class),
            new \onlyofficePlugin(null),
            $logger,
            IRetrieveDocumentServersStub::buildWith(new DocumentServer(1, 'https://example.com', new ConcealedString(''))),
        );

        $project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();

        $logger->expects(self::once())
            ->method('debug')
            ->with('No document server with existing secret key has been defined');

        self::assertFalse($checker->isOnlyOfficeIntegrationAvailableForProject($project));
    }

    public function testItReturnFalseIfProjectDoesNotUseDocman(): void
    {
        $checker = new OnlyOfficeAvailabilityChecker(
            $this->createMock(\PluginManager::class),
            new \onlyofficePlugin(null),
            $this->createMock(LoggerInterface::class),
            IRetrieveDocumentServersStub::buildWith(new DocumentServer(1, 'https://example.com', new ConcealedString('very_secret'))),
        );

        $project = ProjectTestBuilder::aProject()
            ->withId(self::PROJECT_ID)
            ->withUsedService('cvs')
            ->build();

        self::assertFalse($checker->isOnlyOfficeIntegrationAvailableForProject($project));
    }

    public function testItReturnFalseIfProjectIsNotAllowedToUseOnlyOffice(): void
    {
        $plugin_manager    = $this->createMock(\PluginManager::class);
        $onlyoffice_plugin = new \onlyofficePlugin(null);
        $checker           = new OnlyOfficeAvailabilityChecker(
            $plugin_manager,
            $onlyoffice_plugin,
            $this->createMock(LoggerInterface::class),
            IRetrieveDocumentServersStub::buildWith(new DocumentServer(1, 'https://example.com', new ConcealedString('very_secret'))),
        );

        $project = ProjectTestBuilder::aProject()
            ->withId(self::PROJECT_ID)
            ->withUsedService('cvs')
            ->withUsedService(\DocmanPlugin::SERVICE_SHORTNAME)
            ->build();

        $plugin_manager
            ->method('isPluginAllowedForProject')
            ->with($onlyoffice_plugin, self::PROJECT_ID)
            ->willReturn(false);

        self::assertFalse($checker->isOnlyOfficeIntegrationAvailableForProject($project));
    }

    public function testHappyPath(): void
    {
        $plugin_manager    = $this->createMock(\PluginManager::class);
        $onlyoffice_plugin = new \onlyofficePlugin(null);
        $checker           = new OnlyOfficeAvailabilityChecker(
            $plugin_manager,
            $onlyoffice_plugin,
            $this->createMock(LoggerInterface::class),
            IRetrieveDocumentServersStub::buildWith(new DocumentServer(1, 'https://example.com', new ConcealedString('very_secret'))),
        );

        $project = ProjectTestBuilder::aProject()
            ->withId(self::PROJECT_ID)
            ->withUsedService('cvs')
            ->withUsedService(\DocmanPlugin::SERVICE_SHORTNAME)
            ->build();

        $plugin_manager
            ->method('isPluginAllowedForProject')
            ->with($onlyoffice_plugin, self::PROJECT_ID)
            ->willReturn(true);

        self::assertTrue($checker->isOnlyOfficeIntegrationAvailableForProject($project));
    }
}
