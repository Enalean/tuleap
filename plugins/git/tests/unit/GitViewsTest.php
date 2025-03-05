<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

namespace Tuleap\Git;

use GitPlugin;
use GitViews;
use PFUser;
use Project;
use ProjectManager;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitViewsTest extends TestCase
{
    public function testCanReturnOptionsListOfProjectsTheUserIsAdminOf(): void
    {
        $project = $this->givenAProject('Guinea Pig');
        $user    = $this->givenAUserWithProjects($project);
        $manager = $this->givenAProjectManager($project);

        $view   = $this->createPartialMock(GitViews::class, []);
        $output = $view->getUserProjectsAsOptions($user, $manager, '50');
        self::assertMatchesRegularExpression('/<option value="123"/', $output);
        self::assertDoesNotMatchRegularExpression('/<option value="456"/', $output);
    }

    public function testOptionsShouldContainThePublicNameOfTheProject(): void
    {
        $project = $this->givenAProject('Guinea Pig');
        $user    = $this->givenAUserWithProjects($project);
        $manager = $this->givenAProjectManager($project);

        $view = $this->createPartialMock(GitViews::class, []);
        self::assertMatchesRegularExpression('/Guinea Pig/', $view->getUserProjectsAsOptions($user, $manager, '50'));
    }

    public function testOptionsShouldContainTheUnixNameOfTheProjectAsTitle(): void
    {
        $project = $this->givenAProject('Guinea Pig', 'gpig');
        $user    = $this->givenAUserWithProjects($project);
        $manager = $this->givenAProjectManager($project);

        $view = $this->createPartialMock(GitViews::class, []);
        self::assertMatchesRegularExpression('/title="gpig"/', $view->getUserProjectsAsOptions($user, $manager, '50'));
    }

    public function testOptionsShouldPurifyThePublicNameOfTheProject(): void
    {
        $project = $this->givenAProject('Guinea < Pig');
        $user    = $this->givenAUserWithProjects($project);
        $manager = $this->givenAProjectManager($project);

        $view = $this->createPartialMock(GitViews::class, []);
        self::assertMatchesRegularExpression('/Guinea &lt; Pig/', $view->getUserProjectsAsOptions($user, $manager, '50'));
    }

    public function testCurrentProjectMustNotBeInProjectList(): void
    {
        $project = $this->givenAProject('Guinea Pig');
        $user    = $this->givenAUserWithProjects($project);
        $manager = $this->givenAProjectManager($project);

        $view = $this->createPartialMock(GitViews::class, []);
        self::assertDoesNotMatchRegularExpression('/Guinea Pig/', $view->getUserProjectsAsOptions($user, $manager, 123));
    }

    public function testProjectListMustContainsOnlyProjectsWithGitEnabled(): void
    {
        $project = $this->givenAProjectWithoutGitService();
        $user    = $this->givenAUserWithProjects($project);
        $manager = $this->givenAProjectManager($project);

        $view = $this->createPartialMock(GitViews::class, []);
        self::assertDoesNotMatchRegularExpression('/Guinea Pig/', $view->getUserProjectsAsOptions($user, $manager, '50'));
    }

    private function givenAProject(string $name, ?string $unixName = null, bool $useGit = true): Project
    {
        $project = ProjectTestBuilder::aProject()
            ->withId(123)
            ->withPublicName($name)
            ->withoutServices();

        if ($unixName) {
            $project = $project->withUnixName($unixName);
        }

        if ($useGit) {
            $project = $project->withUsedService(GitPlugin::SERVICE_SHORTNAME);
        }

        return $project->build();
    }

    private function givenAProjectWithoutGitService(): Project
    {
        return $this->givenAProject('Guinea Pig', null, false);
    }

    private function givenAProjectManager(Project $project): ProjectManager
    {
        $manager = $this->createMock(ProjectManager::class);
        $manager->method('getProject')->willReturnCallback(static fn($id) => match ((int) $id) {
            (int) $project->getID() => $project,
            default                 => null,
        });

        return $manager;
    }

    private function givenAUserWithProjects(Project $project): PFUser
    {
        return UserTestBuilder::aUser()
            ->withProjects([123, 456])
            ->withAdministratorOf($project)
            ->withoutSiteAdministrator()
            ->build();
    }
}
