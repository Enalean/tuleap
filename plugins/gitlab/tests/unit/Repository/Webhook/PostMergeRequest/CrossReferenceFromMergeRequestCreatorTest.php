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
use Psr\Log\LoggerInterface;
use Reference;
use Tuleap\Gitlab\Reference\TuleapReferencedArtifactNotFoundException;
use Tuleap\Gitlab\Reference\TuleapReferenceNotFoundException;
use Tuleap\Gitlab\Reference\TuleapReferenceRetriever;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReferencesParser;

class CrossReferenceFromMergeRequestCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var CrossReferenceFromMergeRequestCreator
     */
    private $creator;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TuleapReferenceRetriever
     */
    private $tuleap_reference_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\ReferenceManager
     */
    private $reference_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;

    protected function setUp(): void
    {
        $this->tuleap_reference_retriever = Mockery::mock(TuleapReferenceRetriever::class);
        $this->reference_manager          = Mockery::mock(\ReferenceManager::class);
        $this->logger                     = Mockery::mock(LoggerInterface::class);

        $this->creator = new CrossReferenceFromMergeRequestCreator(
            new TuleapReferencesFromMergeRequestDataExtractor(new WebhookTuleapReferencesParser()),
            $this->tuleap_reference_retriever,
            $this->reference_manager,
            $this->logger,
        );
    }

    public function testItDoesNothingIfThereIsNoReferencesInMergeRequestData(): void
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
            "My title",
            'My description',
            'opened',
            (new \DateTimeImmutable())->setTimestamp(1611315112),
            10
        );

        $this->reference_manager
            ->shouldReceive('insertCrossReference')
            ->never();

        $this->logger
            ->shouldReceive('info')
            ->with('0 Tuleap references found in merge request 2')
            ->once();

        $this->creator->createCrossReferencesFromMergeRequest($webhook_data, $repository);
    }

    public function testItDoesNothingIfTheReferenceIsNotFound(): void
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
            "My title tuleap-42",
            'My description',
            'opened',
            (new \DateTimeImmutable())->setTimestamp(1611315112),
            10
        );

        $this->tuleap_reference_retriever
            ->shouldReceive('retrieveTuleapReference')
            ->with(42)
            ->andThrow(TuleapReferenceNotFoundException::class);

        $this->reference_manager
            ->shouldReceive('insertCrossReference')
            ->never();

        $this->logger
            ->shouldReceive('info')
            ->with('1 Tuleap references found in merge request 2')
            ->once();
        $this->logger
            ->shouldReceive('info')
            ->with(
                '|_ Reference to Tuleap artifact #42 found, cross-reference will be added in project the GitLab repository is integrated in.'
            )
            ->once();
        $this->logger
            ->shouldReceive('error')
            ->with(
                'No reference found with the keyword \'art\', and this must not happen. If you read this, this is really bad.'
            )
            ->once();

        $this->creator->createCrossReferencesFromMergeRequest($webhook_data, $repository);
    }

    public function testItDoesNothingIfTheReferencedArtifactIsNotFound(): void
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
            "My title tuleap-42",
            'My description',
            'opened',
            (new \DateTimeImmutable())->setTimestamp(1611315112),
            10
        );

        $this->tuleap_reference_retriever
            ->shouldReceive('retrieveTuleapReference')
            ->with(42)
            ->andThrow(new TuleapReferencedArtifactNotFoundException(42));

        $this->reference_manager
            ->shouldReceive('insertCrossReference')
            ->never();

        $this->logger
            ->shouldReceive('info')
            ->with('1 Tuleap references found in merge request 2')
            ->once();
        $this->logger
            ->shouldReceive('info')
            ->with(
                '|_ Reference to Tuleap artifact #42 found, cross-reference will be added in project the GitLab repository is integrated in.'
            )
            ->once();
        $this->logger
            ->shouldReceive('error')
            ->with('Tuleap artifact #42 not found, no cross-reference will be added.')
            ->once();

        $this->creator->createCrossReferencesFromMergeRequest($webhook_data, $repository);
    }

    public function testItSavesReferenceInIntegratedProject(): void
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
            "My title tuleap-42",
            'My description tuleap-66',
            'opened',
            (new \DateTimeImmutable())->setTimestamp(1611315112),
            10
        );

        $this->tuleap_reference_retriever
            ->shouldReceive('retrieveTuleapReference')
            ->with(42)
            ->andReturn(
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
                )
            );

        $this->tuleap_reference_retriever
            ->shouldReceive('retrieveTuleapReference')
            ->with(66)
            ->andReturn(
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
                )
            );

        $this->reference_manager
            ->shouldReceive('insertCrossReference')
            ->with(
                Mockery::on(
                    function (\CrossReference $cross_reference) {
                        return $cross_reference->getRefSourceId() === 'root/repo01/2'
                            && $cross_reference->getRefSourceType() === 'plugin_gitlab_mr'
                            && $cross_reference->getRefSourceKey() === 'gitlab_mr'
                            && $cross_reference->getRefSourceGid() === 101
                            && $cross_reference->getRefTargetId() === 42
                            && $cross_reference->getRefTargetGid() === 110;
                    }
                )
            )
            ->once();

        $this->reference_manager
            ->shouldReceive('insertCrossReference')
            ->with(
                Mockery::on(
                    function (\CrossReference $cross_reference) {
                        return $cross_reference->getRefSourceId() === 'root/repo01/2'
                            && $cross_reference->getRefSourceType() === 'plugin_gitlab_mr'
                            && $cross_reference->getRefSourceKey() === 'gitlab_mr'
                            && $cross_reference->getRefSourceGid() === 101
                            && $cross_reference->getRefTargetId() === 66
                            && $cross_reference->getRefTargetGid() === 110;
                    }
                )
            )
            ->once();

        $this->logger
            ->shouldReceive('info')
            ->with('2 Tuleap references found in merge request 2')
            ->once();
        $this->logger
            ->shouldReceive('info')
            ->with(
                '|_ Reference to Tuleap artifact #42 found, cross-reference will be added in project the GitLab repository is integrated in.'
            )
            ->once();
        $this->logger
            ->shouldReceive('info')
            ->with(
                '|  |_ Tuleap artifact #42 found'
            )
            ->once();
        $this->logger
            ->shouldReceive('info')
            ->with(
                '|_ Reference to Tuleap artifact #66 found, cross-reference will be added in project the GitLab repository is integrated in.'
            )
            ->once();
        $this->logger
            ->shouldReceive('info')
            ->with(
                '|  |_ Tuleap artifact #66 found'
            )
            ->once();

        $this->creator->createCrossReferencesFromMergeRequest($webhook_data, $repository);
    }
}
