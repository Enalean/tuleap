<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Reference\Branch;

use DateTimeImmutable;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Branch\BranchInfoDao;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitlabBranchFactoryTest extends TestCase
{
    private GitlabBranchFactory $factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&BranchInfoDao
     */
    private $dao;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(BranchInfoDao::class);

        $this->factory = new GitlabBranchFactory(
            $this->dao
        );
    }

    public function testItReturnsGitlabBranchWithDate(): void
    {
        $gitlab_integration = $this->buildGitlabIntegration();

        $this->dao
            ->expects($this->once())
            ->method('searchBranchInRepositoryWithBranchName')
            ->with(1, 'dev_tuleap-123')
            ->willReturn([
                'branch_name' => 'dev_tuleap-123',
                'commit_sha1' => '11645a413d7af2995cd92e40bf387e39d06d0e61',
                'last_push_date' => 1608555618,
            ]);

        $gitlab_branch = $this->factory->getGitlabBranchInRepositoryWithBranchName(
            $gitlab_integration,
            'dev_tuleap-123'
        );

        self::assertNotNull($gitlab_branch);
        self::assertSame('dev_tuleap-123', $gitlab_branch->getBranchName());
        self::assertSame('11645a413d7af2995cd92e40bf387e39d06d0e61', $gitlab_branch->getCommitSha1());
        self::assertNotNull($gitlab_branch->getLastPushDate());
        self::assertSame(1608555618, $gitlab_branch->getLastPushDate()->getTimestamp());
    }

    public function testItReturnsGitlabBranchWithoutDate(): void
    {
        $gitlab_integration = $this->buildGitlabIntegration();

        $this->dao
            ->expects($this->once())
            ->method('searchBranchInRepositoryWithBranchName')
            ->with(1, 'dev_tuleap-123')
            ->willReturn([
                'branch_name' => 'dev_tuleap-123',
                'commit_sha1' => '11645a413d7af2995cd92e40bf387e39d06d0e61',
                'last_push_date' => null,
            ]);

        $gitlab_branch = $this->factory->getGitlabBranchInRepositoryWithBranchName(
            $gitlab_integration,
            'dev_tuleap-123'
        );

        self::assertNotNull($gitlab_branch);
        self::assertSame('dev_tuleap-123', $gitlab_branch->getBranchName());
        self::assertSame('11645a413d7af2995cd92e40bf387e39d06d0e61', $gitlab_branch->getCommitSha1());
        self::assertNull($gitlab_branch->getLastPushDate());
    }

    public function testItReturnsNullIfGitlabBranchNotFound(): void
    {
        $gitlab_integration = $this->buildGitlabIntegration();

        $this->dao
            ->expects($this->once())
            ->method('searchBranchInRepositoryWithBranchName')
            ->with(1, 'dev_tuleap-123')
            ->willReturn(null);

        $gitlab_branch = $this->factory->getGitlabBranchInRepositoryWithBranchName(
            $gitlab_integration,
            'dev_tuleap-123'
        );

        self::assertNull($gitlab_branch);
    }

    protected function buildGitlabIntegration(): GitlabRepositoryIntegration
    {
        return new GitlabRepositoryIntegration(
            1,
            4,
            'root/repo01',
            '',
            'https://example.com/root/repo01',
            new DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            true
        );
    }
}
