<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\PullRequest;

use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class PullRequestEmptyStatePresenterBuilderTest extends TestCase
{
    private const PARENT_REPOSITORY_URL = "url/to/parent_repository.git";

    private PullRequestEmptyStatePresenterBuilder $presenter_builder;

    protected function setUp(): void
    {
        $url_verification = $this->createStub(\URLVerification::class);
        $url_verification->method('userCanAccessProject')->willThrowException(new \Project_AccessPrivateException());

        $repository_url_manager = $this->createStub(\Git_GitRepositoryUrlManager::class);
        $repository_url_manager->method('getRepositoryBaseUrl')->willReturn(self::PARENT_REPOSITORY_URL);

        $this->presenter_builder = new PullRequestEmptyStatePresenterBuilder(
            $repository_url_manager,
            $url_verification
        );
    }

    public function testItBuildsAPresenter(): void
    {
        $repository_id = 2;
        $project_id    = 102;

        $parent_repository_id         = 1;
        $parent_repository_project_id = 104;
        $parent_repository_name       = "parent-repository.git";

        $parent_repository = GitRepositoryTestBuilder::aProjectRepository()
            ->withId($parent_repository_id)
            ->withName($parent_repository_name)
            ->inProject(ProjectTestBuilder::aProject()->withId($parent_repository_project_id)->build())
            ->build();

        $repository = GitRepositoryTestBuilder::aForkOf($parent_repository)
            ->inProject(ProjectTestBuilder::aProject()->withId($project_id)->build())
            ->migratedToGerrit()
            ->withId($repository_id)
            ->build();

        $presenter = $this->presenter_builder->build($repository, UserTestBuilder::aUser()->build());
        \assert($presenter instanceof PullRequestEmptyStatePresenter);

        self::assertSame($repository_id, $presenter->repository_id);
        self::assertSame($project_id, $presenter->project_id);
        self::assertTrue($presenter->is_migrated_to_gerrit);

        self::assertNotNull($presenter->parent_repository_presenter);
        self::assertSame(self::PARENT_REPOSITORY_URL, $presenter->parent_repository_presenter->parent_repository_url);
        self::assertSame($parent_repository_name, $presenter->parent_repository_presenter->parent_repository_name);
        self::assertSame($parent_repository_id, $presenter->parent_repository_presenter->parent_repository_id);
        self::assertSame((string) $parent_repository_project_id, $presenter->parent_repository_presenter->parent_project_id);
    }
}
