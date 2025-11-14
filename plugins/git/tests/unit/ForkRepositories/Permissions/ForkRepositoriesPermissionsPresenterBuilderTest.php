<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

namespace Tuleap\Git\ForkRepositories\Permissions;

use GitPresenters_AccessControlPresenter;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Git\ForkRepositories\ForkRepositoriesUrlsBuilder;
use Tuleap\Git\GitAccessControlPresenterBuilder;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Git\Tests\Stub\RetrieveGitRepositoryStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ForkRepositoriesPermissionsPresenterBuilderTest extends TestCase
{
    private \Project $destination_project;
    private \PFUser $user;
    /**
     * @var list<string>
     */
    private array $repositories_ids;
    private string $fork_path;
    private ForkType $fork_type;
    private GitAccessControlPresenterBuilder&MockObject $access_control_presenter_builder;

    #[\Override]
    protected function setUp(): void
    {
        $this->destination_project              = ProjectTestBuilder::aProject()->withId(110)->withPublicName('Forks and knives')->withIcon('🍴')->build();
        $this->user                             = UserTestBuilder::aUser()->build();
        $this->repositories_ids                 = [];
        $this->fork_path                        = '';
        $this->fork_type                        = ForkType::PERSONAL;
        $this->access_control_presenter_builder = $this->createMock(GitAccessControlPresenterBuilder::class);
    }

    private function buildPresenter(): ForkRepositoriesPermissionsPresenter
    {
        $repositories = array_map(
            static fn (string $id) => GitRepositoryTestBuilder::aProjectRepository()->withId((int) $id)->withName("repository-$id")->build(),
            $this->repositories_ids
        );
        $builder      = new ForkRepositoriesPermissionsPresenterBuilder(
            ! empty($repositories) ? RetrieveGitRepositoryStub::withGitRepositories(...$repositories) : RetrieveGitRepositoryStub::withoutGitRepository(),
            $this->access_control_presenter_builder,
        );

        return $builder->build(
            $this->destination_project,
            $this->user,
            new ForkRepositoriesFormInputs(
                $this->repositories_ids,
                (string) $this->destination_project->getID(),
                $this->fork_path,
                $this->fork_type,
            ),
            CSRFSynchronizerTokenStub::buildSelf(),
        );
    }

    public function testItBuildsThePresenterForASingleRepositoryFork(): void
    {
        $access_control_presenter = $this->createStub(GitPresenters_AccessControlPresenter::class);
        $this->access_control_presenter_builder->expects($this->once())->method('buildForSingleRepositoryFork')->willReturn($access_control_presenter);
        $this->fork_type        = ForkType::PERSONAL;
        $this->fork_path        = 'my-forks/';
        $this->repositories_ids = ['1'];

        $presenter = $this->buildPresenter();

        self::assertSame((int) $this->destination_project->getID(), $presenter->project_id);
        self::assertSame(1, $presenter->nb_repositories);
        self::assertSame(ForkType::PERSONAL->value, $presenter->fork_type);
        self::assertSame($this->fork_path, $presenter->fork_path);
        self::assertSame(['repository-1'], $presenter->repositories_names);
        self::assertSame(ForkRepositoriesUrlsBuilder::buildPOSTDoForksRepositoriesURL($this->destination_project), $presenter->post_url);
        self::assertSame($access_control_presenter, $presenter->access_control_presenter);
    }

    public function testItBuildsThePresenterForACrossProjectFork(): void
    {
        $access_control_presenter = $this->createStub(GitPresenters_AccessControlPresenter::class);
        $this->access_control_presenter_builder->expects($this->once())->method('buildWithDefaults')->willReturn($access_control_presenter);
        $this->fork_type        = ForkType::CROSS_PROJECT;
        $this->repositories_ids = ['1', '2', '5'];

        $presenter = $this->buildPresenter();

        self::assertSame((int) $this->destination_project->getID(), $presenter->project_id);
        self::assertSame(3, $presenter->nb_repositories);
        self::assertSame(ForkType::CROSS_PROJECT->value, $presenter->fork_type);
        self::assertSame($this->fork_path, $presenter->fork_path);
        self::assertSame(['repository-1', 'repository-2', 'repository-5'], $presenter->repositories_names);
        self::assertSame(ForkRepositoriesUrlsBuilder::buildPOSTDoForksRepositoriesURL($this->destination_project), $presenter->post_url);
        self::assertSame($access_control_presenter, $presenter->access_control_presenter);
    }
}
