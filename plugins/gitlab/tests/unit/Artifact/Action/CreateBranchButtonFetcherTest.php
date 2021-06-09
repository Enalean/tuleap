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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Artifact\Action;

use ForgeConfig;
use PFUser;
use Project;
use Tracker;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Gitlab\Plugin\GitlabIntegrationAvailabilityChecker;
use Tuleap\Gitlab\Repository\Webhook\WebhookDao;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Test\Builders\IncludeAssetsBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;

final class CreateBranchButtonFetcherTest extends TestCase
{
    use ForgeConfigSandbox;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitlabIntegrationAvailabilityChecker
     */
    private $availability_checker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&WebhookDao
     */
    private $webhook_dao;

    private CreateBranchButtonFetcher $fetcher;
    private JavascriptAsset $javascript_asset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->availability_checker = $this->createMock(GitlabIntegrationAvailabilityChecker::class);
        $this->webhook_dao          = $this->createMock(WebhookDao::class);
        $this->javascript_asset     = new JavascriptAsset(IncludeAssetsBuilder::build(), 'action.js');

        $this->fetcher = new CreateBranchButtonFetcher(
            $this->availability_checker,
            $this->webhook_dao,
            $this->javascript_asset
        );
    }

    public function testItReturnsTheActionLinkButton(): void
    {
        $this->mockFeatureFlagEnabled();

        $user     = $this->createMock(PFUser::class);
        $project  = Project::buildForTest();
        $artifact = $this->createMock(Artifact::class);
        $tracker  = $this->createMock(Tracker::class);

        $tracker
            ->expects(self::once())
            ->method('getProject')
            ->willReturn($project);

        $artifact
            ->expects(self::once())
            ->method('getTracker')
            ->willReturn($tracker);

        $this->availability_checker
            ->expects(self::once())
            ->method('isGitlabIntegrationAvailableForProject')
            ->with($project)
            ->willReturn(true);

        $user
            ->expects(self::once())
            ->method('isMember')
            ->with(101)
            ->willReturn(true);

        $artifact
            ->expects(self::once())
            ->method('userCanView')
            ->with($user)
            ->willReturn(true);

        $this->webhook_dao
            ->expects(self::once())
            ->method('projectHasIntegrationsWithSecretConfigured')
            ->with(101)
            ->willReturn(true);

        $button_action = $this->fetcher->getActionButton($artifact, $user);

        self::assertNotNull($button_action);
        self::assertSame('Create GitLab branch', $button_action->getLinkPresenter()->link_label);
        self::assertSame('action.js', $button_action->getAssetLink());
    }

    public function testItReturnsNullIfFeatureFlagIsNotSet(): void
    {
        $artifact = $this->createMock(Artifact::class);
        $user     = $this->createMock(PFUser::class);

        ForgeConfig::set(
            ForgeConfig::FEATURE_FLAG_PREFIX . CreateBranchButtonFetcher::FEATURE_FLAG_KEY,
            false
        );

        self::assertNull(
            $this->fetcher->getActionButton($artifact, $user)
        );
    }

    public function testItReturnsNullIfProjectCannotUseGitlabIntegration(): void
    {
        $this->mockFeatureFlagEnabled();

        $artifact = $this->createMock(Artifact::class);
        $user     = $this->createMock(PFUser::class);
        $project  = Project::buildForTest();
        $tracker  = $this->createMock(Tracker::class);

        $tracker
            ->expects(self::once())
            ->method('getProject')
            ->willReturn($project);

        $artifact
            ->expects(self::once())
            ->method('getTracker')
            ->willReturn($tracker);

        $this->availability_checker
            ->expects(self::once())
            ->method('isGitlabIntegrationAvailableForProject')
            ->with($project)
            ->willReturn(false);

        self::assertNull(
            $this->fetcher->getActionButton($artifact, $user)
        );
    }

    public function testItReturnsNullIfUserIsNotProjectMember(): void
    {
        $this->mockFeatureFlagEnabled();

        $user     = $this->createMock(PFUser::class);
        $project  = Project::buildForTest();
        $artifact = $this->createMock(Artifact::class);
        $tracker  = $this->createMock(Tracker::class);

        $tracker
            ->expects(self::once())
            ->method('getProject')
            ->willReturn($project);

        $artifact
            ->expects(self::once())
            ->method('getTracker')
            ->willReturn($tracker);

        $this->availability_checker
            ->expects(self::once())
            ->method('isGitlabIntegrationAvailableForProject')
            ->with($project)
            ->willReturn(true);

        $user
            ->expects(self::once())
            ->method('isMember')
            ->with(101)
            ->willReturn(false);

        self::assertNull(
            $this->fetcher->getActionButton($artifact, $user)
        );
    }

    public function testItReturnsNullIfUserCannotSeeArtifact(): void
    {
        $this->mockFeatureFlagEnabled();

        $user     = $this->createMock(PFUser::class);
        $project  = Project::buildForTest();
        $artifact = $this->createMock(Artifact::class);
        $tracker  = $this->createMock(Tracker::class);

        $tracker
            ->expects(self::once())
            ->method('getProject')
            ->willReturn($project);

        $artifact
            ->expects(self::once())
            ->method('getTracker')
            ->willReturn($tracker);

        $this->availability_checker
            ->expects(self::once())
            ->method('isGitlabIntegrationAvailableForProject')
            ->with($project)
            ->willReturn(true);

        $user
            ->expects(self::once())
            ->method('isMember')
            ->with(101)
            ->willReturn(true);

        $artifact
            ->expects(self::once())
            ->method('userCanView')
            ->with($user)
            ->willReturn(false);

        self::assertNull(
            $this->fetcher->getActionButton($artifact, $user)
        );
    }

    public function testItReturnsNullIfProjectDoesNotHaveIntegrationWithSecretConfigured(): void
    {
        $this->mockFeatureFlagEnabled();

        $user     = $this->createMock(PFUser::class);
        $project  = Project::buildForTest();
        $artifact = $this->createMock(Artifact::class);
        $tracker  = $this->createMock(Tracker::class);

        $tracker
            ->expects(self::once())
            ->method('getProject')
            ->willReturn($project);

        $artifact
            ->expects(self::once())
            ->method('getTracker')
            ->willReturn($tracker);

        $this->availability_checker
            ->expects(self::once())
            ->method('isGitlabIntegrationAvailableForProject')
            ->with($project)
            ->willReturn(true);

        $user
            ->expects(self::once())
            ->method('isMember')
            ->with(101)
            ->willReturn(true);

        $artifact
            ->expects(self::once())
            ->method('userCanView')
            ->with($user)
            ->willReturn(true);

        $this->webhook_dao
            ->expects(self::once())
            ->method('projectHasIntegrationsWithSecretConfigured')
            ->with(101)
            ->willReturn(false);

        self::assertNull(
            $this->fetcher->getActionButton($artifact, $user)
        );
    }

    private function mockFeatureFlagEnabled(): void
    {
        ForgeConfig::set(
            ForgeConfig::FEATURE_FLAG_PREFIX . CreateBranchButtonFetcher::FEATURE_FLAG_KEY,
            true
        );
    }
}
