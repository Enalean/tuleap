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
use Tuleap\Test\Builders\ProjectTestBuilder;
use UserHelper;
use UserManager;

final class CrossReferencePullRequestOrganizerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private CrossReferencePullRequestOrganizer $organizer;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ProjectManager
     */
    private $project_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Factory
     */
    private $pull_request_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PullRequestPermissionChecker
     */
    private $permission_checker;
    /**
     * @var GitRepositoryFactory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $git_repository_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&UserManager
     */
    private $user_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&UserHelper
     */
    private $user_helper;

    protected function setUp(): void
    {
        $this->project_manager        = $this->createMock(ProjectManager::class);
        $this->pull_request_factory   = $this->createMock(Factory::class);
        $this->permission_checker     = $this->createMock(PullRequestPermissionChecker::class);
        $this->git_repository_factory = $this->createMock(GitRepositoryFactory::class);
        $this->user_manager           = $this->createMock(UserManager::class);
        $this->user_helper            = $this->createMock(UserHelper::class);

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
        $user                = $this->createMock(PFUser::class);
        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($user);
        $by_nature_organizer->method('getCrossReferencePresenters')->willReturn(
            [
                CrossReferencePresenterBuilder::get(1)->withType('git')->build(),
                CrossReferencePresenterBuilder::get(2)->withType('tracker')->build(),
                CrossReferencePresenterBuilder::get(3)->withType('whatever')->build(),
            ],
        );
        $by_nature_organizer->expects(self::never())->method('moveCrossReferenceToSection');

        $this->organizer->organizePullRequestReferences($by_nature_organizer);
    }

    public function testItRemovesPullRequestCrossReferenceIfPRIsNotFound(): void
    {
        $user = $this->createMock(PFUser::class);

        $ref = CrossReferencePresenterBuilder::get(2)
            ->withType('pullrequest')
            ->withValue("42")
            ->build();

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($user);
        $by_nature_organizer->method('getCrossReferencePresenters')->willReturn(
            [
                CrossReferencePresenterBuilder::get(1)->withType('git')->build(),
                $ref,
                CrossReferencePresenterBuilder::get(3)->withType('whatever')->build(),
            ],
        );

        $this->pull_request_factory
            ->method('getPullRequestById')
            ->with(42)
            ->willThrowException(new PullRequestNotFoundException());

        $by_nature_organizer
            ->expects(self::once())
            ->method('removeUnreadableCrossReference')
            ->with($ref);

        $this->organizer->organizePullRequestReferences($by_nature_organizer);
    }

    public function testItRemovesPullRequestCrossReferenceIfPRBelongsToAnInaccessibleProject(): void
    {
        $user = $this->createMock(PFUser::class);

        $ref = CrossReferencePresenterBuilder::get(2)
            ->withType('pullrequest')
            ->withValue("42")
            ->build();

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($user);
        $by_nature_organizer->method('getCrossReferencePresenters')->willReturn(
            [
                CrossReferencePresenterBuilder::get(1)->withType('git')->build(),
                $ref,
                CrossReferencePresenterBuilder::get(3)->withType('whatever')->build(),
            ],
        );

        $pull_request = $this->createMock(PullRequest::class);

        $this->pull_request_factory
            ->method('getPullRequestById')
            ->with(42)
            ->willReturn($pull_request);

        $this->permission_checker
            ->method('checkPullRequestIsReadableByUser')
            ->with($pull_request, $user)
            ->willThrowException($this->createMock(\Project_AccessException::class));

        $by_nature_organizer
            ->expects(self::once())
            ->method('removeUnreadableCrossReference')
            ->with($ref);

        $this->organizer->organizePullRequestReferences($by_nature_organizer);
    }

    public function testItRemovesPullRequestCrossReferenceIfPRBelongsToARepositoryTheUserCannotAccess(): void
    {
        $user = $this->createMock(PFUser::class);

        $ref = CrossReferencePresenterBuilder::get(2)
            ->withType('pullrequest')
            ->withValue("42")
            ->build();

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($user);
        $by_nature_organizer->method('getCrossReferencePresenters')->willReturn(
            [
                CrossReferencePresenterBuilder::get(1)->withType('git')->build(),
                $ref,
                CrossReferencePresenterBuilder::get(3)->withType('whatever')->build(),
            ],
        );

        $pull_request = $this->createMock(PullRequest::class);

        $this->pull_request_factory
            ->method('getPullRequestById')
            ->with(42)
            ->willReturn($pull_request);

        $this->permission_checker
            ->method('checkPullRequestIsReadableByUser')
            ->with($pull_request, $user)
            ->willThrowException(new UserCannotReadGitRepositoryException());

        $by_nature_organizer
            ->expects(self::once())
            ->method('removeUnreadableCrossReference')
            ->with($ref);

        $this->organizer->organizePullRequestReferences($by_nature_organizer);
    }

    public function testItRemovesPullRequestCrossReferenceIfPRBelongsToARepositoryWeCannotInstantiate(): void
    {
        $user = $this->createMock(PFUser::class);

        $ref = CrossReferencePresenterBuilder::get(2)
            ->withType('pullrequest')
            ->withValue("42")
            ->build();

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($user);
        $by_nature_organizer->method('getCrossReferencePresenters')->willReturn(
            [
                CrossReferencePresenterBuilder::get(1)->withType('git')->build(),
                $ref,
                CrossReferencePresenterBuilder::get(3)->withType('whatever')->build(),
            ],
        );

        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getRepositoryId')->willReturn(101);

        $this->pull_request_factory
            ->method('getPullRequestById')
            ->with(42)
            ->willReturn($pull_request);

        $this->permission_checker
            ->method('checkPullRequestIsReadableByUser')
            ->with($pull_request, $user);

        $this->git_repository_factory
            ->method('getRepositoryById')
            ->with(101)
            ->willReturn(null);

        $by_nature_organizer
            ->expects(self::once())
            ->method('removeUnreadableCrossReference')
            ->with($ref);

        $this->organizer->organizePullRequestReferences($by_nature_organizer);
    }

    /**
     * @testWith ["A", "Abandonned"]
     *           ["M", "Merged"]
     *           ["R", "Review"]
     */
    public function testItMovesCrossReferenceToRepositorySection(string $status, string $expected_status_label): void
    {
        $user = $this->createMock(PFUser::class);
        $user->method('getLocale')->willReturn('en_US');
        $user->method('getPreference')->willReturn('relative_first-absolute_tooltip');

        $ref = CrossReferencePresenterBuilder::get(2)
            ->withType('pullrequest')
            ->withValue("42")
            ->build();

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($user);
        $by_nature_organizer->method('getCrossReferencePresenters')->willReturn(
            [
                CrossReferencePresenterBuilder::get(1)->withType('git')->build(),
                $ref,
                CrossReferencePresenterBuilder::get(3)->withType('whatever')->build(),
            ],
        );

        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getRepositoryId')->willReturn(101);
        $pull_request->method('getTitle')->willReturn('Lorem ipsum doloret');
        $pull_request->method('getCreationDate')->willReturn(1234567890);
        $pull_request->method('getUserId')->willReturn(1001);
        $pull_request->method('getStatus')->willReturn($status);

        $this->pull_request_factory
            ->method('getPullRequestById')
            ->with(42)
            ->willReturn($pull_request);

        $this->permission_checker
            ->method('checkPullRequestIsReadableByUser')
            ->with($pull_request, $user);

        $gir_repository = $this->createMock(\GitRepository::class);
        $gir_repository->method('getName')->willReturn('barry/ginger');

        $this->git_repository_factory
            ->method('getRepositoryById')
            ->with(101)
            ->willReturn($gir_repository);

        $this->project_manager
            ->method('getProject')
            ->willReturn(ProjectTestBuilder::aProject()->withUnixName('peculiar')->build());

        $user_1001 = $this->createMock(PFUser::class);
        $user_1001->method('hasAvatar')->willReturn(true);
        $user_1001->method('getAvatarUrl')->willReturn('/path/to/avatar.png');

        $this->user_manager
            ->method('getUserById')
            ->with(1001)
            ->willReturn($user_1001);

        $this->user_helper
            ->method('getDisplayNameFromUser')
            ->willReturn('John Doe');

        $by_nature_organizer
            ->expects(self::once())
            ->method('moveCrossReferenceToSection')
            ->with(
                self::callback(
                    function (CrossReferencePresenter $new_ref) use ($expected_status_label): bool {
                        return $new_ref->id === 2
                            && $new_ref->title === 'Lorem ipsum doloret'
                            && $new_ref->additional_badges[0]->label === $expected_status_label
                            && $new_ref->creation_metadata !== null
                            && $new_ref->creation_metadata->created_by !== null
                            && $new_ref->creation_metadata->created_by->display_name === 'John Doe'
                            && $new_ref->creation_metadata->created_on->date === '2009-02-14T00:31:30+01:00';
                    }
                ),
                'peculiar/barry/ginger',
            );

        $this->organizer->organizePullRequestReferences($by_nature_organizer);
    }
}
