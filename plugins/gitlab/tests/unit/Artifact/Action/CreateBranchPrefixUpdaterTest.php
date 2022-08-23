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

use Tuleap\Git\Branch\InvalidBranchNameException;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationFactory;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationNotFoundException;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class CreateBranchPrefixUpdaterTest extends TestCase
{
    private CreateBranchPrefixUpdater $updater;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitlabRepositoryIntegrationFactory
     */
    private $integration_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&CreateBranchPrefixDao
     */
    private $create_branch_prefix_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->integration_factory      = $this->createMock(GitlabRepositoryIntegrationFactory::class);
        $this->create_branch_prefix_dao = $this->createMock(CreateBranchPrefixDao::class);

        $this->updater = new CreateBranchPrefixUpdater(
            $this->integration_factory,
            $this->create_branch_prefix_dao
        );
    }

    public function testItStoresTheBranchPrefix(): void
    {
        $integration = new GitlabRepositoryIntegration(
            18,
            2,
            'smartoid/browser',
            'Next gen browser',
            'https://example.com/smartoid/browser',
            new \DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $this->integration_factory
            ->expects(self::once())
            ->method('getIntegrationById')
            ->with(18)
            ->willReturn($integration);

        $this->create_branch_prefix_dao
            ->expects(self::once())
            ->method('setCreateBranchPrefixForIntegration');

        $this->updater->updateBranchPrefix(
            18,
            "dev-"
        );
    }

    public function testItStoresTheBranchPrefixWithSomeSpecialChars(): void
    {
        $integration = new GitlabRepositoryIntegration(
            18,
            2,
            'smartoid/browser',
            'Next gen browser',
            'https://example.com/smartoid/browser',
            new \DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $this->integration_factory
            ->expects(self::once())
            ->method('getIntegrationById')
            ->with(18)
            ->willReturn($integration);

        $this->create_branch_prefix_dao
            ->expects(self::once())
            ->method('setCreateBranchPrefixForIntegration');

        $this->updater->updateBranchPrefix(
            18,
            "dev/"
        );
    }

    public function testItThrowsAnExceptionIfIntegrationNotFoundTheBranchPrefix(): void
    {
        $this->integration_factory
            ->expects(self::once())
            ->method('getIntegrationById')
            ->with(18)
            ->willReturn(null);

        $this->create_branch_prefix_dao
            ->expects(self::never())
            ->method('setCreateBranchPrefixForIntegration');

        $this->expectException(GitlabRepositoryIntegrationNotFoundException::class);

        $this->updater->updateBranchPrefix(
            18,
            "dev-"
        );
    }

    public function testItThrowsAnExceptionIfBranchPrefixIsNotValid(): void
    {
        $integration = new GitlabRepositoryIntegration(
            18,
            2,
            'smartoid/browser',
            'Next gen browser',
            'https://example.com/smartoid/browser',
            new \DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $this->integration_factory
            ->expects(self::once())
            ->method('getIntegrationById')
            ->with(18)
            ->willReturn($integration);

        $this->create_branch_prefix_dao
            ->expects(self::never())
            ->method('setCreateBranchPrefixForIntegration');

        $this->expectException(InvalidBranchNameException::class);

        $this->updater->updateBranchPrefix(
            18,
            "dev:"
        );
    }
}
