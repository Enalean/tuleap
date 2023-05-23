<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Repository\Webhook\PostMergeRequest;

use DateTimeImmutable;
use ColinODell\PsrTestLogger\TestLogger;
use Reference;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\Reference\MergeRequest\GitlabMergeRequest;
use Tuleap\Gitlab\Reference\MergeRequest\GitlabMergeRequestReferenceRetriever;
use Tuleap\Gitlab\Reference\TuleapReferencedArtifactNotFoundException;
use Tuleap\Gitlab\Reference\TuleapReferenceNotFoundException;
use Tuleap\Gitlab\Reference\TuleapReferenceRetriever;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Branch\BranchNameTuleapReferenceParser;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReferencesParser;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class PostMergeRequestWebhookActionProcessorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var PostMergeRequestWebhookActionProcessor
     */
    private $processor;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TuleapReferenceRetriever
     */
    private $tuleap_reference_retriever;
    private TestLogger $logger;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\ReferenceManager
     */
    private $reference_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&MergeRequestTuleapReferenceDao
     */
    private $merge_request_reference_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PostMergeRequestBotCommenter
     */
    private $bot_commenter;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PostMergeRequestWebhookAuthorDataRetriever
     */
    private $author_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitlabMergeRequestReferenceRetriever
     */
    private $merge_request_retriever;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tuleap_reference_retriever  = $this->createMock(TuleapReferenceRetriever::class);
        $this->logger                      = new TestLogger();
        $this->reference_manager           = $this->createMock(\ReferenceManager::class);
        $this->merge_request_reference_dao = $this->createMock(MergeRequestTuleapReferenceDao::class);
        $this->bot_commenter               = $this->createMock(PostMergeRequestBotCommenter::class);
        $this->author_retriever            = $this->createMock(PostMergeRequestWebhookAuthorDataRetriever::class);
        $this->merge_request_retriever     = $this->createMock(GitlabMergeRequestReferenceRetriever::class);

        $references_from_merge_request_data_extractor = new TuleapReferencesFromMergeRequestDataExtractor(
            new WebhookTuleapReferencesParser(),
            new BranchNameTuleapReferenceParser(),
        );

        $this->processor = new PostMergeRequestWebhookActionProcessor(
            $this->merge_request_reference_dao,
            $this->logger,
            $this->bot_commenter,
            new PreviouslySavedReferencesRetriever(
                $references_from_merge_request_data_extractor,
                $this->tuleap_reference_retriever,
                $this->merge_request_reference_dao,
            ),
            new CrossReferenceFromMergeRequestCreator(
                $references_from_merge_request_data_extractor,
                $this->tuleap_reference_retriever,
                $this->reference_manager,
                $this->logger,
            ),
            $this->author_retriever,
            $this->merge_request_retriever
        );
    }

    public function testItProcessesActionsForPostMergeRequestWebhook(): void
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

        $merge_request_webhook_data = new PostMergeRequestWebhookData(
            'merge_request',
            123,
            'https://example.com',
            2,
            "My Title TULEAP-58",
            'TULEAP-666 TULEAP-45',
            'opened',
            (new \DateTimeImmutable())->setTimestamp(1611315112),
            10,
            'some_feature'
        );

        $this->merge_request_reference_dao
            ->method('searchMergeRequestInRepositoryWithId')
            ->with(1, 2)
            ->willReturn([]);

        $this->merge_request_retriever
            ->method('getGitlabMergeRequestInRepositoryWithId')
            ->with($integration, 2)
            ->willReturn(null);

        $this->merge_request_reference_dao
            ->expects(self::once())
            ->method('saveGitlabMergeRequestInfo')
            ->with(1, 2, 'My Title TULEAP-58', 'TULEAP-666 TULEAP-45', 'some_feature', 'opened', 1611315112);

        $this->reference_manager
            ->expects(self::once())
            ->method('insertCrossReference');

        $this->tuleap_reference_retriever
            ->method('retrieveTuleapReference')
            ->willReturnCallback(
                function (int $id): Reference {
                    if ($id === 58) {
                        throw new TuleapReferencedArtifactNotFoundException(58);
                    }
                    if ($id === 666) {
                        throw new TuleapReferenceNotFoundException();
                    }
                    if ($id === 45) {
                        return new Reference(
                            45,
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

        $this->author_retriever
            ->expects(self::once())
            ->method('retrieveAuthorData')
            ->with($integration, $merge_request_webhook_data)
            ->willReturn(['name' => 'John', 'public_email' => 'john@thewall.fr']);

        $this->merge_request_reference_dao
            ->expects(self::once())
            ->method('setAuthorData')
            ->with(1, 2, 'John', 'john@thewall.fr');

        $this->bot_commenter
            ->expects(self::once())
            ->method('addCommentOnMergeRequest')
            ->with(
                $merge_request_webhook_data,
                $integration,
                [new WebhookTuleapReference(45, null)]
            );

        $this->processor->process($integration, $merge_request_webhook_data);

        self::assertTrue($this->logger->hasInfoThatContains('3 Tuleap references found in merge request 2'));
        self::assertTrue($this->logger->hasInfoThatContains('Reference to Tuleap artifact #58 found, cross-reference will be added in project the GitLab repository is integrated in.'));
        self::assertTrue($this->logger->hasInfoThatContains('Reference to Tuleap artifact #666 found, cross-reference will be added in project the GitLab repository is integrated in.'));
        self::assertTrue($this->logger->hasInfoThatContains('Reference to Tuleap artifact #45 found, cross-reference will be added in project the GitLab repository is integrated in.'));
        self::assertTrue($this->logger->hasErrorThatContains('Tuleap artifact #58 not found, no cross-reference will be added.'));
        self::assertTrue($this->logger->hasErrorThatContains('No reference found with the keyword \'art\', and this must not happen. If you read this, this is really bad.'));
        self::assertTrue($this->logger->hasInfoThatContains('Tuleap artifact #45 found'));
        self::assertTrue($this->logger->hasInfoThatContains('Merge request data for 2 saved in database'));
        self::assertTrue($this->logger->hasInfoThatContains('Try to get author data of merge request #2'));
        self::assertTrue($this->logger->hasInfoThatContains('Author name of merge request #2 is: John'));
        self::assertTrue($this->logger->hasInfoThatContains('Author has been saved in database'));
        self::assertTrue($this->logger->hasDebug('Some references are added, a comment should be added'));
    }

    public function testItDoesNotWriteACommentIfReferencesAreTheSameThanPreviousOnes(): void
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

        $merge_request_webhook_data = new PostMergeRequestWebhookData(
            'merge_request',
            123,
            'https://example.com',
            2,
            "My Title TULEAP-58",
            'TULEAP-666 TULEAP-45',
            'opened',
            (new \DateTimeImmutable())->setTimestamp(1611315112),
            10,
            'some_feature'
        );

        $this->merge_request_reference_dao
            ->method('searchMergeRequestInRepositoryWithId')
            ->with(1, 2)
            ->willReturn(
                [
                    'title'        => 'My Title TULEAP-58',
                    'description'  => 'TULEAP-666 TULEAP-45',
                    'source_branch' => 'some_feature',
                    'author_name'  => null,
                    'author_email' => null,
                ]
            );

        $this->merge_request_retriever
            ->method('getGitlabMergeRequestInRepositoryWithId')
            ->with($integration, 2)
            ->willReturn(new GitlabMergeRequest(
                'My Title TULEAP-58',
                'TULEAP-666 TULEAP-45',
                new DateTimeImmutable(),
                null,
                null
            ));

        $this->merge_request_reference_dao
            ->expects(self::once())
            ->method('saveGitlabMergeRequestInfo')
            ->with(1, 2, 'My Title TULEAP-58', 'TULEAP-666 TULEAP-45', 'some_feature', 'opened', 1611315112);

        $this->reference_manager
            ->expects(self::exactly(2))
            ->method('insertCrossReference');

        $this->tuleap_reference_retriever
            ->method('retrieveTuleapReference')
            ->willReturnCallback(
                function (int $id): Reference {
                    if ($id === 58) {
                        return new Reference(
                            58,
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
                    if ($id === 666) {
                        throw new TuleapReferenceNotFoundException();
                    }
                    if ($id === 45) {
                        return new Reference(
                            45,
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

        $this->author_retriever
            ->expects(self::once())
            ->method('retrieveAuthorData')
            ->with($integration, $merge_request_webhook_data)
            ->willReturn(['name' => 'John', 'public_email' => 'john@thewall.fr']);

        $this->merge_request_reference_dao
            ->expects(self::once())
            ->method('setAuthorData')
            ->with(1, 2, 'John', 'john@thewall.fr');

        $this->bot_commenter
            ->expects(self::never())
            ->method('addCommentOnMergeRequest');

        $this->processor->process($integration, $merge_request_webhook_data);

        self::assertTrue($this->logger->hasInfoThatContains('3 Tuleap references found in merge request 2'));
        self::assertTrue($this->logger->hasInfoThatContains('Reference to Tuleap artifact #58 found, cross-reference will be added in project the GitLab repository is integrated in.'));
        self::assertTrue($this->logger->hasInfoThatContains('Reference to Tuleap artifact #666 found, cross-reference will be added in project the GitLab repository is integrated in.'));
        self::assertTrue($this->logger->hasInfoThatContains('Reference to Tuleap artifact #45 found, cross-reference will be added in project the GitLab repository is integrated in.'));
        self::assertTrue($this->logger->hasInfoThatContains('Reference to Tuleap artifact #45 found, cross-reference will be added in project the GitLab repository is integrated in.'));
        self::assertTrue($this->logger->hasErrorThatContains('No reference found with the keyword \'art\', and this must not happen. If you read this, this is really bad.'));
        self::assertTrue($this->logger->hasInfoThatContains('Tuleap artifact #45 found'));
        self::assertTrue($this->logger->hasInfoThatContains('Tuleap artifact #58 found'));
        self::assertTrue($this->logger->hasInfoThatContains('Merge request data for 2 saved in database'));
        self::assertTrue($this->logger->hasInfoThatContains('Try to get author data of merge request #2'));
        self::assertTrue($this->logger->hasInfoThatContains('Author name of merge request #2 is: John'));
        self::assertTrue($this->logger->hasInfoThatContains('Author has been saved in database'));
    }

    public function testItWritesACommentIfSomeReferencesAreRemoved(): void
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

        $merge_request_webhook_data = new PostMergeRequestWebhookData(
            'merge_request',
            123,
            'https://example.com',
            2,
            "My Title TULEAP-58",
            'TULEAP-666',
            'opened',
            (new \DateTimeImmutable())->setTimestamp(1611315112),
            10,
            'some_feature'
        );

        $this->merge_request_reference_dao
            ->method('searchMergeRequestInRepositoryWithId')
            ->with(1, 2)
            ->willReturn(
                [
                    'title'        => 'My Title TULEAP-58',
                    'description'  => 'TULEAP-666 TULEAP-45',
                    'source_branch' => 'some_feature',
                    'author_name'  => null,
                    'author_email' => null,
                ]
            );

        $this->merge_request_retriever
            ->method('getGitlabMergeRequestInRepositoryWithId')
            ->with($integration, 2)
            ->willReturn(new GitlabMergeRequest(
                'My Title TULEAP-58',
                'TULEAP-666 TULEAP-45',
                new DateTimeImmutable(),
                null,
                null
            ));

        $this->merge_request_reference_dao
            ->expects(self::once())
            ->method('saveGitlabMergeRequestInfo')
            ->with(1, 2, 'My Title TULEAP-58', 'TULEAP-666', 'some_feature', 'opened', 1611315112);

        $this->reference_manager
            ->expects(self::once())
            ->method('insertCrossReference');

        $this->tuleap_reference_retriever
            ->method('retrieveTuleapReference')
            ->willReturnCallback(
                function (int $id): Reference {
                    if ($id === 58) {
                        return new Reference(
                            58,
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
                    if ($id === 666) {
                        throw new TuleapReferenceNotFoundException();
                    }
                    if ($id === 45) {
                        return new Reference(
                            43,
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

        $this->author_retriever
            ->expects(self::once())
            ->method('retrieveAuthorData')
            ->with($integration, $merge_request_webhook_data)
            ->willReturn(['name' => 'John', 'public_email' => 'john@thewall.fr']);

        $this->merge_request_reference_dao
            ->expects(self::once())
            ->method('setAuthorData')
            ->with(1, 2, 'John', 'john@thewall.fr');

        $this->bot_commenter
            ->expects(self::once())
            ->method('addCommentOnMergeRequest')
            ->with(
                $merge_request_webhook_data,
                $integration,
                [new WebhookTuleapReference(58, null)]
            );

        $this->processor->process($integration, $merge_request_webhook_data);
    }

    public function testItProcessesActionsForPostMergeRequestWebhookAlreadyIntegrated(): void
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

        $merge_request_webhook_data = new PostMergeRequestWebhookData(
            'merge_request',
            123,
            'https://example.com',
            2,
            "My Title TULEAP-58",
            '',
            'closed',
            (new \DateTimeImmutable())->setTimestamp(1611315112),
            10,
            'some_feature'
        );

        $this->merge_request_reference_dao
            ->method('searchMergeRequestInRepositoryWithId')
            ->with(1, 2)
            ->willReturn([]);

        $this->merge_request_retriever
            ->method('getGitlabMergeRequestInRepositoryWithId')
            ->with($integration, 2)
            ->willReturn(null);

        $this->merge_request_reference_dao
            ->expects(self::once())
            ->method('saveGitlabMergeRequestInfo')
            ->with(1, 2, 'My Title TULEAP-58', '', 'some_feature', 'closed', 1611315112);

        $this->reference_manager
            ->expects(self::once())
            ->method('insertCrossReference');

        $this->tuleap_reference_retriever
            ->expects(self::once())
            ->method('retrieveTuleapReference')
            ->with(58)
            ->willReturn(
                new Reference(
                    58,
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

        $this->author_retriever
            ->expects(self::once())
            ->method('retrieveAuthorData')
            ->with($integration, $merge_request_webhook_data)
            ->willReturn(['name' => 'John', 'public_email' => 'john@thewall.fr']);

        $this->merge_request_reference_dao
            ->expects(self::once())
            ->method('setAuthorData')
            ->with(1, 2, 'John', 'john@thewall.fr');

        $this->bot_commenter
            ->expects(self::once())
            ->method('addCommentOnMergeRequest');

        $this->processor->process($integration, $merge_request_webhook_data);

        self::assertTrue($this->logger->hasInfoThatContains('1 Tuleap references found in merge request 2'));
        self::assertTrue($this->logger->hasInfoThatContains('Reference to Tuleap artifact #58 found, cross-reference will be added in project the GitLab repository is integrated in.'));
        self::assertTrue($this->logger->hasInfoThatContains('Tuleap artifact #58 found'));
        self::assertTrue($this->logger->hasInfoThatContains('Merge request data for 2 saved in database'));
        self::assertTrue($this->logger->hasInfoThatContains('Try to get author data of merge request #2'));
        self::assertTrue($this->logger->hasInfoThatContains('Author name of merge request #2 is: John'));
        self::assertTrue($this->logger->hasInfoThatContains('Author has been saved in database'));
        self::assertTrue($this->logger->hasDebugThatContains('Some references are added, a comment should be added'));
    }

    public function testItProcessesActionsForPostMergeRequestWebhookAlreadyIntegratedAndLogErrorIfCantGetAuthor(): void
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

        $merge_request_webhook_data = new PostMergeRequestWebhookData(
            'merge_request',
            123,
            'https://example.com',
            2,
            "My Title TULEAP-58",
            '',
            'closed',
            (new \DateTimeImmutable())->setTimestamp(1611315112),
            10,
            'some_feature'
        );

        $this->merge_request_reference_dao
            ->method('searchMergeRequestInRepositoryWithId')
            ->with(1, 2)
            ->willReturn([]);

        $this->merge_request_retriever
            ->method('getGitlabMergeRequestInRepositoryWithId')
            ->with($integration, 2)
            ->willReturn(null);

        $this->merge_request_reference_dao
            ->expects(self::once())
            ->method('saveGitlabMergeRequestInfo')
            ->with(1, 2, 'My Title TULEAP-58', '', 'some_feature', 'closed', 1611315112);

        $this->reference_manager
            ->expects(self::once())
            ->method('insertCrossReference');

        $this->tuleap_reference_retriever
            ->expects(self::once())
            ->method('retrieveTuleapReference')
            ->with(58)
            ->willReturn(
                new Reference(
                    58,
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

        $exception = new GitlabRequestException(404, "Unauthorized");

        $this->author_retriever
            ->expects(self::once())
            ->method('retrieveAuthorData')
            ->with($integration, $merge_request_webhook_data)
            ->willThrowException($exception);

        $this->merge_request_reference_dao
            ->expects(self::never())
            ->method('setAuthorData');

        $this->bot_commenter
            ->expects(self::once())
            ->method('addCommentOnMergeRequest');

        $this->processor->process($integration, $merge_request_webhook_data);

        self::assertTrue($this->logger->hasInfoThatContains('1 Tuleap references found in merge request 2'));
        self::assertTrue($this->logger->hasInfoThatContains('Reference to Tuleap artifact #58 found, cross-reference will be added in project the GitLab repository is integrated in'));
        self::assertTrue($this->logger->hasInfoThatContains('Tuleap artifact #58 found'));
        self::assertTrue($this->logger->hasInfoThatContains('Merge request data for 2 saved in database'));
        self::assertTrue($this->logger->hasInfoThatContains('Try to get author data of merge request #2'));
        self::assertTrue($this->logger->hasErrorThatContains('Can\'t get data on author of merge request #2'));
        self::assertTrue($this->logger->hasDebugThatContains('Some references are added, a comment should be added'));
    }

    public function testIDoesNotSaveMergeRequestDataIfNoReferencesAreFound(): void
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

        $merge_request_webhook_data = new PostMergeRequestWebhookData(
            'merge_request',
            123,
            'https://example.com',
            2,
            "My Title",
            '',
            'closed',
            (new \DateTimeImmutable())->setTimestamp(1611315112),
            10,
            'some_feature'
        );

        $this->merge_request_reference_dao
            ->method('searchMergeRequestInRepositoryWithId')
            ->with(1, 2)
            ->willReturn([]);

        $this->merge_request_retriever
            ->method('getGitlabMergeRequestInRepositoryWithId')
            ->with($integration, 2)
            ->willReturn(null);

        $this->merge_request_reference_dao
            ->expects(self::never())
            ->method('saveGitlabMergeRequestInfo');

        $this->bot_commenter
            ->expects(self::never())
            ->method('addCommentOnMergeRequest');

        $this->processor->process($integration, $merge_request_webhook_data);

        self::assertTrue($this->logger->hasInfoThatContains('0 Tuleap references found in merge request 2'));
    }

    public function testItUpdatesSavedMergeRequestDataIfNoReferencesAreFoundButMergeRequestWasAlreadySaved(): void
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

        $merge_request_webhook_data = new PostMergeRequestWebhookData(
            'merge_request',
            123,
            'https://example.com',
            2,
            "My Title",
            '',
            'closed',
            (new \DateTimeImmutable())->setTimestamp(1611315112),
            10,
            'some_feature'
        );

        $this->merge_request_reference_dao
            ->method('searchMergeRequestInRepositoryWithId')
            ->willReturn(
                [
                    'title'        => 'Previous title',
                    'state'        => 'opened',
                    'description'  => 'Previous description',
                    'source_branch' => 'some_branch',
                    'author_name'  => 'John',
                    'author_email' => 'john@thewall.fr',
                ]
            );

        $this->merge_request_retriever
            ->method('getGitlabMergeRequestInRepositoryWithId')
            ->with($integration, 2)
            ->willReturn(new GitlabMergeRequest(
                'My Title TULEAP-58',
                'TULEAP-666 TULEAP-45',
                new DateTimeImmutable(),
                'John',
                'john@thewall.fr'
            ));

        $this->merge_request_reference_dao
            ->expects(self::once())
            ->method('saveGitlabMergeRequestInfo')
            ->with(1, 2, 'My Title', '', 'some_feature', 'closed', 1611315112);

        $this->author_retriever
            ->expects(self::never())
            ->method('retrieveAuthorData')
            ->with($integration, $merge_request_webhook_data);

        $this->merge_request_reference_dao
            ->expects(self::never())
            ->method('setAuthorData')
            ->with(1, 2, 'John', 'john@thewall.fr');

        $this->bot_commenter
            ->expects(self::never())
            ->method('addCommentOnMergeRequest');

        $this->processor->process($integration, $merge_request_webhook_data);

        self::assertTrue($this->logger->hasInfoThatContains('0 Tuleap references found in merge request 2'));
        self::assertTrue($this->logger->hasInfoThatContains('Merge request data for 2 saved in database'));
        self::assertFalse($this->logger->hasInfoThatContains('Try to get author data of merge request #2'));
        self::assertFalse($this->logger->hasInfoThatContains('Author name of merge request #2 is: John'));
        self::assertFalse($this->logger->hasInfoThatContains('Author has been saved in database'));
    }
}
