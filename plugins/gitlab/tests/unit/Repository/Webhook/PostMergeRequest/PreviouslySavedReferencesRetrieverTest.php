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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
use Tuleap\Gitlab\Reference\TuleapReferencedArtifactNotFoundException;
use Tuleap\Gitlab\Reference\TuleapReferenceNotFoundException;
use Tuleap\Gitlab\Reference\TuleapReferenceRetriever;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReferencesParser;

class PreviouslySavedReferencesRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var PreviouslySavedReferencesRetriever
     */
    private $retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TuleapReferenceRetriever
     */
    private $tuleap_reference_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|MergeRequestTuleapReferenceDao
     */
    private $dao;

    protected function setUp(): void
    {
        $this->tuleap_reference_retriever = Mockery::mock(TuleapReferenceRetriever::class);
        $this->dao                        = Mockery::mock(MergeRequestTuleapReferenceDao::class);

        $this->retriever = new PreviouslySavedReferencesRetriever(
            new TuleapReferencesFromMergeRequestDataExtractor(
                new WebhookTuleapReferencesParser()
            ),
            $this->tuleap_reference_retriever,
            $this->dao,
        );
    }

    public function testItReturnsEmptyArrayIfNothingFoundInDatabase(): void
    {
        $repository = new GitlabRepository(
            1,
            123654,
            'root/repo01',
            '',
            'https://example.com/root/repo01',
            new \DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $webhook_data = new PostMergeRequestWebhookData(
            'merge_request',
            123,
            'https://example.com',
            2,
            "My Title TULEAP-58",
            'TULEAP-666 TULEAP-45',
            'opened',
            (new \DateTimeImmutable())->setTimestamp(1611315112),
            10
        );

        $this->dao
            ->shouldReceive('searchMergeRequestInRepositoryWithId')
            ->with(1, 2)
            ->andReturn([]);

        self::assertEmpty(
            $this->retriever->retrievePreviousReferences($webhook_data, $repository)
        );
    }

    public function testItReturnsEmptyArrayIfNoReferencesAreFoundInThePreviouslySavedData(): void
    {
        $repository = new GitlabRepository(
            1,
            123654,
            'root/repo01',
            '',
            'https://example.com/root/repo01',
            new \DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $webhook_data = new PostMergeRequestWebhookData(
            'merge_request',
            123,
            'https://example.com',
            2,
            "My Title TULEAP-58",
            'TULEAP-666 TULEAP-45',
            'opened',
            (new \DateTimeImmutable())->setTimestamp(1611315112),
            10
        );

        $this->dao
            ->shouldReceive('searchMergeRequestInRepositoryWithId')
            ->with(1, 2)
            ->andReturn(
                [
                    'title'       => 'Title of merge request',
                    'description' => 'Description of merge request',
                ]
            );

        self::assertEmpty(
            $this->retriever->retrievePreviousReferences($webhook_data, $repository)
        );
    }

    public function testItReturnsEmptyArrayIfReferenceIsNotFound(): void
    {
        $repository = new GitlabRepository(
            1,
            123654,
            'root/repo01',
            '',
            'https://example.com/root/repo01',
            new \DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $webhook_data = new PostMergeRequestWebhookData(
            'merge_request',
            123,
            'https://example.com',
            2,
            "My Title TULEAP-58",
            'TULEAP-666 TULEAP-45',
            'opened',
            (new \DateTimeImmutable())->setTimestamp(1611315112),
            10
        );

        $this->dao
            ->shouldReceive('searchMergeRequestInRepositoryWithId')
            ->with(1, 2)
            ->andReturn(
                [
                    'title'       => 'Title of merge request TULEAP-8',
                    'description' => 'Description of merge request',
                ]
            );

        $this->tuleap_reference_retriever
            ->shouldReceive('retrieveTuleapReference')
            ->with(8)
            ->once()
            ->andThrow(TuleapReferenceNotFoundException::class);

        self::assertEmpty(
            $this->retriever->retrievePreviousReferences($webhook_data, $repository)
        );
    }

    public function testItReturnsEmptyArrayIfReferencedArtifactIsNotFound(): void
    {
        $repository = new GitlabRepository(
            1,
            123654,
            'root/repo01',
            '',
            'https://example.com/root/repo01',
            new \DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $webhook_data = new PostMergeRequestWebhookData(
            'merge_request',
            123,
            'https://example.com',
            2,
            "My Title TULEAP-58",
            'TULEAP-666 TULEAP-45',
            'opened',
            (new \DateTimeImmutable())->setTimestamp(1611315112),
            10
        );

        $this->dao
            ->shouldReceive('searchMergeRequestInRepositoryWithId')
            ->with(1, 2)
            ->andReturn(
                [
                    'title'       => 'Title of merge request TULEAP-8',
                    'description' => 'Description of merge request',
                ]
            );

        $this->tuleap_reference_retriever
            ->shouldReceive('retrieveTuleapReference')
            ->with(8)
            ->once()
            ->andThrow(Mockery::mock(TuleapReferencedArtifactNotFoundException::class));

        self::assertEmpty(
            $this->retriever->retrievePreviousReferences($webhook_data, $repository)
        );
    }

    public function testItReturnsPreviousReferences(): void
    {
        $repository = new GitlabRepository(
            1,
            123654,
            'root/repo01',
            '',
            'https://example.com/root/repo01',
            new \DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $webhook_data = new PostMergeRequestWebhookData(
            'merge_request',
            123,
            'https://example.com',
            2,
            "My Title TULEAP-58",
            'TULEAP-666 TULEAP-45',
            'opened',
            (new \DateTimeImmutable())->setTimestamp(1611315112),
            10
        );

        $this->dao
            ->shouldReceive('searchMergeRequestInRepositoryWithId')
            ->with(1, 2)
            ->andReturn(
                [
                    'title'       => 'Title of merge request TULEAP-8',
                    'description' => 'Description of merge request TULEAP-58',
                ]
            );

        $this->tuleap_reference_retriever
            ->shouldReceive('retrieveTuleapReference')
            ->with(8)
            ->once();

        $this->tuleap_reference_retriever
            ->shouldReceive('retrieveTuleapReference')
            ->with(58)
            ->once();

        self::assertEquals(
            [
                new WebhookTuleapReference(8),
                new WebhookTuleapReference(58),
            ],
            $this->retriever->retrievePreviousReferences($webhook_data, $repository)
        );
    }
}
