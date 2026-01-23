<?php
/**
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Repository\Project;

use ColinODell\PsrTestLogger\TestLogger;
use DateTimeImmutable;
use Override;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Gitlab\Reference\UpdateCrossReference;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\UpdateGitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Webhook\WebhookDataExtractor;
use Tuleap\Gitlab\Test\Builder\WebhookDataBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;

#[DisableReturnValueGenerationForTestDoubles]
final class GitlabRepositoryUpdaterTest extends TestCase
{
    private TestLogger $logger;
    private UpdateGitlabRepositoryIntegration&MockObject $integration_dao;
    private UpdateCrossReference&MockObject $reference_dao;
    private GitlabRepositoryUpdater $updater;

    #[Override]
    protected function setUp(): void
    {
        $this->logger          = new TestLogger();
        $this->integration_dao = $this->createMock(UpdateGitlabRepositoryIntegration::class);
        $this->reference_dao   = $this->createMock(UpdateCrossReference::class);
        $this->updater         = new GitlabRepositoryUpdater(
            $this->logger,
            new DBTransactionExecutorPassthrough(),
            $this->integration_dao,
            $this->reference_dao,
        );
    }

    public function testItFailsIfIntegrationAndWebhookAreNotSameRepository(): void
    {
        $this->updater->updateRepositoryDataIfNeeded(
            new GitlabRepositoryIntegration(
                123,
                254,
                'fabrice/some_repo',
                'Amazing!',
                'https://example.com/repo',
                new DateTimeImmutable(),
                ProjectTestBuilder::aProject()->build(),
                true,
            ),
            WebhookDataBuilder::aWebhook(WebhookDataExtractor::PUSH_EVENT)
                ->withRepositoryId(524)
                ->build(),
        );

        self::assertTrue($this->logger->hasError('Compared repositories do NOT have the same id!'));
    }

    public function testItSkipIfThereIsNoDifferences(): void
    {
        $this->integration_dao->expects($this->never())->method('updateGitlabRepositoryIntegration');

        $this->updater->updateRepositoryDataIfNeeded(
            new GitlabRepositoryIntegration(
                123,
                254,
                'fabrice/some_repo',
                'Amazing!',
                'https://example.com/repo',
                new DateTimeImmutable(),
                ProjectTestBuilder::aProject()->build(),
                true,
            ),
            WebhookDataBuilder::aWebhook(WebhookDataExtractor::PUSH_EVENT)
                ->withRepositoryId(254)
                ->withRepositoryUrl('https://example.com/repo')
                ->withRepositoryName('fabrice/some_repo')
                ->withRepositoryDescription('Amazing!')
                ->build(),
        );

        self::assertTrue($this->logger->hasDebug('There are NO differences'));
    }

    public function testItUpdatesIntegration(): void
    {
        $this->integration_dao->expects($this->once())->method('updateGitlabRepositoryIntegration')->with(
            123,
            'jeanne/some_repo',
            'Woohoo!',
            'https://example.com/new_repo',
        );
        $this->reference_dao->expects($this->once())->method('updateBranchCrossReference')->with(123, 'fabrice/some_repo', 'jeanne/some_repo');
        $this->reference_dao->expects($this->once())->method('updateCommitCrossReference')->with(123, 'fabrice/some_repo', 'jeanne/some_repo');
        $this->reference_dao->expects($this->once())->method('updateMergeRequestCrossReference')->with(123, 'fabrice/some_repo', 'jeanne/some_repo');
        $this->reference_dao->expects($this->once())->method('updateTagCrossReference')->with(123, 'fabrice/some_repo', 'jeanne/some_repo');

        $this->updater->updateRepositoryDataIfNeeded(
            new GitlabRepositoryIntegration(
                123,
                254,
                'fabrice/some_repo',
                'Amazing!',
                'https://example.com/repo',
                new DateTimeImmutable(),
                ProjectTestBuilder::aProject()->build(),
                true,
            ),
            WebhookDataBuilder::aWebhook(WebhookDataExtractor::PUSH_EVENT)
                ->withRepositoryId(254)
                ->withRepositoryUrl('https://example.com/new_repo')
                ->withRepositoryName('jeanne/some_repo')
                ->withRepositoryDescription('Woohoo!')
                ->build(),
        );

        self::assertTrue($this->logger->hasDebug('Integration updated!'));
    }
}
