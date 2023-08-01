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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook\PostMergeRequest;

use Psr\Log\LoggerInterface;
use Reference;
use Tuleap\Gitlab\Reference\TuleapReferencedArtifactNotFoundException;
use Tuleap\Gitlab\Reference\TuleapReferenceNotFoundException;
use Tuleap\Gitlab\Reference\TuleapReferenceRetriever;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Branch\BranchNameTuleapReferenceParser;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReferencesParser;
use Tuleap\Reference\CrossReference;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class CrossReferenceFromMergeRequestCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TuleapReferenceRetriever
     */
    private $tuleap_reference_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\ReferenceManager
     */
    private $reference_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&LoggerInterface
     */
    private $logger;

    private CrossReferenceFromMergeRequestCreator $creator;

    protected function setUp(): void
    {
        $this->tuleap_reference_retriever = $this->createMock(TuleapReferenceRetriever::class);
        $this->reference_manager          = $this->createMock(\ReferenceManager::class);
        $this->logger                     = $this->createMock(LoggerInterface::class);

        $this->creator = new CrossReferenceFromMergeRequestCreator(
            new TuleapReferencesFromMergeRequestDataExtractor(new WebhookTuleapReferencesParser(), new BranchNameTuleapReferenceParser()),
            $this->tuleap_reference_retriever,
            $this->reference_manager,
            $this->logger,
        );
    }

    public function testItDoesNothingIfThereIsNoReferencesInMergeRequestData(): void
    {
        $integration = new GitlabRepositoryIntegration(
            1,
            123654,
            'root/repo01',
            '',
            'https://example.com/root/repo01',
            new \DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $webhook_data = new PostMergeRequestWebhookData(
            'merge_request',
            123,
            'https://example.com',
            2,
            "My title",
            'My description',
            'opened',
            (new \DateTimeImmutable())->setTimestamp(1611315112),
            10,
            'some_feature'
        );

        $this->reference_manager
            ->expects(self::never())
            ->method('insertCrossReference');

        $this->logger
            ->expects(self::once())
            ->method('info')
            ->with('0 Tuleap references found in merge request 2');

        $this->creator->createCrossReferencesFromMergeRequest($webhook_data, $integration);
    }

    public function testItDoesNothingIfTheReferenceIsNotFound(): void
    {
        $integration = new GitlabRepositoryIntegration(
            1,
            123654,
            'root/repo01',
            '',
            'https://example.com/root/repo01',
            new \DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $webhook_data = new PostMergeRequestWebhookData(
            'merge_request',
            123,
            'https://example.com',
            2,
            "My title tuleap-42",
            'My description',
            'opened',
            (new \DateTimeImmutable())->setTimestamp(1611315112),
            10,
            'some_feature'
        );

        $this->tuleap_reference_retriever
            ->method('retrieveTuleapReference')
            ->with(42)
            ->willThrowException(new TuleapReferenceNotFoundException());

        $this->reference_manager
            ->expects(self::never())
            ->method('insertCrossReference');

        $this->logger
            ->method('info')
            ->willReturnCallback(
                function (string $message): void {
                    match ($message) {
                        '1 Tuleap references found in merge request 2',
                        '|_ Reference to Tuleap artifact #42 found, cross-reference will be added in project the GitLab repository is integrated in.' => true,
                    };
                }
            );
        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with(
                'No reference found with the keyword \'art\', and this must not happen. If you read this, this is really bad.'
            );

        $this->creator->createCrossReferencesFromMergeRequest($webhook_data, $integration);
    }

    public function testItDoesNothingIfTheReferencedArtifactIsNotFound(): void
    {
        $integration = new GitlabRepositoryIntegration(
            1,
            123654,
            'root/repo01',
            '',
            'https://example.com/root/repo01',
            new \DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $webhook_data = new PostMergeRequestWebhookData(
            'merge_request',
            123,
            'https://example.com',
            2,
            "My title tuleap-42",
            'My description',
            'opened',
            (new \DateTimeImmutable())->setTimestamp(1611315112),
            10,
            'some_feature'
        );

        $this->tuleap_reference_retriever
            ->method('retrieveTuleapReference')
            ->with(42)
            ->willThrowException(new TuleapReferencedArtifactNotFoundException(42));

        $this->reference_manager
            ->expects(self::never())
            ->method('insertCrossReference');

        $this->logger
            ->method('info')
            ->willReturnCallback(
                function (string $message): void {
                    match ($message) {
                        '1 Tuleap references found in merge request 2',
                        '|_ Reference to Tuleap artifact #42 found, cross-reference will be added in project the GitLab repository is integrated in.' => true,
                    };
                }
            );

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with('Tuleap artifact #42 not found, no cross-reference will be added.');

        $this->creator->createCrossReferencesFromMergeRequest($webhook_data, $integration);
    }

    public function testItSavesReferenceInIntegratedProject(): void
    {
        $integration = new GitlabRepositoryIntegration(
            1,
            123654,
            'root/repo01',
            '',
            'https://example.com/root/repo01',
            new \DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $webhook_data = new PostMergeRequestWebhookData(
            'merge_request',
            123,
            'https://example.com',
            2,
            "My title tuleap-42",
            'My description tuleap-66',
            'opened',
            (new \DateTimeImmutable())->setTimestamp(1611315112),
            10,
            'some_feature'
        );

        $this->tuleap_reference_retriever
            ->expects(self::exactly(2))
            ->method('retrieveTuleapReference')
            ->willReturnMap(
                [
                    [
                        42,
                        new Reference(
                            42,
                            'key',
                            'desc',
                            'link',
                            'P',
                            'service_short_name',
                            'nature',
                            1,
                            110
                        ),
                    ],
                    [
                        66,
                        new Reference(
                            66,
                            'key',
                            'desc',
                            'link',
                            'P',
                            'service_short_name',
                            'nature',
                            1,
                            110
                        ),
                    ],
                ]
            );

        $this->reference_manager
            ->expects(self::exactly(2))
            ->method('insertCrossReference')
            ->willReturnCallback(
                fn(CrossReference $cross_reference): bool => match (true) {
                    $cross_reference->getRefSourceId() === 'root/repo01/2'
                    && $cross_reference->getRefSourceType() === 'plugin_gitlab_mr'
                    && $cross_reference->getRefSourceKey() === 'gitlab_mr'
                    && $cross_reference->getRefSourceGid() === 101
                    && $cross_reference->getRefTargetId() === 42
                    && $cross_reference->getRefTargetGid() === 110,
                        $cross_reference->getRefSourceId() === 'root/repo01/2'
                        && $cross_reference->getRefSourceType() === 'plugin_gitlab_mr'
                        && $cross_reference->getRefSourceKey() === 'gitlab_mr'
                        && $cross_reference->getRefSourceGid() === 101
                        && $cross_reference->getRefTargetId() === 66
                        && $cross_reference->getRefTargetGid() === 110 => true
                }
            );

        $this->logger
            ->method('info')
            ->willReturnCallback(
                function (string $message): void {
                    match ($message) {
                        '2 Tuleap references found in merge request 2',
                        '|_ Reference to Tuleap artifact #42 found, cross-reference will be added in project the GitLab repository is integrated in.',
                        '|  |_ Tuleap artifact #42 found',
                        '|_ Reference to Tuleap artifact #66 found, cross-reference will be added in project the GitLab repository is integrated in.',
                        '|  |_ Tuleap artifact #66 found' => true,
                    };
                }
            );

        $this->creator->createCrossReferencesFromMergeRequest($webhook_data, $integration);
    }
}
