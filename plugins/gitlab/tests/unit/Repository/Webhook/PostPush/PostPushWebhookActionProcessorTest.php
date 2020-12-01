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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Commits\CommitTuleapReferencesParser;

final class PostPushWebhookActionProcessorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var PostPushWebhookActionProcessor
     */
    private $processor;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = Mockery::mock(LoggerInterface::class);

        $this->processor = new PostPushWebhookActionProcessor(
            new CommitTuleapReferencesParser(),
            $this->logger
        );
    }

    public function testItProcessesActionsForPostPushWebhook(): void
    {
        $webhook_data = new PostPushWebhookData(
            'push',
            123654,
            'https://example.com/root/repo01',
            [
                new PostPushCommitWebhookData(
                    'feff4ced04b237abb8b4a50b4160099313152c3c',
                    'commit TULEAP-123 01'
                )
            ]
        );

        $this->logger
            ->shouldReceive('info')
            ->with("1 Tuleap references found in commit feff4ced04b237abb8b4a50b4160099313152c3c")
            ->once();
        $this->logger
            ->shouldReceive('info')
            ->with("Reference to Tuleap artifact #123 found.")
            ->once();

        $this->processor->process($webhook_data);
    }
}
