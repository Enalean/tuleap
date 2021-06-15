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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\REST\v1;

use DateTimeImmutable;
use Luracast\Restler\RestException;
use PFUser;
use Project;
use Tracker;
use Tracker_ArtifactFactory;
use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\Plugin\GitlabIntegrationAvailabilityChecker;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationFactory;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;

final class GitlabBranchCreatorTest extends TestCase
{
    private GitlabBranchCreator $creator;
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->artifact_factory      = $this->createMock(Tracker_ArtifactFactory::class);
        $this->availability_checker  = $this->createMock(GitlabIntegrationAvailabilityChecker::class);
        $this->integration_factory   = $this->createMock(GitlabRepositoryIntegrationFactory::class);
        $this->credentials_retriever = $this->createMock(CredentialsRetriever::class);
        $this->gitlab_api_client     = $this->createMock(ClientWrapper::class);

        $this->creator = new GitlabBranchCreator(
            $this->artifact_factory,
            $this->availability_checker,
            $this->integration_factory,
            $this->credentials_retriever,
            $this->gitlab_api_client
        );
    }

    public function testItAsksToCreateTheBanch(): void
    {
        $user = $this->buildMockUser();

        $this->artifact_factory
            ->expects(self::once())
            ->method('getArtifactByIdUserCanView')
            ->with($user, 123)
            ->willReturn(
                $this->buildMockArtifact()
            );

        $this->availability_checker
            ->expects(self::once())
            ->method('isGitlabIntegrationAvailableForProject')
            ->willReturn(true);

        $integration = $this->buildMockIntegration();

        $credentials = $this->createMock(Credentials::class);
        $this->credentials_retriever
            ->expects(self::once())
            ->method('getCredentials')
            ->with($integration)
            ->willReturn($credentials);

        $this->gitlab_api_client
            ->expects(self::once())
            ->method('postUrl')
            ->with(
                $credentials,
                "/projects/23/repository/branches?branch=dev_TULEAP-123&ref=main",
                []
            );

        $this->creator->createBranchInGitlab(
            $user,
            $this->buildGitlabBranchPOSTRepresentation()
        );
    }

    public function testItThrowAnExceptionIfArtifactDoesNotExistOrUserCannotReadIt(): void
    {
        $user = UserTestBuilder::anActiveUser()->build();

        $this->artifact_factory
            ->expects(self::once())
            ->method('getArtifactByIdUserCanView')
            ->with($user, 123)
            ->willReturn(null);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->creator->createBranchInGitlab(
            $user,
            $this->buildGitlabBranchPOSTRepresentation()
        );
    }

    public function testItThrowAnExceptionIfGitlabIntegrationIsNotAvailableInProject(): void
    {
        $user = UserTestBuilder::anActiveUser()->build();

        $this->artifact_factory
            ->expects(self::once())
            ->method('getArtifactByIdUserCanView')
            ->with($user, 123)
            ->willReturn(
                $this->buildMockArtifact()
            );

        $this->availability_checker
            ->expects(self::once())
            ->method('isGitlabIntegrationAvailableForProject')
            ->willReturn(false);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->creator->createBranchInGitlab(
            $user,
            $this->buildGitlabBranchPOSTRepresentation()
        );
    }

    public function testItThrowAnExceptionIfUserIsNotProjectMember(): void
    {
        $user = $this->createMock(PFUser::class);

        $this->artifact_factory
            ->expects(self::once())
            ->method('getArtifactByIdUserCanView')
            ->with($user, 123)
            ->willReturn(
                $this->buildMockArtifact()
            );

        $this->availability_checker
            ->expects(self::once())
            ->method('isGitlabIntegrationAvailableForProject')
            ->willReturn(true);

        $user
            ->expects(self::once())
            ->method('isMember')
            ->with(101)
            ->willReturn(false);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->creator->createBranchInGitlab(
            $user,
            $this->buildGitlabBranchPOSTRepresentation()
        );
    }

    public function testItThrowAnExceptionIfGitlabIntegrationIsNotFound(): void
    {
        $user = $this->buildMockUser();

        $this->artifact_factory
            ->expects(self::once())
            ->method('getArtifactByIdUserCanView')
            ->with($user, 123)
            ->willReturn(
                $this->buildMockArtifact()
            );

        $this->availability_checker
            ->expects(self::once())
            ->method('isGitlabIntegrationAvailableForProject')
            ->willReturn(true);

        $this->integration_factory
            ->expects(self::once())
            ->method('getIntegrationById')
            ->with(1)
            ->willReturn(null);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->creator->createBranchInGitlab(
            $user,
            $this->buildGitlabBranchPOSTRepresentation()
        );
    }

    public function testItThrowAnExceptionIfGitlabIntegrationAndProjectAreNotInTheSameProject(): void
    {
        $user = $this->buildMockUser();

        $this->artifact_factory
            ->expects(self::once())
            ->method('getArtifactByIdUserCanView')
            ->with($user, 123)
            ->willReturn(
                $this->buildMockArtifact()
            );

        $this->availability_checker
            ->expects(self::once())
            ->method('isGitlabIntegrationAvailableForProject')
            ->willReturn(true);

        $this->mockIntegrationWithAnotherProject();

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->creator->createBranchInGitlab(
            $user,
            $this->buildGitlabBranchPOSTRepresentation()
        );
    }

    public function testItThrowAnExceptionIfGitlabIntegrationDoesNotHaveCredentials(): void
    {
        $user = $this->buildMockUser();

        $this->artifact_factory
            ->expects(self::once())
            ->method('getArtifactByIdUserCanView')
            ->with($user, 123)
            ->willReturn(
                $this->buildMockArtifact()
            );

        $this->availability_checker
            ->expects(self::once())
            ->method('isGitlabIntegrationAvailableForProject')
            ->willReturn(true);

        $integration = $this->buildMockIntegration();
        $this->credentials_retriever
            ->expects(self::once())
            ->method('getCredentials')
            ->with($integration)
            ->willReturn(null);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->creator->createBranchInGitlab(
            $user,
            $this->buildGitlabBranchPOSTRepresentation()
        );
    }

    public function testItThrowAnExceptionIfGitlabAPIQueryHasError(): void
    {
        $user = $this->buildMockUser();

        $this->artifact_factory
            ->expects(self::once())
            ->method('getArtifactByIdUserCanView')
            ->with($user, 123)
            ->willReturn(
                $this->buildMockArtifact()
            );

        $this->availability_checker
            ->expects(self::once())
            ->method('isGitlabIntegrationAvailableForProject')
            ->willReturn(true);

        $integration = $this->buildMockIntegration();

        $credentials = $this->createMock(Credentials::class);
        $this->credentials_retriever
            ->expects(self::once())
            ->method('getCredentials')
            ->with($integration)
            ->willReturn($credentials);

        $this->gitlab_api_client
            ->expects(self::once())
            ->method('postUrl')
            ->with(
                $credentials,
                "/projects/23/repository/branches?branch=dev_TULEAP-123&ref=main",
                []
            )
            ->willThrowException(
                new GitlabRequestException(
                    400,
                    "Bad request"
                )
            );


        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->creator->createBranchInGitlab(
            $user,
            $this->buildGitlabBranchPOSTRepresentation()
        );
    }

    public function testItThrowAnExceptionIfGitlabAPIResponseIsInError(): void
    {
        $user = $this->buildMockUser();

        $this->artifact_factory
            ->expects(self::once())
            ->method('getArtifactByIdUserCanView')
            ->with($user, 123)
            ->willReturn(
                $this->buildMockArtifact()
            );

        $this->availability_checker
            ->expects(self::once())
            ->method('isGitlabIntegrationAvailableForProject')
            ->willReturn(true);

        $integration = $this->buildMockIntegration();

        $credentials = $this->createMock(Credentials::class);
        $this->credentials_retriever
            ->expects(self::once())
            ->method('getCredentials')
            ->with($integration)
            ->willReturn($credentials);

        $this->gitlab_api_client
            ->expects(self::once())
            ->method('postUrl')
            ->with(
                $credentials,
                "/projects/23/repository/branches?branch=dev_TULEAP-123&ref=main",
                []
            )
            ->willThrowException(
                new GitlabResponseAPIException(
                    "Bad request"
                )
            );


        $this->expectException(RestException::class);
        $this->expectExceptionCode(500);

        $this->creator->createBranchInGitlab(
            $user,
            $this->buildGitlabBranchPOSTRepresentation()
        );
    }

    private function buildGitlabBranchPOSTRepresentation(): GitlabBranchPOSTRepresentation
    {
        return GitlabBranchPOSTRepresentation::build(
            1,
            123,
            "dev_TULEAP-123",
            "main"
        );
    }

    private function buildMockArtifact(): Artifact
    {
        $tracker = $this->createMock(Tracker::class);
        $tracker
            ->method('getProject')
            ->willReturn(Project::buildForTest());
        $tracker
            ->method('getGroupId')
            ->willReturn('101');

        $artifact = $this->createMock(Artifact::class);
        $artifact
            ->method('getTracker')
            ->willReturn($tracker);

        return $artifact;
    }

    private function buildMockUser(): PFUser
    {
        $user = $this->createMock(PFUser::class);

        $user
            ->expects(self::once())
            ->method('isMember')
            ->with(101)
            ->willReturn(true);

        return $user;
    }

    private function mockIntegrationWithAnotherProject(): void
    {
        $project_integration = $this->createMock(Project::class);
        $project_integration
            ->expects(self::once())
            ->method('getID')
            ->willReturn(102);

        $integration = new GitlabRepositoryIntegration(
            1,
            23,
            "root/project01",
            "",
            "https://example.com",
            new DateTimeImmutable(),
            $project_integration,
            false
        );

        $this->integration_factory
            ->expects(self::once())
            ->method('getIntegrationById')
            ->with(1)
            ->willReturn($integration);
    }

    private function buildMockIntegration(): GitlabRepositoryIntegration
    {
        $integration = new GitlabRepositoryIntegration(
            1,
            23,
            "root/project01",
            "",
            "https://example.com",
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $this->integration_factory
            ->expects(self::once())
            ->method('getIntegrationById')
            ->with(1)
            ->willReturn($integration);

        return $integration;
    }
}
