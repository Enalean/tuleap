<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\REST\v1;

use DateTimeImmutable;
use Exception;
use Luracast\Restler\RestException;
use PFUser;
use Tracker_ArtifactFactory;
use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabProject;
use Tuleap\Gitlab\API\GitlabProjectBuilder;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\Artifact\MergeRequestTitleCreatorFromArtifact;
use Tuleap\Gitlab\Plugin\GitlabIntegrationAvailabilityChecker;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationFactory;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitlabMergeRequestCreatorTest extends TestCase
{
    private GitlabMergeRequestCreator $creator;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitlabIntegrationAvailabilityChecker
     */
    private $availability_checker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitlabRepositoryIntegrationFactory
     */
    private $integration_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&CredentialsRetriever
     */
    private $credentials_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ClientWrapper
     */
    private $gitlab_api_client;
    /**
     * @var GitlabProjectBuilder&\PHPUnit\Framework\MockObject\MockObject
     */
    private $gitlab_project_builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artifact_factory       = $this->createMock(Tracker_ArtifactFactory::class);
        $this->availability_checker   = $this->createMock(GitlabIntegrationAvailabilityChecker::class);
        $this->integration_factory    = $this->createMock(GitlabRepositoryIntegrationFactory::class);
        $this->credentials_retriever  = $this->createMock(CredentialsRetriever::class);
        $this->gitlab_api_client      = $this->createMock(ClientWrapper::class);
        $this->gitlab_project_builder = $this->createMock(GitlabProjectBuilder::class);

        $this->creator = new GitlabMergeRequestCreator(
            $this->artifact_factory,
            $this->availability_checker,
            $this->integration_factory,
            $this->credentials_retriever,
            $this->gitlab_project_builder,
            $this->gitlab_api_client,
            new MergeRequestTitleCreatorFromArtifact(),
        );
    }

    public function testItAsksToCreateTheMergeRequest(): void
    {
        $user = $this->buildMockUser();

        $this->artifact_factory
            ->expects($this->once())
            ->method('getArtifactByIdUserCanView')
            ->with($user, 123)
            ->willReturn(
                $this->buildMockArtifact()
            );

        $this->availability_checker
            ->expects($this->once())
            ->method('isGitlabIntegrationAvailableForProject')
            ->willReturn(true);

        $integration = $this->buildMockIntegration();

        $credentials = $this->createMock(Credentials::class);
        $this->credentials_retriever
            ->expects($this->once())
            ->method('getCredentials')
            ->with($integration)
            ->willReturn($credentials);

        $this->gitlab_project_builder
            ->expects($this->once())
            ->method('getProjectFromGitlabAPI')
            ->willReturn(
                new GitlabProject(
                    18,
                    'desc',
                    'web_url',
                    'path_with_namespace',
                    new DateTimeImmutable(),
                    'default_branch_name'
                )
            );

        $this->gitlab_api_client
            ->expects($this->once())
            ->method('postUrl')
            ->with(
                $credentials,
                '/projects/23/merge_requests?id=1&source_branch=TULEAP-123_main&target_branch=default_branch_name&title=Draft%3A+TULEAP-123+art+title',
                []
            );

        $this->creator->createMergeRequestInGitlab(
            $user,
            $this->buildGitlabMergeRequestPOSTRepresentation()
        );
    }

    public function testItThrowAnExceptionIfArtifactDoesNotExistOrUserCannotReadIt(): void
    {
        $user = UserTestBuilder::anActiveUser()->build();

        $this->artifact_factory
            ->expects($this->once())
            ->method('getArtifactByIdUserCanView')
            ->with($user, 123)
            ->willReturn(null);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->creator->createMergeRequestInGitlab(
            $user,
            $this->buildGitlabMergeRequestPOSTRepresentation()
        );
    }

    public function testItThrowAnExceptionIfGitlabIntegrationIsNotAvailableInProject(): void
    {
        $user = UserTestBuilder::anActiveUser()->build();

        $this->artifact_factory
            ->expects($this->once())
            ->method('getArtifactByIdUserCanView')
            ->with($user, 123)
            ->willReturn(
                $this->buildMockArtifact()
            );

        $this->availability_checker
            ->expects($this->once())
            ->method('isGitlabIntegrationAvailableForProject')
            ->willReturn(false);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->creator->createMergeRequestInGitlab(
            $user,
            $this->buildGitlabMergeRequestPOSTRepresentation()
        );
    }

    public function testItThrowAnExceptionIfUserIsNotProjectMember(): void
    {
        $user = UserTestBuilder::anActiveUser()->withoutMemberOfProjects()->build();

        $this->artifact_factory
            ->expects($this->once())
            ->method('getArtifactByIdUserCanView')
            ->with($user, 123)
            ->willReturn(
                $this->buildMockArtifact()
            );

        $this->availability_checker
            ->expects($this->once())
            ->method('isGitlabIntegrationAvailableForProject')
            ->willReturn(true);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->creator->createMergeRequestInGitlab(
            $user,
            $this->buildGitlabMergeRequestPOSTRepresentation()
        );
    }

    public function testItThrowAnExceptionIfGitlabIntegrationIsNotFound(): void
    {
        $user = $this->buildMockUser();

        $this->artifact_factory
            ->expects($this->once())
            ->method('getArtifactByIdUserCanView')
            ->with($user, 123)
            ->willReturn(
                $this->buildMockArtifact()
            );

        $this->availability_checker
            ->expects($this->once())
            ->method('isGitlabIntegrationAvailableForProject')
            ->willReturn(true);

        $this->integration_factory
            ->expects($this->once())
            ->method('getIntegrationById')
            ->with(1)
            ->willReturn(null);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->creator->createMergeRequestInGitlab(
            $user,
            $this->buildGitlabMergeRequestPOSTRepresentation()
        );
    }

    public function testItThrowAnExceptionIfGitlabIntegrationAndProjectAreNotInTheSameProject(): void
    {
        $user = $this->buildMockUser();

        $this->artifact_factory
            ->expects($this->once())
            ->method('getArtifactByIdUserCanView')
            ->with($user, 123)
            ->willReturn(
                $this->buildMockArtifact()
            );

        $this->availability_checker
            ->expects($this->once())
            ->method('isGitlabIntegrationAvailableForProject')
            ->willReturn(true);

        $this->mockIntegrationWithAnotherProject();

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->creator->createMergeRequestInGitlab(
            $user,
            $this->buildGitlabMergeRequestPOSTRepresentation()
        );
    }

    public function testItThrowAnExceptionIfGitlabIntegrationDoesNotHaveCredentials(): void
    {
        $user = $this->buildMockUser();

        $this->artifact_factory
            ->expects($this->once())
            ->method('getArtifactByIdUserCanView')
            ->with($user, 123)
            ->willReturn(
                $this->buildMockArtifact()
            );

        $this->availability_checker
            ->expects($this->once())
            ->method('isGitlabIntegrationAvailableForProject')
            ->willReturn(true);

        $integration = $this->buildMockIntegration();
        $this->credentials_retriever
            ->expects($this->once())
            ->method('getCredentials')
            ->with($integration)
            ->willReturn(null);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->creator->createMergeRequestInGitlab(
            $user,
            $this->buildGitlabMergeRequestPOSTRepresentation()
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideGitLabAPIToRetrieveGitlabProjectExceptions')]
    public function testItThrowAnExceptionIfGitlabAPIToRetrieveGitlabProjectHasError(Exception $exception): void
    {
        $user = $this->buildMockUser();

        $this->artifact_factory
            ->expects($this->once())
            ->method('getArtifactByIdUserCanView')
            ->with($user, 123)
            ->willReturn(
                $this->buildMockArtifact()
            );

        $this->availability_checker
            ->expects($this->once())
            ->method('isGitlabIntegrationAvailableForProject')
            ->willReturn(true);

        $integration = $this->buildMockIntegration();

        $credentials = $this->createMock(Credentials::class);
        $this->credentials_retriever
            ->expects($this->once())
            ->method('getCredentials')
            ->with($integration)
            ->willReturn($credentials);

        $this->gitlab_project_builder
            ->expects($this->once())
            ->method('getProjectFromGitlabAPI')
            ->willThrowException($exception);

        $this->gitlab_api_client
            ->expects(self::never())
            ->method('postUrl');

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->creator->createMergeRequestInGitlab(
            $user,
            $this->buildGitlabMergeRequestPOSTRepresentation()
        );
    }

    public static function provideGitLabAPIToRetrieveGitlabProjectExceptions(): array
    {
        return [
            [
                new GitlabRequestException(
                    500,
                    'Error'
                ),
            ],
            [
                new GitlabRequestException(
                    400,
                    '403 Forbidden (Forbidden)'
                ),
            ],
            [
                new GitlabResponseAPIException('Error'),
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideGitLabAPIExceptions')]
    public function testItThrowAnExceptionIfGitlabCreateMergeRequestAPIHasError(Exception $exception): void
    {
        $user = $this->buildMockUser();

        $this->artifact_factory
            ->expects($this->once())
            ->method('getArtifactByIdUserCanView')
            ->with($user, 123)
            ->willReturn(
                $this->buildMockArtifact()
            );

        $this->availability_checker
            ->expects($this->once())
            ->method('isGitlabIntegrationAvailableForProject')
            ->willReturn(true);

        $integration = $this->buildMockIntegration();

        $credentials = $this->createMock(Credentials::class);
        $this->credentials_retriever
            ->expects($this->once())
            ->method('getCredentials')
            ->with($integration)
            ->willReturn($credentials);

        $this->gitlab_project_builder
            ->expects($this->once())
            ->method('getProjectFromGitlabAPI')
            ->willReturn(
                $this->buildGitlabProject()
            );

        $this->gitlab_api_client
            ->expects($this->once())
            ->method('postUrl')
            ->with(
                $credentials,
                '/projects/23/merge_requests?id=1&source_branch=TULEAP-123_main&target_branch=default_branch_name&title=Draft%3A+TULEAP-123+art+title',
                []
            )
            ->willThrowException($exception);

        $this->expectException(RestException::class);

        if ($exception instanceof GitlabRequestException) {
            $this->expectExceptionCode(400);
        } elseif ($exception instanceof GitlabResponseAPIException) {
            $this->expectExceptionCode(500);
        }

        $this->creator->createMergeRequestInGitlab(
            $user,
            $this->buildGitlabMergeRequestPOSTRepresentation()
        );
    }

    public static function provideGitLabAPIExceptions(): array
    {
        return [
            [
                new GitlabRequestException(
                    400,
                    'Bad request'
                ),
            ],
            [
                new GitlabResponseAPIException('Bad request'),
            ],
        ];
    }

    private function buildGitlabMergeRequestPOSTRepresentation(): GitlabMergeRequestPOSTRepresentation
    {
        return GitlabMergeRequestPOSTRepresentation::build(
            1,
            123,
            'TULEAP-123_main'
        );
    }

    private function buildMockArtifact(): Artifact
    {
        return ArtifactTestBuilder::anArtifact(123)
            ->inProject(ProjectTestBuilder::aProject()->withId(101)->build())
            ->withTitle('art title')
            ->build();
    }

    private function buildMockUser(): PFUser
    {
        return UserTestBuilder::anActiveUser()->withMemberOf(ProjectTestBuilder::aProject()->build())->build();
    }

    private function mockIntegrationWithAnotherProject(): void
    {
        $project_integration = ProjectTestBuilder::aProject()->withId(102)->build();

        $integration = new GitlabRepositoryIntegration(
            1,
            23,
            'root/project01',
            '',
            'https://example.com',
            new DateTimeImmutable(),
            $project_integration,
            false
        );

        $this->integration_factory
            ->expects($this->once())
            ->method('getIntegrationById')
            ->with(1)
            ->willReturn($integration);
    }

    private function buildMockIntegration(): GitlabRepositoryIntegration
    {
        $integration = new GitlabRepositoryIntegration(
            1,
            23,
            'root/project01',
            '',
            'https://example.com',
            new DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $this->integration_factory
            ->expects($this->once())
            ->method('getIntegrationById')
            ->with(1)
            ->willReturn($integration);

        return $integration;
    }

    private function buildGitlabProject(): GitlabProject
    {
        return new GitlabProject(
            18,
            'desc',
            'web_url',
            'path_with_namespace',
            new DateTimeImmutable(),
            'default_branch_name'
        );
    }
}
