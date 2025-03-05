<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Plugin;

use gitlabPlugin;
use GitPlugin;
use PluginManager;
use Project;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitlabIntegrationAvailabilityCheckerTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PluginManager
     */
    private $plugin_manager;
    /**
     * @var gitlabPlugin&\PHPUnit\Framework\MockObject\MockObject
     */
    private $gitlab_plugin;

    private GitlabIntegrationAvailabilityChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plugin_manager = $this->createMock(PluginManager::class);
        $this->gitlab_plugin  = $this->createMock(gitlabPlugin::class);

        $this->checker = new GitlabIntegrationAvailabilityChecker(
            $this->plugin_manager,
            $this->gitlab_plugin
        );
    }

    public function testItReturnsTrueIfProjectCanUseGitlabIntegration(): void
    {
        $project = $this->createMock(Project::class);
        $project
            ->expects(self::once())
            ->method('getID')
            ->willReturn('101');

        $project
            ->expects(self::once())
            ->method('usesService')
            ->with(GitPlugin::SERVICE_SHORTNAME)
            ->willReturn(true);

        $this->plugin_manager
            ->expects(self::once())
            ->method('isPluginAllowedForProject')
            ->with($this->gitlab_plugin, 101)
            ->willReturn(true);

        self::assertTrue(
            $this->checker->isGitlabIntegrationAvailableForProject($project)
        );
    }

    public function testItReturnsFalseIfProjectDoesNotUseGitService(): void
    {
        $project = $this->createMock(Project::class);

        $project
            ->expects(self::once())
            ->method('usesService')
            ->with(GitPlugin::SERVICE_SHORTNAME)
            ->willReturn(false);

        $this->plugin_manager
            ->expects(self::never())
            ->method('isPluginAllowedForProject');

        self::assertFalse(
            $this->checker->isGitlabIntegrationAvailableForProject($project)
        );
    }

    public function testItReturnsFalseIfProjectIsRestrictedToUseGitlabIntegration(): void
    {
        $project = $this->createMock(Project::class);
        $project
            ->expects(self::once())
            ->method('getID')
            ->willReturn('101');

        $project
            ->expects(self::once())
            ->method('usesService')
            ->with(GitPlugin::SERVICE_SHORTNAME)
            ->willReturn(true);

        $this->plugin_manager
            ->expects(self::once())
            ->method('isPluginAllowedForProject')
            ->with($this->gitlab_plugin, 101)
            ->willReturn(false);

        self::assertFalse(
            $this->checker->isGitlabIntegrationAvailableForProject($project)
        );
    }
}
