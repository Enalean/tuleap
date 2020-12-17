<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Reference;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\CrossReferencePresenter;

class CrossReferenceGitOrganizerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\ProjectManager
     */
    private $project_manager;
    /**
     * @var \Git_ReferenceManager|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $git_reference_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectAccessChecker
     */
    private $access_checker;
    /**
     * @var CrossReferenceGitOrganizer
     */
    private $organizer;

    protected function setUp(): void
    {
        $this->project_manager       = Mockery::mock(\ProjectManager::class);
        $this->git_reference_manager = Mockery::mock(\Git_ReferenceManager::class);
        $this->access_checker        = Mockery::mock(ProjectAccessChecker::class);

        $this->organizer = new CrossReferenceGitOrganizer(
            $this->project_manager,
            $this->git_reference_manager,
            $this->access_checker
        );
    }

    public function testItDoesNotOrganizeCrossReferencesItDoesNotKnow(): void
    {
        $user = Mockery::mock(\PFUser::class);

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCurrentUser'     => $user,
                    'getCrossReferences' => [
                        new CrossReferencePresenter(1, "tracker", "another_title", "url", "delete_url", 1, "whatever"),
                    ]
                ]
            )->getMock();

        $by_nature_organizer->shouldReceive('moveCrossReferenceToSection')->never();

        $this->organizer->organizeGitReferences($by_nature_organizer);
    }

    public function testItDoesNotOrganizeGitCrossReferencesIfRepositoryCannotBeFound(): void
    {
        $user    = Mockery::mock(\PFUser::class);
        $project = Mockery::mock(Project::class);

        $this->project_manager
            ->shouldReceive(['getProject' => $project])
            ->getMock();

        $this->access_checker
            ->shouldReceive('checkUserCanAccessProject')
            ->with($user, $project)
            ->once();

        $this->git_reference_manager
            ->shouldReceive('getRepositoryFromCrossReferenceValue')
            ->with($project, 'cloudy/stable/1a2b3c4d5e')
            ->andReturnNull();

        $a_ref = new CrossReferencePresenter(
            1,
            "git_commit",
            "title",
            "url",
            "delete_url",
            1,
            'cloudy/stable/1a2b3c4d5e'
        );

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCurrentUser'     => $user,
                    'getCrossReferences' => [$a_ref],
                ]
            )->getMock();

        $by_nature_organizer->shouldReceive('moveCrossReferenceToSection')->never();
        $by_nature_organizer
            ->shouldReceive('removeUnreadableCrossReference')
            ->with($a_ref)
            ->once();

        $this->organizer->organizeGitReferences($by_nature_organizer);
    }

    public function testItDoesNotOrganizeGitCrossReferencesIfProjectCannotBeAccessedByCurrentUser(): void
    {
        $user    = Mockery::mock(\PFUser::class);
        $project = Mockery::mock(Project::class);

        $this->project_manager
            ->shouldReceive(['getProject' => $project])
            ->getMock();

        $this->access_checker
            ->shouldReceive('checkUserCanAccessProject')
            ->with($user, $project)
            ->once()
            ->andThrow(Mockery::mock(\Project_AccessException::class));

        $a_ref = new CrossReferencePresenter(
            1,
            "git_commit",
            "title",
            "url",
            "delete_url",
            1,
            'cloudy/stable/1a2b3c4d5e'
        );

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCurrentUser'     => $user,
                    'getCrossReferences' => [
                        $a_ref,
                    ]
                ]
            )->getMock();

        $by_nature_organizer->shouldReceive('moveCrossReferenceToSection')->never();
        $by_nature_organizer
            ->shouldReceive('removeUnreadableCrossReference')
            ->with($a_ref)
            ->once();

        $this->organizer->organizeGitReferences($by_nature_organizer);
    }

    public function testItOrganizesGitCrossReferencesInTheirRespectiveRepositorySection(): void
    {
        $user    = Mockery::mock(\PFUser::class);
        $project = Mockery::mock(Project::class)
            ->shouldReceive(['getUnixNameLowerCase' => 'acme'])
            ->getMock();

        $another_project = Mockery::mock(Project::class)
            ->shouldReceive(['getUnixNameLowerCase' => 'foobar'])
            ->getMock();

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(1)
            ->andReturn($project);
        $this->project_manager
            ->shouldReceive('getProject')
            ->with(2)
            ->andReturn($another_project);

        $this->access_checker
            ->shouldReceive('checkUserCanAccessProject')
            ->with($user, $project)
            ->once();
        $this->access_checker
            ->shouldReceive('checkUserCanAccessProject')
            ->with($user, $another_project)
            ->once();

        $repository         = Mockery::mock(\GitRepository::class)
            ->shouldReceive(['getFullName' => 'cloudy/stable', 'userCanRead' => true])
            ->getMock();
        $another_repository = Mockery::mock(\GitRepository::class)
            ->shouldReceive(['getFullName' => 'tuleap/stable', 'userCanRead' => true])
            ->getMock();

        $this->git_reference_manager
            ->shouldReceive('getRepositoryFromCrossReferenceValue')
            ->with($project, 'cloudy/stable/1a2b3c4d5e')
            ->andReturn($repository);
        $this->git_reference_manager
            ->shouldReceive('getRepositoryFromCrossReferenceValue')
            ->with($another_project, 'tuleap/stable/e5d4c3b2a1')
            ->andReturn($another_repository);

        $a_ref = new CrossReferencePresenter(
            1,
            "git_commit",
            "title",
            "url",
            "delete_url",
            1,
            'cloudy/stable/1a2b3c4d5e'
        );

        $another_ref = new CrossReferencePresenter(
            2,
            "git_commit",
            "title",
            "url",
            "delete_url",
            2,
            'tuleap/stable/e5d4c3b2a1'
        );

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCrossReferences' => [$a_ref, $another_ref],
                    'getCurrentUser'     => $user,
                ]
            )->getMock();

        $by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->with($a_ref, 'acme/cloudy/stable')
            ->once();
        $by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->with($another_ref, 'foobar/tuleap/stable')
            ->once();

        $this->organizer->organizeGitReferences($by_nature_organizer);
    }

    public function testItIgnoresRepositoriesUserCannotRead(): void
    {
        $user    = Mockery::mock(\PFUser::class);
        $project = Mockery::mock(Project::class)
            ->shouldReceive(['getUnixNameLowerCase' => 'acme'])
            ->getMock();

        $another_project = Mockery::mock(Project::class)
            ->shouldReceive(['getUnixNameLowerCase' => 'foobar'])
            ->getMock();

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(1)
            ->andReturn($project);
        $this->project_manager
            ->shouldReceive('getProject')
            ->with(2)
            ->andReturn($another_project);

        $this->access_checker
            ->shouldReceive('checkUserCanAccessProject')
            ->with($user, $project)
            ->once();
        $this->access_checker
            ->shouldReceive('checkUserCanAccessProject')
            ->with($user, $another_project)
            ->once();

        $repository         = Mockery::mock(\GitRepository::class)
            ->shouldReceive(['getFullName' => 'cloudy/stable', 'userCanRead' => true])
            ->getMock();
        $another_repository = Mockery::mock(\GitRepository::class)
            ->shouldReceive(['getFullName' => 'tuleap/stable', 'userCanRead' => false])
            ->getMock();

        $this->git_reference_manager
            ->shouldReceive('getRepositoryFromCrossReferenceValue')
            ->with($project, 'cloudy/stable/1a2b3c4d5e')
            ->andReturn($repository);
        $this->git_reference_manager
            ->shouldReceive('getRepositoryFromCrossReferenceValue')
            ->with($another_project, 'tuleap/stable/e5d4c3b2a1')
            ->andReturn($another_repository);

        $a_ref = new CrossReferencePresenter(
            1,
            "git_commit",
            "title",
            "url",
            "delete_url",
            1,
            'cloudy/stable/1a2b3c4d5e'
        );

        $another_ref = new CrossReferencePresenter(
            2,
            "git_commit",
            "title",
            "url",
            "delete_url",
            2,
            'tuleap/stable/e5d4c3b2a1'
        );

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCrossReferences' => [$a_ref, $another_ref],
                    'getCurrentUser'     => $user,
                ]
            )->getMock();

        $by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->with($a_ref, 'acme/cloudy/stable')
            ->once();
        $by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->with($another_ref, 'foobar/tuleap/stable')
            ->never();
        $by_nature_organizer
            ->shouldReceive('removeUnreadableCrossReference')
            ->with($another_ref)
            ->once();

        $this->organizer->organizeGitReferences($by_nature_organizer);
    }
}
