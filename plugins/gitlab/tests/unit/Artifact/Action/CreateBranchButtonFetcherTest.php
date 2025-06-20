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

use Cocur\Slugify\Slugify;
use PFUser;
use Tuleap\Gitlab\Artifact\BranchNameCreatorFromArtifact;
use Tuleap\Gitlab\Plugin\GitlabIntegrationAvailabilityChecker;
use Tuleap\Gitlab\REST\v1\GitlabRepositoryRepresentation;
use Tuleap\Gitlab\REST\v1\GitlabRepositoryRepresentationFactory;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Test\Builders\IncludeAssetsBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CreateBranchButtonFetcherTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitlabIntegrationAvailabilityChecker
     */
    private $availability_checker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitlabRepositoryRepresentationFactory
     */
    private $representation_factory;

    private CreateBranchButtonFetcher $fetcher;
    private JavascriptAsset $javascript_asset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->availability_checker   = $this->createMock(GitlabIntegrationAvailabilityChecker::class);
        $this->representation_factory = $this->createMock(GitlabRepositoryRepresentationFactory::class);
        $this->javascript_asset       = new JavascriptAsset(IncludeAssetsBuilder::build(), 'action.js');

        $this->fetcher = new CreateBranchButtonFetcher(
            $this->availability_checker,
            $this->representation_factory,
            new BranchNameCreatorFromArtifact(
                new Slugify(),
                $this->createMock(CreateBranchPrefixDao::class)
            ),
            $this->javascript_asset
        );
    }

    public function testItReturnsTheActionLinkButton(): void
    {
        $user     = $this->createMock(PFUser::class);
        $project  = ProjectTestBuilder::aProject()->build();
        $artifact = $this->createMock(Artifact::class);
        $tracker  = $this->createMock(Tracker::class);

        $tracker
            ->expects($this->once())
            ->method('getProject')
            ->willReturn($project);

        $artifact
            ->expects($this->once())
            ->method('getTracker')
            ->willReturn($tracker);

        $this->availability_checker
            ->expects($this->once())
            ->method('isGitlabIntegrationAvailableForProject')
            ->with($project)
            ->willReturn(true);

        $user
            ->expects($this->once())
            ->method('isMember')
            ->with(101)
            ->willReturn(true);

        $artifact
            ->expects($this->once())
            ->method('userCanView')
            ->with($user)
            ->willReturn(true);

        $artifact
            ->method('getId')
            ->willReturn('89');

        $artifact
            ->expects($this->once())
            ->method('getTitle')
            ->willReturn('This \is a :feature');

        $this->representation_factory
            ->expects($this->once())
            ->method('getAllIntegrationsRepresentationsInProjectWithConfiguredToken')
            ->with($project)
            ->willReturn([
                new GitlabRepositoryRepresentation(
                    1,
                    1,
                    'root/repo01',
                    '',
                    'https://example.com',
                    1236647,
                    $project,
                    false,
                    true,
                    'dev-'
                ),
            ]);

        $button_action = $this->fetcher->getActionButton($artifact, $user);

        self::assertNotNull($button_action);
        self::assertSame('Create GitLab branch and merge request', $button_action->getLinkPresenter()->link_label);
        self::assertNotNull($button_action->getLinkPresenter()->data);
        self::assertCount(3, $button_action->getLinkPresenter()->data);
        self::assertSame(
            [
                'name' => 'artifact-id',
                'value' => '89',
            ],
            $button_action->getLinkPresenter()->data[1]
        );
        self::assertSame(
            [
                'name' => 'branch-name',
                'value' => 'tuleap-89-this-is-a-feature',
            ],
            $button_action->getLinkPresenter()->data[2]
        );
        self::assertSame($this->javascript_asset, $button_action->asset);
    }

    public function testItReturnsTheActionLinkButtonWithoutArtifactTitle(): void
    {
        $user     = $this->createMock(PFUser::class);
        $project  = ProjectTestBuilder::aProject()->build();
        $artifact = $this->createMock(Artifact::class);
        $tracker  = $this->createMock(Tracker::class);

        $tracker
            ->expects($this->once())
            ->method('getProject')
            ->willReturn($project);

        $artifact
            ->expects($this->once())
            ->method('getTracker')
            ->willReturn($tracker);

        $this->availability_checker
            ->expects($this->once())
            ->method('isGitlabIntegrationAvailableForProject')
            ->with($project)
            ->willReturn(true);

        $user
            ->expects($this->once())
            ->method('isMember')
            ->with(101)
            ->willReturn(true);

        $artifact
            ->expects($this->once())
            ->method('userCanView')
            ->with($user)
            ->willReturn(true);

        $artifact
            ->expects($this->exactly(2))
            ->method('getId')
            ->willReturn('89');

        $artifact
            ->expects($this->once())
            ->method('getTitle')
            ->willReturn(null);

        $this->representation_factory
            ->expects($this->once())
            ->method('getAllIntegrationsRepresentationsInProjectWithConfiguredToken')
            ->with($project)
            ->willReturn([
                new GitlabRepositoryRepresentation(
                    1,
                    1,
                    'root/repo01',
                    '',
                    'https://example.com',
                    1236647,
                    $project,
                    false,
                    true,
                    'dev-'
                ),
            ]);

        $button_action = $this->fetcher->getActionButton($artifact, $user);

        self::assertNotNull($button_action);
        self::assertSame('Create GitLab branch and merge request', $button_action->getLinkPresenter()->link_label);
        self::assertNotNull($button_action->getLinkPresenter()->data);
        self::assertCount(3, $button_action->getLinkPresenter()->data);
        self::assertSame(
            [
                'name' => 'artifact-id',
                'value' => '89',
            ],
            $button_action->getLinkPresenter()->data[1]
        );
        self::assertSame(
            [
                'name' => 'branch-name',
                'value' => 'tuleap-89',
            ],
            $button_action->getLinkPresenter()->data[2]
        );
        self::assertSame($this->javascript_asset, $button_action->asset);
    }

    public function testItReturnsNullIfProjectCannotUseGitlabIntegration(): void
    {
        $artifact = $this->createMock(Artifact::class);
        $user     = $this->createMock(PFUser::class);
        $project  = ProjectTestBuilder::aProject()->build();
        $tracker  = $this->createMock(Tracker::class);

        $tracker
            ->expects($this->once())
            ->method('getProject')
            ->willReturn($project);

        $artifact
            ->expects($this->once())
            ->method('getTracker')
            ->willReturn($tracker);

        $this->availability_checker
            ->expects($this->once())
            ->method('isGitlabIntegrationAvailableForProject')
            ->with($project)
            ->willReturn(false);

        self::assertNull(
            $this->fetcher->getActionButton($artifact, $user)
        );
    }

    public function testItReturnsNullIfUserIsNotProjectMember(): void
    {
        $user     = $this->createMock(PFUser::class);
        $project  = ProjectTestBuilder::aProject()->build();
        $artifact = $this->createMock(Artifact::class);
        $tracker  = $this->createMock(Tracker::class);

        $tracker
            ->expects($this->once())
            ->method('getProject')
            ->willReturn($project);

        $artifact
            ->expects($this->once())
            ->method('getTracker')
            ->willReturn($tracker);

        $this->availability_checker
            ->expects($this->once())
            ->method('isGitlabIntegrationAvailableForProject')
            ->with($project)
            ->willReturn(true);

        $user
            ->expects($this->once())
            ->method('isMember')
            ->with(101)
            ->willReturn(false);

        self::assertNull(
            $this->fetcher->getActionButton($artifact, $user)
        );
    }

    public function testItReturnsNullIfUserCannotSeeArtifact(): void
    {
        $user     = $this->createMock(PFUser::class);
        $project  = ProjectTestBuilder::aProject()->build();
        $artifact = $this->createMock(Artifact::class);
        $tracker  = $this->createMock(Tracker::class);

        $tracker
            ->expects($this->once())
            ->method('getProject')
            ->willReturn($project);

        $artifact
            ->expects($this->once())
            ->method('getTracker')
            ->willReturn($tracker);

        $this->availability_checker
            ->expects($this->once())
            ->method('isGitlabIntegrationAvailableForProject')
            ->with($project)
            ->willReturn(true);

        $user
            ->expects($this->once())
            ->method('isMember')
            ->with(101)
            ->willReturn(true);

        $artifact
            ->expects($this->once())
            ->method('userCanView')
            ->with($user)
            ->willReturn(false);

        self::assertNull(
            $this->fetcher->getActionButton($artifact, $user)
        );
    }

    public function testItReturnsNullIfProjectDoesNotHaveIntegrationWithSecretConfigured(): void
    {
        $user     = $this->createMock(PFUser::class);
        $project  = ProjectTestBuilder::aProject()->build();
        $artifact = $this->createMock(Artifact::class);
        $tracker  = $this->createMock(Tracker::class);

        $tracker
            ->expects($this->once())
            ->method('getProject')
            ->willReturn($project);

        $artifact
            ->expects($this->once())
            ->method('getTracker')
            ->willReturn($tracker);

        $this->availability_checker
            ->expects($this->once())
            ->method('isGitlabIntegrationAvailableForProject')
            ->with($project)
            ->willReturn(true);

        $user
            ->expects($this->once())
            ->method('isMember')
            ->with(101)
            ->willReturn(true);

        $artifact
            ->expects($this->once())
            ->method('userCanView')
            ->with($user)
            ->willReturn(true);

        $this->representation_factory
            ->expects($this->once())
            ->method('getAllIntegrationsRepresentationsInProjectWithConfiguredToken')
            ->with($project)
            ->willReturn([]);

        self::assertNull(
            $this->fetcher->getActionButton($artifact, $user)
        );
    }
}
