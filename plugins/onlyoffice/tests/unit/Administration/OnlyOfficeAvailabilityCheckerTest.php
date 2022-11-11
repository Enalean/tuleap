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
use Tuleap\ForgeConfigSandbox;
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
        );

        $project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();

        $logger->expects(self::once())
            ->method('debug')
            ->with("Settings onlyoffice_document_server_url does not seem to be defined");

        self::assertFalse($checker->isOnlyOfficeIntegrationAvailableForProject($project));
    }

    public function testItLogsThatServerSecretIsNotConfigured(): void
    {
        \ForgeConfig::set(OnlyOfficeDocumentServerSettings::URL, 'https://example.com');

        $logger = $this->createMock(LoggerInterface::class);

        $checker = new OnlyOfficeAvailabilityChecker(
            $this->createMock(\PluginManager::class),
            new \onlyofficePlugin(null),
            $logger,
        );

        $project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();

        $logger->expects(self::once())
            ->method('debug')
            ->with("Settings onlyoffice_document_server_secret does not seem to be defined");

        self::assertFalse($checker->isOnlyOfficeIntegrationAvailableForProject($project));
    }

    public function testItReturnFalseIfProjectDoesNotUseDocman(): void
    {
        \ForgeConfig::set(OnlyOfficeDocumentServerSettings::URL, 'https://example.com');
        \ForgeConfig::set(OnlyOfficeDocumentServerSettings::SECRET, 'very_secret');

        $checker = new OnlyOfficeAvailabilityChecker(
            $this->createMock(\PluginManager::class),
            new \onlyofficePlugin(null),
            $this->createMock(LoggerInterface::class),
        );

        $project = ProjectTestBuilder::aProject()
            ->withId(self::PROJECT_ID)
            ->withUsedService('cvs')
            ->build();

        self::assertFalse($checker->isOnlyOfficeIntegrationAvailableForProject($project));
    }

    public function testItReturnFalseIfProjectIsNotAllowedToUseOnlyOffice(): void
    {
        \ForgeConfig::set(OnlyOfficeDocumentServerSettings::URL, 'https://example.com');
        \ForgeConfig::set(OnlyOfficeDocumentServerSettings::SECRET, 'very_secret');

        $plugin_manager    = $this->createMock(\PluginManager::class);
        $onlyoffice_plugin = new \onlyofficePlugin(null);
        $checker           = new OnlyOfficeAvailabilityChecker(
            $plugin_manager,
            $onlyoffice_plugin,
            $this->createMock(LoggerInterface::class),
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
        \ForgeConfig::set(OnlyOfficeDocumentServerSettings::URL, 'https://example.com');
        \ForgeConfig::set(OnlyOfficeDocumentServerSettings::SECRET, 'very_secret');

        $plugin_manager    = $this->createMock(\PluginManager::class);
        $onlyoffice_plugin = new \onlyofficePlugin(null);
        $checker           = new OnlyOfficeAvailabilityChecker(
            $plugin_manager,
            $onlyoffice_plugin,
            $this->createMock(LoggerInterface::class),
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
