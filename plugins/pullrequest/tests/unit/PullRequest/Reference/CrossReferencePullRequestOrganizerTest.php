<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Reference;

use GitRepositoryFactory;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use ProjectManager;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\GlobalLanguageMock;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Exception\PullRequestNotFoundException;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\Factory;
use Tuleap\PullRequest\PullRequest;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\CrossReferencePresenter;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;
use UserHelper;
use UserManager;

class CrossReferencePullRequestOrganizerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var CrossReferencePullRequestOrganizer
     */
    private $organizer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectManager
     */
    private $project_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Factory
     */
    private $pull_request_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PullRequestPermissionChecker
     */
    private $permission_checker;
    /**
     * @var GitRepositoryFactory|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $git_repository_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UserManager
     */
    private $user_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UserHelper
     */
    private $user_helper;

    protected function setUp(): void
    {
        $this->project_manager        = Mockery::mock(ProjectManager::class);
        $this->pull_request_factory   = Mockery::mock(Factory::class);
        $this->permission_checker     = Mockery::mock(PullRequestPermissionChecker::class);
        $this->git_repository_factory = Mockery::mock(GitRepositoryFactory::class);
        $this->user_manager           = Mockery::mock(UserManager::class);
        $this->user_helper            = Mockery::mock(UserHelper::class);

        $this->organizer = new CrossReferencePullRequestOrganizer(
            $this->project_manager,
            $this->pull_request_factory,
            $this->permission_checker,
            $this->git_repository_factory,
            new TlpRelativeDatePresenterBuilder(),
            $this->user_manager,
            $this->user_helper,
        );

        $GLOBALS['Language']
            ->method('getText')
            ->with('system', 'datefmt')
            ->willReturn('d/m/Y H:i');
    }

    public function testItDoesNotOrganizeCrossReferencesItDoesNotKnow(): void
    {
        $user                = Mockery::mock(PFUser::class);
        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCurrentUser'              => $user,
                    'getCrossReferencePresenters' => [
                        CrossReferencePresenterBuilder::get(1)->withType('git')->build(),
                        CrossReferencePresenterBuilder::get(2)->withType('tracker')->build(),
                        CrossReferencePresenterBuilder::get(3)->withType('whatever')->build(),
                    ],
                ]
            )->getMock();


        $by_nature_organizer->shouldReceive('moveCrossReferenceToSection')->never();

        $this->organizer->organizePullRequestReferences($by_nature_organizer);
    }

    public function testItRemovesPullRequestCrossReferenceIfPRIsNotFound(): void
    {
        $user = Mockery::mock(PFUser::class);

        $ref = CrossReferencePresenterBuilder::get(2)
            ->withType('pullrequest')
            ->withValue("42")
            ->build();

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCurrentUser'              => $user,
                    'getCrossReferencePresenters' => [
                        CrossReferencePresenterBuilder::get(1)->withType('git')->build(),
                        $ref,
                        CrossReferencePresenterBuilder::get(3)->withType('whatever')->build(),
                    ],
                ]
            )->getMock();

        $this->pull_request_factory
            ->shouldReceive('getPullRequestById')
            ->with(42)
            ->andThrow(PullRequestNotFoundException::class);

        $by_nature_organizer
            ->shouldReceive('removeUnreadableCrossReference')
            ->with($ref)
            ->once();

        $this->organizer->organizePullRequestReferences($by_nature_organizer);
    }

    public function testItRemovesPullRequestCrossReferenceIfPRBelongsToAnInaccessibleProject(): void
    {
        $user = Mockery::mock(PFUser::class);

        $ref = CrossReferencePresenterBuilder::get(2)
            ->withType('pullrequest')
            ->withValue("42")
            ->build();

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCurrentUser'              => $user,
                    'getCrossReferencePresenters' => [
                        CrossReferencePresenterBuilder::get(1)->withType('git')->build(),
                        $ref,
                        CrossReferencePresenterBuilder::get(3)->withType('whatever')->build(),
                    ],
                ]
            )->getMock();

        $pull_request = Mockery::mock(PullRequest::class);

        $this->pull_request_factory
            ->shouldReceive('getPullRequestById')
            ->with(42)
            ->andReturns($pull_request);

        $this->permission_checker
            ->shouldReceive('checkPullRequestIsReadableByUser')
            ->with($pull_request, $user)
            ->andThrow(Mockery::mock(\Project_AccessException::class));

        $by_nature_organizer
            ->shouldReceive('removeUnreadableCrossReference')
            ->with($ref)
            ->once();

        $this->organizer->organizePullRequestReferences($by_nature_organizer);
    }

    public function testItRemovesPullRequestCrossReferenceIfPRBelongsToARepositoryTheUserCannotAccess(): void
    {
        $user = Mockery::mock(PFUser::class);

        $ref = CrossReferencePresenterBuilder::get(2)
            ->withType('pullrequest')
            ->withValue("42")
            ->build();

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCurrentUser'              => $user,
                    'getCrossReferencePresenters' => [
                        CrossReferencePresenterBuilder::get(1)->withType('git')->build(),
                        $ref,
                        CrossReferencePresenterBuilder::get(3)->withType('whatever')->build(),
                    ],
                ]
            )->getMock();

        $pull_request = Mockery::mock(PullRequest::class);

        $this->pull_request_factory
            ->shouldReceive('getPullRequestById')
            ->with(42)
            ->andReturns($pull_request);

        $this->permission_checker
            ->shouldReceive('checkPullRequestIsReadableByUser')
            ->with($pull_request, $user)
            ->andThrow(UserCannotReadGitRepositoryException::class);

        $by_nature_organizer
            ->shouldReceive('removeUnreadableCrossReference')
            ->with($ref)
            ->once();

        $this->organizer->organizePullRequestReferences($by_nature_organizer);
    }

    public function testItRemovesPullRequestCrossReferenceIfPRBelongsToARepositoryWeCannotInstantiate(): void
    {
        $user = Mockery::mock(PFUser::class);

        $ref = CrossReferencePresenterBuilder::get(2)
            ->withType('pullrequest')
            ->withValue("42")
            ->build();

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCurrentUser'              => $user,
                    'getCrossReferencePresenters' => [
                        CrossReferencePresenterBuilder::get(1)->withType('git')->build(),
                        $ref,
                        CrossReferencePresenterBuilder::get(3)->withType('whatever')->build(),
                    ],
                ]
            )->getMock();

        $pull_request = Mockery::mock(
            PullRequest::class,
            [
                'getRepositoryId' => 101,
            ]
        );

        $this->pull_request_factory
            ->shouldReceive('getPullRequestById')
            ->with(42)
            ->andReturns($pull_request);

        $this->permission_checker
            ->shouldReceive('checkPullRequestIsReadableByUser')
            ->with($pull_request, $user);

        $this->git_repository_factory
            ->shouldReceive('getRepositoryById')
            ->with(101)
            ->andReturnNull();

        $by_nature_organizer
            ->shouldReceive('removeUnreadableCrossReference')
            ->with($ref)
            ->once();

        $this->organizer->organizePullRequestReferences($by_nature_organizer);
    }

    /**
     * @testWith ["A", "Abandonned"]
     *           ["M", "Merged"]
     *           ["R", "Review"]
     */
    public function testItMovesCrossReferenceToRepositorySection($status, $expected_status_label): void
    {
        $user = Mockery::mock(
            PFUser::class,
            [
                'getLocale'     => 'en_US',
                'getPreference' => 'relative_first-absolute_tooltip',
            ]
        );

        $ref = CrossReferencePresenterBuilder::get(2)
            ->withType('pullrequest')
            ->withValue("42")
            ->build();

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCurrentUser'              => $user,
                    'getCrossReferencePresenters' => [
                        CrossReferencePresenterBuilder::get(1)->withType('git')->build(),
                        $ref,
                        CrossReferencePresenterBuilder::get(3)->withType('whatever')->build(),
                    ],
                ]
            )->getMock();

        $pull_request = Mockery::mock(
            PullRequest::class,
            [
                'getRepositoryId' => 101,
                'getTitle'        => 'Lorem ipsum doloret',
                'getCreationDate' => 1234567890,
                'getUserId'       => 1001,
                'getStatus'       => $status,
            ]
        );

        $this->pull_request_factory
            ->shouldReceive('getPullRequestById')
            ->with(42)
            ->andReturns($pull_request);

        $this->permission_checker
            ->shouldReceive('checkPullRequestIsReadableByUser')
            ->with($pull_request, $user);

        $this->git_repository_factory
            ->shouldReceive('getRepositoryById')
            ->with(101)
            ->andReturn(
                Mockery::mock(
                    \GitRepository::class,
                    [
                        'getName' => 'barry/ginger',
                    ]
                )
            );

        $this->project_manager
            ->shouldReceive('getProject')
            ->andReturn(
                Mockery::mock(
                    \Project::class,
                    [
                        'getUnixNameLowerCase' => 'peculiar',
                    ]
                )
            );

        $this->user_manager
            ->shouldReceive('getUserById')
            ->with(1001)
            ->andReturn(
                Mockery::mock(
                    PFUser::class,
                    [
                        'hasAvatar'    => true,
                        'getAvatarUrl' => '/path/to/avatar.png',
                    ]
                )
            );

        $this->user_helper
            ->shouldReceive('getDisplayNameFromUser')
            ->andReturn('John Doe');

        $by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->with(
                Mockery::on(
                    function (CrossReferencePresenter $new_ref) use ($expected_status_label) {
                        return $new_ref->id === 2
                            && $new_ref->title === 'Lorem ipsum doloret'
                            && $new_ref->additional_badges[0]->label === $expected_status_label
                            && $new_ref->creation_metadata->created_by->display_name === 'John Doe'
                            && $new_ref->creation_metadata->created_on->date === '2009-02-14T00:31:30+01:00';
                    }
                ),
                'peculiar/barry/ginger',
            )
            ->once();

        $this->organizer->organizePullRequestReferences($by_nature_organizer);
    }
}
