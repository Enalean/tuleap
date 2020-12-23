<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Reference;

use DateTimeImmutable;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Project_AccessException;
use ProjectManager;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\GitlabRepositoryFactory;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\CrossReferencePresenter;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;

class GitlabCrossReferenceOrganizerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabRepositoryFactory
     */
    private $gitlab_repository_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabCommitFactory
     */
    private $gitlab_commit_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectManager
     */
    private $project_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectAccessChecker
     */
    private $project_access_checker;
    /**
     * @var GitlabCrossReferenceOrganizer
     */
    private $organizer;

    protected function setUp(): void
    {
        $this->gitlab_repository_factory = Mockery::mock(GitlabRepositoryFactory::class);
        $this->gitlab_commit_factory = Mockery::mock(GitlabCommitFactory::class);
        $this->project_manager = Mockery::mock(ProjectManager::class);
        $this->project_access_checker = Mockery::mock(ProjectAccessChecker::class);
        $this->organizer = new GitlabCrossReferenceOrganizer(
            $this->gitlab_repository_factory,
            $this->gitlab_commit_factory,
            $this->project_manager,
            $this->project_access_checker,
        );
    }

    public function testItDoesNotOrganizeCrossReferencesItDoesNotKnow(): void
    {
        $user = Mockery::mock(PFUser::class);
        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCurrentUser'              => $user,
                    'getCrossReferencePresenters' => [
                        CrossReferencePresenterBuilder::get(1)->withType('nature_1')->build(),
                        CrossReferencePresenterBuilder::get(2)->withType('nature_2')->build(),
                        CrossReferencePresenterBuilder::get(3)->withType('nature_3')->build(),
                    ]
                ]
            )->getMock();


        $by_nature_organizer->shouldReceive('moveCrossReferenceToSection')->never();

        $this->organizer->organizeGitLabReferences($by_nature_organizer);
    }

    public function testItDoesNotOrganizeGitlabCrossReferencesIfProjectCannotBeAccessedByCurrentUser(): void
    {
        $user    = Mockery::mock(PFUser::class);
        $project = Mockery::mock(Project::class);

        $this->project_manager
            ->shouldReceive(['getProject' => $project])
            ->getMock();

        $this->project_access_checker
            ->shouldReceive('checkUserCanAccessProject')
            ->with($user, $project)
            ->once()
            ->andThrow(Mockery::mock(Project_AccessException::class));

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_gitlab_commit')
            ->build();

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCurrentUser'              => $user,
                    'getCrossReferencePresenters' => [$a_ref],
                ]
            )->getMock();

        $by_nature_organizer->shouldReceive('moveCrossReferenceToSection')->never();
        $by_nature_organizer
            ->shouldReceive('removeUnreadableCrossReference')
            ->with($a_ref)
            ->once();

        $this->organizer->organizeGitLabReferences($by_nature_organizer);
    }

    public function testItDoesNotOrganizeGitlabCrossReferencesIfRepositoryCannotBeFound(): void
    {
        $user    = Mockery::mock(PFUser::class);
        $project = Mockery::mock(Project::class);

        $this->project_manager
            ->shouldReceive(['getProject' => $project])
            ->getMock();

        $this->project_access_checker
            ->shouldReceive('checkUserCanAccessProject')
            ->with($user, $project)
            ->once();

        $this->gitlab_repository_factory
            ->shouldReceive('getGitlabRepositoryByNameInProject')
            ->with($project, 'john-snow/winter-is-coming')
            ->andReturn(null);

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_gitlab_commit')
            ->withValue('john-snow/winter-is-coming/14a9b6c0c0c965977cf2af2199f93df82afcdea3')
            ->build();

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCurrentUser'              => $user,
                    'getCrossReferencePresenters' => [$a_ref],
                ]
            )->getMock();

        $by_nature_organizer->shouldReceive('moveCrossReferenceToSection')->never();
        $by_nature_organizer
            ->shouldReceive('removeUnreadableCrossReference')
            ->with($a_ref)
            ->once();

        $this->organizer->organizeGitLabReferences($by_nature_organizer);
    }

    public function testItDoesNotOrganizeGitlabCrossReferencesIfCommitDataCannotBeFound(): void
    {
        $user    = Mockery::mock(PFUser::class);
        $project = Mockery::mock(Project::class)
            ->shouldReceive('getUnixNameLowercase')
            ->andReturn('thenightwatch')
            ->getMock();

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(1)
            ->andReturn($project);

        $this->project_access_checker
            ->shouldReceive('checkUserCanAccessProject')
            ->with($user, $project)
            ->once();

        $repository = new GitlabRepository(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new DateTimeImmutable()
        );

        $this->gitlab_repository_factory
            ->shouldReceive('getGitlabRepositoryByNameInProject')
            ->with($project, 'john-snow/winter-is-coming')
            ->andReturn($repository);

        $this->gitlab_commit_factory->shouldReceive('getGitlabCommitInRepositoryWithSha1')
            ->with($repository, '14a9b6c0c0c965977cf2af2199f93df82afcdea3')
            ->andReturn(null);

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_gitlab_commit')
            ->withValue('john-snow/winter-is-coming/14a9b6c0c0c965977cf2af2199f93df82afcdea3')
            ->build();

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCurrentUser'              => $user,
                    'getCrossReferencePresenters' => [$a_ref],
                ]
            )->getMock();

        $by_nature_organizer
            ->shouldReceive('removeUnreadableCrossReference')
            ->with($a_ref)
            ->once();

        $by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->never();

        $this->organizer->organizeGitLabReferences($by_nature_organizer);
    }

    public function testItOrganizesGitlabCrossReferencesInTheirRespectiveRepositorySection(): void
    {
        $user    = Mockery::mock(PFUser::class);
        $project = Mockery::mock(Project::class)
            ->shouldReceive('getUnixNameLowercase')
            ->andReturn('thenightwatch')
            ->getMock();

        $another_project = Mockery::mock(Project::class)
            ->shouldReceive(['getUnixNameLowerCase' => 'foodstocks'])
            ->getMock();

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(1)
            ->andReturn($project);

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(2)
            ->andReturn($another_project);

        $this->project_access_checker
            ->shouldReceive('checkUserCanAccessProject')
            ->with($user, $project)
            ->once();

        $this->project_access_checker
            ->shouldReceive('checkUserCanAccessProject')
            ->with($user, $another_project)
            ->once();

        $repository = new GitlabRepository(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new DateTimeImmutable()
        );

        $another_repository = new GitlabRepository(
            2,
            3,
            'winter-is-coming',
            'Need more hot chocolate, we crave sugar',
            'the_full_url',
            new DateTimeImmutable()
        );

        $this->gitlab_repository_factory
            ->shouldReceive('getGitlabRepositoryByNameInProject')
            ->with($project, 'john-snow/winter-is-coming')
            ->andReturn($repository);

        $this->gitlab_repository_factory
            ->shouldReceive('getGitlabRepositoryByNameInProject')
            ->with($another_project, 'samwell-tarly/winter-is-coming')
            ->andReturn($another_repository);

        $this->gitlab_commit_factory->shouldReceive('getGitlabCommitInRepositoryWithSha1')
            ->with($repository, '14a9b6c0c0c965977cf2af2199f93df82afcdea3')
            ->andReturn(new GitlabCommit(
                2,
                '14a9b6c0c0c965977cf2af2199f93df82afcdea3',
                1608555618,
                'Increase blankets stocks for winter',
                "master",
                'John Snow',
                'john-snow@the-wall.com',
            ));

        $this->gitlab_commit_factory->shouldReceive('getGitlabCommitInRepositoryWithSha1')
            ->with($another_repository, 'be35d127acb88876ee4fdbf02188d372dc61e98d')
            ->andReturn(new GitlabCommit(
                3,
                'be35d127acb88876ee4fdbf02188d372dc61e98d',
                1608555618,
                'Increase hot chocolate stocks for winter',
                "master",
                'Samwell Tarly',
                'samwell-tarly@the-wall.com',
            ));

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_gitlab_commit')
            ->withValue('john-snow/winter-is-coming/14a9b6c0c0c965977cf2af2199f93df82afcdea3')
            ->withProjectId(1)
            ->build();

        $another_ref = CrossReferencePresenterBuilder::get(2)
            ->withType('plugin_gitlab_commit')
            ->withValue('samwell-tarly/winter-is-coming/be35d127acb88876ee4fdbf02188d372dc61e98d')
            ->withProjectId(2)
            ->build();

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCurrentUser'              => $user,
                    'getCrossReferencePresenters' => [$a_ref, $another_ref],
                ]
            )->getMock();

        $by_nature_organizer->shouldReceive('removeCrossReferenceToSection')->never();
        $by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->with(
                Mockery::on(function (CrossReferencePresenter $ref) {
                    return $ref->id === 1
                        && $ref->additional_badges[0]->label === '14a9b6c0c0';
                }),
                'thenightwatch/winter-is-coming'
            )
            ->once();

        $by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->with(
                Mockery::on(function (CrossReferencePresenter $ref) {
                    return $ref->id === 2
                        && $ref->additional_badges[0]->label === 'be35d127ac';
                }),
                'foodstocks/winter-is-coming'
            )
            ->once();

        $this->organizer->organizeGitLabReferences($by_nature_organizer);
    }
}
