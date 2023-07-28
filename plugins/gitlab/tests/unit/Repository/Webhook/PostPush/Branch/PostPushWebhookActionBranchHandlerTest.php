<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Repository\Webhook\PostPush\Branch;

use DateTimeImmutable;
use Psr\Log\NullLogger;
use Reference;
use ReferenceManager;
use Tuleap\Gitlab\Reference\TuleapReferencedArtifactNotFoundException;
use Tuleap\Gitlab\Reference\TuleapReferenceRetriever;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushCommitWebhookData;
use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushWebhookData;
use Tuleap\Reference\CrossReferenceManager;
use Tuleap\Reference\CrossReferencesDao;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class PostPushWebhookActionBranchHandlerTest extends TestCase
{
    private PostPushWebhookActionBranchHandler $handler;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ReferenceManager
     */
    private $reference_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TuleapReferenceRetriever
     */
    private $tuleap_reference_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&BranchInfoDao
     */
    private $branch_info_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&CrossReferencesDao
     */
    private $cross_reference_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&CrossReferenceManager
     */
    private $cross_reference_manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reference_manager          = $this->createMock(ReferenceManager::class);
        $this->tuleap_reference_retriever = $this->createMock(TuleapReferenceRetriever::class);
        $this->branch_info_dao            = $this->createMock(BranchInfoDao::class);
        $this->cross_reference_dao        = $this->createMock(CrossReferencesDao::class);
        $this->cross_reference_manager    = $this->createMock(CrossReferenceManager::class);

        $this->handler = new PostPushWebhookActionBranchHandler(
            new BranchNameTuleapReferenceParser(),
            $this->reference_manager,
            $this->tuleap_reference_retriever,
            $this->branch_info_dao,
            $this->cross_reference_dao,
            $this->cross_reference_manager,
            new NullLogger()
        );
    }

    public function testItSavesTheReferenceInTheBranchName(): void
    {
        $integration = new GitlabRepositoryIntegration(
            1,
            123654,
            'root/repo01',
            '',
            'https://example.com/root/repo01',
            new DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $webhook_data = new PostPushWebhookData(
            'push',
            123654,
            'https://example.com/root/repo01',
            "3cffabe5",
            "refs/heads/dev_TULEAP-123",
            [
                new PostPushCommitWebhookData(
                    'feff4ced04b237abb8b4a50b4160099313152c3c',
                    'A commit with three references, two bad, one good',
                    'A commit with three references: TULEAP-666 TULEAP-777 TULEAP-123',
                    "master",
                    1608110510,
                    "john-snow@example.com",
                    "John Snow"
                ),
            ]
        );

        $this->tuleap_reference_retriever->expects(self::once())
            ->method('retrieveTuleapReference')
            ->with(123)
            ->willReturn(
                new Reference(
                    0,
                    'key',
                    'desc',
                    'link',
                    'P',
                    'service_short_name',
                    'nature',
                    1,
                    100
                )
            );

        $this->cross_reference_dao->expects(self::once())
            ->method('existInDb')
            ->willReturn(false);

        $this->reference_manager->expects(self::once())
            ->method('insertCrossReference');

        $this->branch_info_dao->expects(self::once())
            ->method('saveGitlabBranchInfo');
        $this->branch_info_dao->expects(self::never())
            ->method('updateGitlabBranchInformation');

        $this->branch_info_dao->expects(self::never())
            ->method('deleteBranchInGitlabIntegration');
        $this->cross_reference_manager->expects(self::never())
            ->method('deleteEntity');

        $this->handler->parseBranchReference(
            $integration,
            $webhook_data,
            new DateTimeImmutable()
        );
    }

    public function testItDoesNothingIfTheBranchNameIsEmpty(): void
    {
        $integration = new GitlabRepositoryIntegration(
            1,
            123654,
            'root/repo01',
            '',
            'https://example.com/root/repo01',
            new DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $webhook_data = new PostPushWebhookData(
            'push',
            123654,
            'https://example.com/root/repo01',
            "3cffabe5",
            "",
            [
                new PostPushCommitWebhookData(
                    'feff4ced04b237abb8b4a50b4160099313152c3c',
                    'A commit with three references, two bad, one good',
                    'A commit with three references: TULEAP-666 TULEAP-777 TULEAP-123',
                    "master",
                    1608110510,
                    "john-snow@example.com",
                    "John Snow"
                ),
            ]
        );

        $this->tuleap_reference_retriever->expects(self::never())
            ->method('retrieveTuleapReference');
        $this->cross_reference_dao->expects(self::never())
            ->method('existInDb');
        $this->reference_manager->expects(self::never())
            ->method('insertCrossReference');
        $this->branch_info_dao->expects(self::never())
            ->method('saveGitlabBranchInfo');
        $this->branch_info_dao->expects(self::never())
            ->method('updateGitlabBranchInformation');

        $this->branch_info_dao->expects(self::never())
            ->method('deleteBranchInGitlabIntegration');
        $this->cross_reference_manager->expects(self::never())
            ->method('deleteEntity');

        $this->handler->parseBranchReference(
            $integration,
            $webhook_data,
            new DateTimeImmutable()
        );
    }

    public function testItDoesNothingIfTheBranchNameDesNotContainATuleapReference(): void
    {
        $integration = new GitlabRepositoryIntegration(
            1,
            123654,
            'root/repo01',
            '',
            'https://example.com/root/repo01',
            new DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $webhook_data = new PostPushWebhookData(
            'push',
            123654,
            'https://example.com/root/repo01',
            "3cffabe5",
            "refs/heads/dev",
            [
                new PostPushCommitWebhookData(
                    'feff4ced04b237abb8b4a50b4160099313152c3c',
                    'A commit with three references, two bad, one good',
                    'A commit with three references: TULEAP-666 TULEAP-777 TULEAP-123',
                    "master",
                    1608110510,
                    "john-snow@example.com",
                    "John Snow"
                ),
            ]
        );

        $this->tuleap_reference_retriever->expects(self::never())
            ->method('retrieveTuleapReference');
        $this->cross_reference_dao->expects(self::never())
            ->method('existInDb');
        $this->reference_manager->expects(self::never())
            ->method('insertCrossReference');
        $this->branch_info_dao->expects(self::never())
            ->method('saveGitlabBranchInfo');
        $this->branch_info_dao->expects(self::never())
            ->method('updateGitlabBranchInformation');

        $this->branch_info_dao->expects(self::never())
            ->method('deleteBranchInGitlabIntegration');
        $this->cross_reference_manager->expects(self::never())
            ->method('deleteEntity');

        $this->handler->parseBranchReference(
            $integration,
            $webhook_data,
            new DateTimeImmutable()
        );
    }

    public function testItDoesNothingIfTuleapReferenceIsNotFound(): void
    {
        $integration = new GitlabRepositoryIntegration(
            1,
            123654,
            'root/repo01',
            '',
            'https://example.com/root/repo01',
            new DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $webhook_data = new PostPushWebhookData(
            'push',
            123654,
            'https://example.com/root/repo01',
            "3cffabe5",
            "refs/heads/dev_TULEAP-123",
            [
                new PostPushCommitWebhookData(
                    'feff4ced04b237abb8b4a50b4160099313152c3c',
                    'A commit with three references, two bad, one good',
                    'A commit with three references: TULEAP-666 TULEAP-777 TULEAP-123',
                    "master",
                    1608110510,
                    "john-snow@example.com",
                    "John Snow"
                ),
            ]
        );

        $this->tuleap_reference_retriever->expects(self::once())
            ->method('retrieveTuleapReference')
            ->with(123)
            ->willThrowException(
                new TuleapReferencedArtifactNotFoundException(123)
            );

        $this->cross_reference_dao->expects(self::never())
            ->method('existInDb');
        $this->reference_manager->expects(self::never())
            ->method('insertCrossReference');
        $this->branch_info_dao->expects(self::never())
            ->method('saveGitlabBranchInfo');
        $this->branch_info_dao->expects(self::never())
            ->method('updateGitlabBranchInformation');

        $this->branch_info_dao->expects(self::never())
            ->method('deleteBranchInGitlabIntegration');
        $this->cross_reference_manager->expects(self::never())
            ->method('deleteEntity');

        $this->handler->parseBranchReference(
            $integration,
            $webhook_data,
            new DateTimeImmutable()
        );
    }

    public function testItDoesNotSaveMultipleTimeTheSameBranchButUpdatesTheAssociatedSHA1(): void
    {
        $integration = new GitlabRepositoryIntegration(
            1,
            123654,
            'root/repo01',
            '',
            'https://example.com/root/repo01',
            new DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $webhook_data = new PostPushWebhookData(
            'push',
            123654,
            'https://example.com/root/repo01',
            "3cffabe5",
            "refs/heads/dev_TULEAP-123",
            [
                new PostPushCommitWebhookData(
                    'feff4ced04b237abb8b4a50b4160099313152c3c',
                    'A commit with three references, two bad, one good',
                    'A commit with three references: TULEAP-666 TULEAP-777 TULEAP-123',
                    "master",
                    1608110510,
                    "john-snow@example.com",
                    "John Snow"
                ),
            ]
        );

        $this->tuleap_reference_retriever->expects(self::once())
            ->method('retrieveTuleapReference')
            ->with(123)
            ->willReturn(
                new Reference(
                    0,
                    'key',
                    'desc',
                    'link',
                    'P',
                    'service_short_name',
                    'nature',
                    1,
                    100
                )
            );

        $this->cross_reference_dao->expects(self::once())
            ->method('existInDb')
            ->willReturn(true);

        $this->reference_manager->expects(self::never())
            ->method('insertCrossReference');
        $this->branch_info_dao->expects(self::never())
            ->method('saveGitlabBranchInfo');

        $this->branch_info_dao->expects(self::once())
            ->method('updateGitlabBranchInformation');

        $this->branch_info_dao->expects(self::never())
            ->method('deleteBranchInGitlabIntegration');
        $this->cross_reference_manager->expects(self::never())
            ->method('deleteEntity');

        $this->handler->parseBranchReference(
            $integration,
            $webhook_data,
            new DateTimeImmutable()
        );
    }

    public function testItDeletesTheBranchInformationWhenBranchIsDeleted(): void
    {
        $integration = new GitlabRepositoryIntegration(
            1,
            123654,
            'root/repo01',
            '',
            'https://example.com/root/repo01',
            new DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $webhook_data = new PostPushWebhookData(
            'push',
            123654,
            'https://example.com/root/repo01',
            null,
            "refs/heads/dev_TULEAP-123",
            [
                new PostPushCommitWebhookData(
                    'feff4ced04b237abb8b4a50b4160099313152c3c',
                    'A commit with three references, two bad, one good',
                    'A commit with three references: TULEAP-666 TULEAP-777 TULEAP-123',
                    "master",
                    1608110510,
                    "john-snow@example.com",
                    "John Snow"
                ),
            ]
        );

        $this->tuleap_reference_retriever->expects(self::once())
            ->method('retrieveTuleapReference')
            ->with(123)
            ->willReturn(
                new Reference(
                    0,
                    'key',
                    'desc',
                    'link',
                    'P',
                    'service_short_name',
                    'nature',
                    1,
                    100
                )
            );

        $this->cross_reference_dao->expects(self::never())
            ->method('existInDb');
        $this->reference_manager->expects(self::never())
            ->method('insertCrossReference');
        $this->branch_info_dao->expects(self::never())
            ->method('saveGitlabBranchInfo');
        $this->branch_info_dao->expects(self::never())
            ->method('updateGitlabBranchInformation');

        $this->branch_info_dao->expects(self::once())
            ->method('deleteBranchInGitlabIntegration');
        $this->cross_reference_manager->expects(self::once())
            ->method('deleteEntity');

        $this->handler->parseBranchReference(
            $integration,
            $webhook_data,
            new DateTimeImmutable()
        );
    }
}
