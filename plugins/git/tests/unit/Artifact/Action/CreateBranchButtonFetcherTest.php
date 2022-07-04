<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Git\Artifact\Action;

use Cocur\Slugify\Slugify;
use ForgeConfig;
use GitRepository;
use GitRepositoryFactory;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Git\REST\v1\Branch\BranchNameCreatorFromArtifact;
use Tuleap\Layout\IncludeAssetsGeneric;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalButtonAction;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

final class CreateBranchButtonFetcherTest extends TestCase
{
    use ForgeConfigSandbox;

    private CreateBranchButtonFetcher $create_button_fetcher;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitRepositoryFactory
     */
    private $git_repository_factory;
    /**
     * @var IncludeAssetsGeneric&\PHPUnit\Framework\MockObject\MockObject
     */
    private $include_asset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->git_repository_factory = $this->createMock(GitRepositoryFactory::class);
        $this->include_asset          = $this->createMock(IncludeAssetsGeneric::class);
        $this->create_button_fetcher  = new CreateBranchButtonFetcher(
            $this->git_repository_factory,
            new BranchNameCreatorFromArtifact(
                new Slugify()
            ),
            new JavascriptAsset(
                $this->include_asset,
                ""
            )
        );

        $this->include_asset->method("getFileURL")->willReturn("");
    }

    public function testItReturnsNullIfFeatureFlagIsNotSet(): void
    {
        self::assertNull(
            $this->getActionButton()
        );
    }

    public function testItReturnsNullIfFeatureFlagIsFalse(): void
    {
        ForgeConfig::setFeatureFlag(
            CreateBranchButtonFetcher::FEATURE_FLAG_KEY,
            false
        );

        self::assertNull(
            $this->getActionButton()
        );
    }

    public function testItReturnsNullIfThereIsNoRepositoryInProject(): void
    {
        ForgeConfig::setFeatureFlag(
            CreateBranchButtonFetcher::FEATURE_FLAG_KEY,
            true
        );

        $this->git_repository_factory->method("getAllRepositories")->willReturn([]);

        self::assertNull(
            $this->getActionButton()
        );
    }

    public function testItReturnsNullIfThereIsNoReadableRepositoryForUser(): void
    {
        ForgeConfig::setFeatureFlag(
            CreateBranchButtonFetcher::FEATURE_FLAG_KEY,
            true
        );

        $git_repository = $this->createMock(GitRepository::class);
        $git_repository->method("userCanRead")->willReturn(false);
        $this->git_repository_factory->method("getAllRepositories")->willReturn([$git_repository]);

        self::assertNull(
            $this->getActionButton()
        );
    }

    public function testItReturnsPresenterWhenAllPreconditionsAreMet(): void
    {
        ForgeConfig::setFeatureFlag(
            CreateBranchButtonFetcher::FEATURE_FLAG_KEY,
            true
        );

        $git_repository = $this->createMock(GitRepository::class);
        $git_repository->method("userCanRead")->willReturn(true);
        $this->git_repository_factory->method("getAllRepositories")->willReturn([$git_repository]);

        self::assertInstanceOf(
            AdditionalButtonAction::class,
            $this->getActionButton()
        );
    }

    private function getActionButton(): ?AdditionalButtonAction
    {
        $artifact = ArtifactTestBuilder::anArtifact(101)->inProject(ProjectTestBuilder::aProject()->build())->build();

        return $this->create_button_fetcher->getActionButton(
            $artifact,
            UserTestBuilder::anActiveUser()->build(),
        );
    }
}
