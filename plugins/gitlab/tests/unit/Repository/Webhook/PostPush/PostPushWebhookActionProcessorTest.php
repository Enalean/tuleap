<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Repository\Webhook\PostPush;

use DateTimeImmutable;
use ColinODell\PsrTestLogger\TestLogger;
use Reference;
use ReferenceManager;
use Tuleap\Gitlab\Reference\TuleapReferenceRetriever;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Branch\PostPushWebhookActionBranchHandler;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Commits\CommitTuleapReferenceDao;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReferencesParser;
use Tuleap\Gitlab\Reference\TuleapReferencedArtifactNotFoundException;
use Tuleap\Gitlab\Reference\TuleapReferenceNotFoundException;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class PostPushWebhookActionProcessorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var PostPushWebhookActionProcessor
     */
    private $processor;

    private TestLogger $logger;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ReferenceManager
     */
    private $reference_manager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&CommitTuleapReferenceDao
     */
    private $commit_tuleap_reference_dao;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TuleapReferenceRetriever
     */
    private $tuleap_reference_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PostPushCommitBotCommenter
     */
    private $commenter;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PostPushWebhookCloseArtifactHandler
     */
    private $close_artifact_handler;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PostPushWebhookActionBranchHandler
     */
    private $action_branch_handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = new TestLogger();

        $this->commit_tuleap_reference_dao = $this->createMock(CommitTuleapReferenceDao::class);
        $this->reference_manager           = $this->createMock(ReferenceManager::class);
        $this->tuleap_reference_retriever  = $this->createMock(TuleapReferenceRetriever::class);
        $this->commenter                   = $this->createMock(PostPushCommitBotCommenter::class);
        $this->close_artifact_handler      = $this->createMock(PostPushWebhookCloseArtifactHandler::class);
        $this->action_branch_handler       = $this->createMock(PostPushWebhookActionBranchHandler::class);

        $this->processor = new PostPushWebhookActionProcessor(
            new WebhookTuleapReferencesParser(),
            $this->commit_tuleap_reference_dao,
            $this->reference_manager,
            $this->tuleap_reference_retriever,
            $this->logger,
            $this->commenter,
            $this->close_artifact_handler,
            $this->action_branch_handler,
        );

        $this->action_branch_handler->expects(self::once())->method('parseBranchReference');
    }

    public function testItProcessesActionsForPostPushWebhookWithoutAnyCloseArtifactKeyword(): void
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
            "refs/heads/main",
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

        $this->tuleap_reference_retriever
            ->method('retrieveTuleapReference')
            ->willReturnCallback(
                function (int $id): Reference {
                    if ($id === 666) {
                        throw new TuleapReferencedArtifactNotFoundException(666);
                    }
                    if ($id === 777) {
                        throw new TuleapReferenceNotFoundException();
                    }
                    if ($id === 123) {
                        return new Reference(
                            0,
                            'key',
                            'desc',
                            'link',
                            'P',
                            'service_short_name',
                            'nature',
                            1,
                            100
                        );
                    }

                    throw new \RuntimeException("Unexpected reference ID #" . $id);
                }
            );

        $this->reference_manager->expects(self::once())->method('insertCrossReference');
        $this->commit_tuleap_reference_dao->expects(self::once())->method('saveGitlabCommitInfo')
            ->with(
                1,
                'feff4ced04b237abb8b4a50b4160099313152c3c',
                1608110510,
                'A commit with three references, two bad, one good',
                "master",
                'John Snow',
                'john-snow@example.com'
            );

        $this->commenter
            ->expects(self::once())
            ->method('addCommentOnCommit');

        $this->close_artifact_handler
            ->expects(self::once())
            ->method('handleArtifactClosure');

        $this->processor->process($integration, $webhook_data, new DateTimeImmutable());

        self::assertTrue($this->logger->hasInfoThatContains('3 Tuleap references found in commit feff4ced04b237abb8b4a50b4160099313152c3c'));
        self::assertTrue($this->logger->hasInfoThatContains('Reference to Tuleap artifact #123 found'));
        self::assertTrue($this->logger->hasInfoThatContains('Reference to Tuleap artifact #666 found'));
        self::assertTrue($this->logger->hasInfoThatContains('Reference to Tuleap artifact #777 found'));
        self::assertTrue($this->logger->hasInfoThatContains('Tuleap artifact #123 found, cross-reference will be added in project the GitLab repository is integrated in'));
        self::assertTrue($this->logger->hasErrorThatContains('Tuleap artifact #666 not found, no cross-reference will be added'));
        self::assertTrue($this->logger->hasErrorThatContains('Tuleap artifact #666 not found, no cross-reference will be added'));
        self::assertTrue($this->logger->hasErrorThatContains("No reference found with the keyword 'art', and this must not happen. If you read this, this is really bad"));
        self::assertTrue($this->logger->hasInfoThatContains('Commit data for feff4ced04b237abb8b4a50b4160099313152c3c saved in database'));
    }

    public function testItProcessesActionsForPostPushWebhookWithCloseArtifactKeyword(): void
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
            "refs/heads/main",
            [
                new PostPushCommitWebhookData(
                    'feff4ced04b237abb8b4a50b4160099313152c3c',
                    'A commit with references containing close artifact keyword',
                    'A commit with reference: resolve TULEAP-123',
                    "master",
                    1608110510,
                    "john-snow@example.com",
                    "John Snow"
                ),
            ]
        );

        $this->tuleap_reference_retriever->expects(self::once())->method('retrieveTuleapReference')
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

        $this->reference_manager->expects(self::once())->method('insertCrossReference');
        $this->commit_tuleap_reference_dao->expects(self::once())->method('saveGitlabCommitInfo')
            ->with(
                1,
                'feff4ced04b237abb8b4a50b4160099313152c3c',
                1608110510,
                'A commit with references containing close artifact keyword',
                "master",
                'John Snow',
                'john-snow@example.com'
            );

        $this->commenter
            ->expects(self::once())
            ->method('addCommentOnCommit');

        $this->close_artifact_handler
            ->expects(self::once())
            ->method('handleArtifactClosure');

        $this->processor->process($integration, $webhook_data, new DateTimeImmutable());

        self::assertTrue($this->logger->hasInfoThatContains('1 Tuleap references found in commit feff4ced04b237abb8b4a50b4160099313152c3c'));
        self::assertTrue($this->logger->hasInfoThatContains('Reference to Tuleap artifact #123 found'));
        self::assertTrue($this->logger->hasInfoThatContains('Tuleap artifact #123 found, cross-reference will be added in project the GitLab repository is integrated in'));
        self::assertTrue($this->logger->hasInfoThatContains('Commit data for feff4ced04b237abb8b4a50b4160099313152c3c saved in database'));
    }

    public function testItDoesNothingIfArtifactDoesNotExist(): void
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
            "refs/heads/main",
            [
                new PostPushCommitWebhookData(
                    'feff4ced04b237abb8b4a50b4160099313152c3c',
                    'commit TULEAP-123 01',
                    'commit TULEAP-123 01',
                    "master",
                    1608110510,
                    "john-snow@example.com",
                    "John Snow"
                ),
            ]
        );

        $this->tuleap_reference_retriever->expects(self::once())->method('retrieveTuleapReference')
            ->with(123)
            ->willThrowException(new TuleapReferencedArtifactNotFoundException(123));

        $this->commit_tuleap_reference_dao->expects(self::never())->method('saveGitlabCommitInfo');

        $this->commenter
            ->expects(self::never())
            ->method('addCommentOnCommit');

        $this->close_artifact_handler
            ->expects(self::never())
            ->method('handleArtifactClosure');

        $this->reference_manager->expects(self::never())->method('insertCrossReference');

        $this->processor->process($integration, $webhook_data, new DateTimeImmutable());

        self::assertTrue($this->logger->hasInfoThatContains('1 Tuleap references found in commit feff4ced04b237abb8b4a50b4160099313152c3c'));
        self::assertTrue($this->logger->hasInfoThatContains('Reference to Tuleap artifact #123 found'));
        self::assertTrue($this->logger->hasErrorThatContains('Tuleap artifact #123 not found, no cross-reference will be added'));
    }

    public function testItDoesNothingIfArtReferenceNotFound(): void
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
            "refs/heads/main",
            [
                new PostPushCommitWebhookData(
                    'feff4ced04b237abb8b4a50b4160099313152c3c',
                    'commit TULEAP-123 01',
                    'commit TULEAP-123 01',
                    "master",
                    1608110510,
                    "john-snow@example.com",
                    "John Snow"
                ),
            ]
        );

        $this->tuleap_reference_retriever->expects(self::once())->method('retrieveTuleapReference')
            ->with(123)
            ->willThrowException(new TuleapReferenceNotFoundException());

        $this->commit_tuleap_reference_dao->expects(self::never())->method('saveGitlabCommitInfo');

        $this->commenter
            ->expects(self::never())
            ->method('addCommentOnCommit');

        $this->close_artifact_handler
            ->expects(self::never())
            ->method('handleArtifactClosure');

        $this->reference_manager->expects(self::never())->method('insertCrossReference');

        $this->processor->process($integration, $webhook_data, new DateTimeImmutable());

        self::assertTrue($this->logger->hasInfoThatContains('1 Tuleap references found in commit feff4ced04b237abb8b4a50b4160099313152c3c'));
        self::assertTrue($this->logger->hasInfoThatContains('Reference to Tuleap artifact #123 found'));
        self::assertTrue($this->logger->hasErrorThatContains('No reference found with the keyword \'art\', and this must not happen. If you read this, this is really bad'));
    }
}
