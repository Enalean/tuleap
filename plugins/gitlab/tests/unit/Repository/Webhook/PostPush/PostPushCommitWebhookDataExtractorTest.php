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

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Tuleap\Gitlab\Repository\Webhook\MissingKeyException;

class PostPushCommitWebhookDataExtractorTest extends TestCase
{
    /**
     * @var PostPushCommitWebhookDataExtractor
     */
    private $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extractor = new PostPushCommitWebhookDataExtractor(
            new NullLogger()
        );
    }

    public function testItThrowsAnExceptionIfCommitsKeyIsMissing(): void
    {
        $this->expectException(MissingKeyException::class);

        $webhook_content = [];
        $this->extractor->retrieveWebhookCommitsData($webhook_content);
    }

    public function testItThrowsAnExceptionIfACommitHasIdKeyIsMissing(): void
    {
        $this->expectException(MissingKeyException::class);

        $webhook_content = [
            'commits' => [
                ['message' => "commit 01"]
            ]
        ];
        $this->extractor->retrieveWebhookCommitsData($webhook_content);
    }

    public function testItThrowsAnExceptionIfACommitHasMessageKeyIsMissing(): void
    {
        $this->expectException(MissingKeyException::class);

        $webhook_content = [
            'commits' => [
                ['id' => "feff4ced04b237abb8b4a50b4160099313152c3c"]
            ]
        ];
        $this->extractor->retrieveWebhookCommitsData($webhook_content);
    }

    public function testItExtractsCommitData(): void
    {
        $webhook_content = [
            'commits' => [
                [
                    'id' => "feff4ced04b237abb8b4a50b4160099313152c3c",
                    'message' => "commit 01"
                ],
                [
                    'id' => "08596fb6360bcc951a06471c616f8bc77800d4f4",
                    'message' => "commit 02"
                ]
            ]
        ];

        $commits_data = $this->extractor->retrieveWebhookCommitsData($webhook_content);
        $this->assertCount(2, $commits_data);

        $first_commit  = $commits_data[0];
        $this->assertSame("feff4ced04b237abb8b4a50b4160099313152c3c", $first_commit->getSha1());
        $this->assertSame("commit 01", $first_commit->getMessage());

        $second_commit = $commits_data[1];
        $this->assertSame("08596fb6360bcc951a06471c616f8bc77800d4f4", $second_commit->getSha1());
        $this->assertSame("commit 02", $second_commit->getMessage());
    }
}
