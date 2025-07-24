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

use GitPermissionsManager;
use GitUserNotAdminException;
use Tuleap\Git\Branch\InvalidBranchNameException;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationFactory;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationNotFoundException;
use Tuleap\Gitlab\Test\Builder\RepositoryIntegrationBuilder;
use Tuleap\Gitlab\Test\Stubs\SaveIntegrationBranchPrefixStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CreateBranchPrefixUpdaterTest extends TestCase
{
    private const INTEGRATION_ID = 18;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitlabRepositoryIntegrationFactory
     */
    private $integration_factory;
    private SaveIntegrationBranchPrefixStub $branch_prefix_saver;
    private string $branch_prefix;
    /**
     * @var GitPermissionsManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $git_permissions_manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->integration_factory     = $this->createMock(GitlabRepositoryIntegrationFactory::class);
        $this->git_permissions_manager = $this->createMock(GitPermissionsManager::class);
        $this->branch_prefix_saver     = SaveIntegrationBranchPrefixStub::withCallCount();

        $this->branch_prefix = 'dev-';
    }

    private function updateBranchPrefix(): void
    {
        $updater = new CreateBranchPrefixUpdater(
            $this->integration_factory,
            $this->git_permissions_manager,
            $this->branch_prefix_saver,
        );

        $updater->updateBranchPrefix(
            UserTestBuilder::anActiveUser()->build(),
            self::INTEGRATION_ID,
            $this->branch_prefix,
        );
    }

    public function testItStoresTheBranchPrefix(): void
    {
        $this->integration_factory
            ->expects($this->once())
            ->method('getIntegrationById')
            ->with(self::INTEGRATION_ID)
            ->willReturn(RepositoryIntegrationBuilder::aGitlabRepositoryIntegration(self::INTEGRATION_ID)->build());

        $this->git_permissions_manager
            ->expects($this->once())
            ->method('userIsGitAdmin')
            ->willReturn(true);

        $this->updateBranchPrefix();

        self::assertSame(1, $this->branch_prefix_saver->getCallCount());
    }

    public function testItStoresTheBranchPrefixWithSomeSpecialChars(): void
    {
        $this->integration_factory
            ->expects($this->once())
            ->method('getIntegrationById')
            ->with(self::INTEGRATION_ID)
            ->willReturn(RepositoryIntegrationBuilder::aGitlabRepositoryIntegration(self::INTEGRATION_ID)->build());

        $this->git_permissions_manager
            ->expects($this->once())
            ->method('userIsGitAdmin')
            ->willReturn(true);

        $this->branch_prefix = 'dev/';
        $this->updateBranchPrefix();

        self::assertSame(1, $this->branch_prefix_saver->getCallCount());
    }

    public function testItThrowsAnExceptionIfIntegrationNotFoundTheBranchPrefix(): void
    {
        $this->integration_factory
            ->expects($this->once())
            ->method('getIntegrationById')
            ->with(self::INTEGRATION_ID)
            ->willReturn(null);

        $this->expectException(GitlabRepositoryIntegrationNotFoundException::class);
        $this->updateBranchPrefix();

        self::assertSame(0, $this->branch_prefix_saver->getCallCount());
    }

    public function testItThrowsAnExceptionIfUserIsNotGitAdministrator(): void
    {
        $this->integration_factory
            ->expects($this->once())
            ->method('getIntegrationById')
            ->with(self::INTEGRATION_ID)
            ->willReturn(RepositoryIntegrationBuilder::aGitlabRepositoryIntegration(self::INTEGRATION_ID)->build());

        $this->git_permissions_manager
            ->expects($this->once())
            ->method('userIsGitAdmin')
            ->willReturn(false);

        $this->expectException(GitUserNotAdminException::class);
        $this->updateBranchPrefix();

        self::assertSame(0, $this->branch_prefix_saver->getCallCount());
    }

    public function testItThrowsAnExceptionIfBranchPrefixIsNotValid(): void
    {
        $this->integration_factory
            ->expects($this->once())
            ->method('getIntegrationById')
            ->with(self::INTEGRATION_ID)
            ->willReturn(RepositoryIntegrationBuilder::aGitlabRepositoryIntegration(self::INTEGRATION_ID)->build());

        $this->git_permissions_manager
            ->expects($this->once())
            ->method('userIsGitAdmin')
            ->willReturn(true);

        $this->branch_prefix = 'dev:';

        $this->expectException(InvalidBranchNameException::class);
        $this->updateBranchPrefix();

        self::assertSame(0, $this->branch_prefix_saver->getCallCount());
    }
}
