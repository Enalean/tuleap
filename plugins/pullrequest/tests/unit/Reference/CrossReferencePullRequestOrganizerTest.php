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

use GitRepository;
use GitRepositoryFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Project_AccessException;
use ProjectManager;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\GlobalLanguageMock;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\SearchPullRequestStub;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\CrossReferencePresenter;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use UserHelper;

final class CrossReferencePullRequestOrganizerTest extends TestCase
{
    use GlobalLanguageMock;

    private MockObject&ProjectManager $project_manager;
    private MockObject&PullRequestPermissionChecker $permission_checker;
    private GitRepositoryFactory&MockObject $git_repository_factory;
    private RetrieveUserByIdStub $user_manager;
    private MockObject&UserHelper $user_helper;
    private SearchPullRequestStub $pull_request_dao;

    protected function setUp(): void
    {
        $this->project_manager        = $this->createMock(ProjectManager::class);
        $this->pull_request_dao       = SearchPullRequestStub::withNoRow();
        $this->permission_checker     = $this->createMock(PullRequestPermissionChecker::class);
        $this->git_repository_factory = $this->createMock(GitRepositoryFactory::class);
        $this->user_manager           = RetrieveUserByIdStub::withNoUser();
        $this->user_helper            = $this->createMock(UserHelper::class);


        $GLOBALS['Language']
            ->method('getText')
            ->with('system', 'datefmt')
            ->willReturn('d/m/Y H:i');
    }

    private function organizePullRequestReferences(MockObject&CrossReferenceByNatureOrganizer $by_nature_organizer): void
    {
        $organizer = new CrossReferencePullRequestOrganizer(
            $this->project_manager,
            new PullRequestRetriever($this->pull_request_dao),
            $this->permission_checker,
            $this->git_repository_factory,
            new TlpRelativeDatePresenterBuilder(),
            $this->user_manager,
            $this->user_helper,
        );

        $organizer->organizePullRequestReferences($by_nature_organizer);
    }

    public function testItDoesNotOrganizeCrossReferencesItDoesNotKnow(): void
    {
        $user                = UserTestBuilder::buildWithDefaults();
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

        $this->organizePullRequestReferences($by_nature_organizer);
    }

    public function testItRemovesPullRequestCrossReferenceIfPRIsNotFound(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

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

        $by_nature_organizer
            ->expects(self::once())
            ->method('removeUnreadableCrossReference')
            ->with($ref);

        $this->organizePullRequestReferences($by_nature_organizer);
    }

    public function testItRemovesPullRequestCrossReferenceIfPRBelongsToAnInaccessibleProject(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

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

        $pull_request = PullRequestTestBuilder::aPullRequestInReview()->withId(42);

        $this->permission_checker
            ->method('checkPullRequestIsReadableByUser')
            ->with($pull_request, $user)
            ->willThrowException($this->createMock(Project_AccessException::class));

        $by_nature_organizer
            ->expects(self::once())
            ->method('removeUnreadableCrossReference')
            ->with($ref);

        $this->organizePullRequestReferences($by_nature_organizer);
    }

    public function testItRemovesPullRequestCrossReferenceIfPRBelongsToARepositoryTheUserCannotAccess(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

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

        $pull_request = PullRequestTestBuilder::aPullRequestInReview()->withId(42);

        $this->permission_checker
            ->method('checkPullRequestIsReadableByUser')
            ->with($pull_request, $user)
            ->willThrowException(new UserCannotReadGitRepositoryException());

        $by_nature_organizer
            ->expects(self::once())
            ->method('removeUnreadableCrossReference')
            ->with($ref);

        $this->organizePullRequestReferences($by_nature_organizer);
    }

    public function testItRemovesPullRequestCrossReferenceIfPRBelongsToARepositoryWeCannotInstantiate(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

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

        $pull_request = PullRequestTestBuilder::aPullRequestInReview()->withId(101);

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

        $this->organizePullRequestReferences($by_nature_organizer);
    }

    /**
     * @dataProvider getPullRequest
     */
    public function testItMovesCrossReferenceToRepositorySection(PullRequest $pull_request_with_status, string $expected_status_label): void
    {
        $user = UserTestBuilder::aUser()->withId(105)->withLocale('en_US')->build();

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

        $this->pull_request_dao = SearchPullRequestStub::withAtLeastOnePullRequest($pull_request_with_status);

        $this->permission_checker
            ->method('checkPullRequestIsReadableByUser')
            ->with($pull_request_with_status, $user);

        $gir_repository = $this->createMock(GitRepository::class);
        $gir_repository->method('getName')->willReturn('barry/ginger');

        $this->git_repository_factory
            ->method('getRepositoryById')
            ->with(101)
            ->willReturn($gir_repository);

        $this->project_manager
            ->method('getProject')
            ->willReturn(ProjectTestBuilder::aProject()->withUnixName('peculiar')->build());

        $user_1001 = UserTestBuilder::aUser()->withId(1001)->withAvatarUrl("https://example.com")->build();

        $this->user_manager = RetrieveUserByIdStub::withUser($user_1001);

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

        $this->organizePullRequestReferences($by_nature_organizer);
    }

    public function getPullRequest(): iterable
    {
        yield 'With an abandoned pull request' => [PullRequestTestBuilder::anAbandonedPullRequest()->withRepositoryId(101)->withTitle('Lorem ipsum doloret')->createdAt(1234567890)->createdBy(1001)->build(), "Abandonned"];
        yield 'With a merged pull request' => [PullRequestTestBuilder::aMergedPullRequest()->withRepositoryId(101)->withTitle('Lorem ipsum doloret')->createdAt(1234567890)->createdBy(1001)->build(), "Merged"];
        yield 'With a pull request in review ' => [PullRequestTestBuilder::aPullRequestInReview()->withRepositoryId(101)->withTitle('Lorem ipsum doloret')->createdAt(1234567890)->createdBy(1001)->build(), "Review"];
    }
}
