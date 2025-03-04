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

use Psr\Log\NullLogger;
use Tuleap\Gitlab\Repository\Webhook\MissingKeyException;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class PostPushCommitWebhookDataExtractorTest extends \Tuleap\Test\PHPUnit\TestCase
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
        $this->expectExceptionMessage('key commits is missing');

        $webhook_content = [
            'ref' => 'master',
        ];
        $this->extractor->retrieveWebhookCommitsData($webhook_content);
    }

    public function testItThrowsAnExceptionIfRefKeyIsMissing(): void
    {
        $this->expectException(MissingKeyException::class);
        $this->expectExceptionMessage('key ref is missing');

        $webhook_content = [
            'commits' => [
                ['message' => 'commit 01'],
            ],
        ];
        $this->extractor->retrieveWebhookCommitsData($webhook_content);
    }

    public function testItThrowsAnExceptionIfACommitHasIdKeyIsMissing(): void
    {
        $this->expectException(MissingKeyException::class);
        $this->expectExceptionMessage('key id is missing');

        $webhook_content = [
            'ref' => 'master',
            'commits' => [
                ['message' => 'commit 01'],
            ],
        ];
        $this->extractor->retrieveWebhookCommitsData($webhook_content);
    }

    public function testItThrowsAnExceptionIfACommitHasTitleKeyMissing(): void
    {
        $this->expectException(MissingKeyException::class);
        $this->expectExceptionMessage('key title is missing');

        $webhook_content = [
            'ref' => 'master',
            'commits' => [
                [
                    'id' => 'feff4ced04b237abb8b4a50b4160099313152c3c',
                    'message' => 'commit 01',
                ],
            ],
        ];
        $this->extractor->retrieveWebhookCommitsData($webhook_content);
    }

    public function testItThrowsAnExceptionIfACommitHasMessageKeyIsMissing(): void
    {
        $this->expectException(MissingKeyException::class);
        $this->expectExceptionMessage('key message is missing');

        $webhook_content = [
            'ref' => 'master',
            'commits' => [
                [
                    'id' => 'feff4ced04b237abb8b4a50b4160099313152c3c',
                    'title' => 'commit 01',
                ],
            ],
        ];
        $this->extractor->retrieveWebhookCommitsData($webhook_content);
    }

    public function testItThrowsAnExceptionWhenCommitDateKeyIsMissing(): void
    {
        $this->expectException(MissingKeyException::class);
        $this->expectExceptionMessage('key timestamp is missing');

        $webhook_content = [
            'ref' => 'master',
            'commits' => [
                [
                    'id' => 'feff4ced04b237abb8b4a50b4160099313152c3c',
                    'title' => 'commit 01',
                    'message' => 'commit 01',
                    'author' => [
                        'name' => 'John Snow',
                        'email' => 'john-snow@example.com',
                    ],
                ],
            ],
        ];
        $this->extractor->retrieveWebhookCommitsData($webhook_content);
    }

    public function testItThrowsAnExceptionWhenCommitAuthorKeyIsMissing(): void
    {
        $this->expectException(MissingKeyException::class);
        $this->expectExceptionMessage('key author is missing');

        $webhook_content = [
            'ref' => 'master',
            'commits' => [
                [
                    'id' => 'feff4ced04b237abb8b4a50b4160099313152c3c',
                    'title' => 'commit 01',
                    'message' => 'commit 01',
                    'timestamp' => '2020-12-16T10:21:50+01:00',
                ],
            ],
        ];
        $this->extractor->retrieveWebhookCommitsData($webhook_content);
    }

    public function testItThrowsAnExceptionWhenCommitAuthorNameKeyIsMissing(): void
    {
        $this->expectException(MissingKeyException::class);
        $this->expectExceptionMessage('key name in author is missing');

        $webhook_content = [
            'ref' => 'master',
            'commits' => [
                [
                    'id' => 'feff4ced04b237abb8b4a50b4160099313152c3c',
                    'title' => 'commit 01',
                    'message' => 'commit 01',
                    'timestamp' => '2020-12-16T10:21:50+01:00',
                    'author' => [
                        'email' => 'john-snow@example.com',
                    ],
                ],
            ],
        ];
        $this->extractor->retrieveWebhookCommitsData($webhook_content);
    }

    public function testItThrowsAnExceptionWhenCommitAuthorEmailKeyIsMissing(): void
    {
        $this->expectException(MissingKeyException::class);
        $this->expectExceptionMessage('key email in author is missing');

        $webhook_content = [
            'ref' => 'master',
            'commits' => [
                [
                    'id' => 'feff4ced04b237abb8b4a50b4160099313152c3c',
                    'title' => 'commit 01',
                    'message' => 'commit 01',
                    'timestamp' => '2020-12-16T10:21:50+01:00',
                    'author' => [
                        'name' => 'John Snow',
                    ],
                ],
            ],
        ];
        $this->extractor->retrieveWebhookCommitsData($webhook_content);
    }

    public function testItExtractsCommitData(): void
    {
        $webhook_content = [
            'ref' => 'master',
            'commits' => [
                [
                    'id' => 'feff4ced04b237abb8b4a50b4160099313152c3c',
                    'title' => 'commit 01',
                    'message' => 'commit 01',
                    'timestamp' => '2020-12-16T10:21:50+01:00',
                    'author' => [
                        'name' => 'John Snow',
                        'email' => 'john-snow@example.com',
                    ],
                ],
                [
                    'id' => '08596fb6360bcc951a06471c616f8bc77800d4f4',
                    'title' => 'commit 02',
                    'message' => 'commit 02',
                    'timestamp' => '2020-12-16T10:21:50+01:00',
                    'author' => [
                        'name' => 'The Night King',
                        'email' => 'the-night-king@example.com',
                    ],
                ],
            ],
        ];

        $commits_data = $this->extractor->retrieveWebhookCommitsData($webhook_content);
        self::assertCount(2, $commits_data);

        $first_commit = $commits_data[0];
        self::assertSame('feff4ced04b237abb8b4a50b4160099313152c3c', $first_commit->getSha1());
        self::assertSame('commit 01', $first_commit->getTitle());
        self::assertSame('commit 01', $first_commit->getMessage());
        self::assertSame('master', $first_commit->getBranchName());
        self::assertSame(1608110510, $first_commit->getCommitDate());
        self::assertSame('John Snow', $first_commit->getAuthorName());
        self::assertSame('john-snow@example.com', $first_commit->getAuthorEmail());

        $second_commit = $commits_data[1];
        self::assertSame('08596fb6360bcc951a06471c616f8bc77800d4f4', $second_commit->getSha1());
        self::assertSame('commit 02', $second_commit->getTitle());
        self::assertSame('commit 02', $second_commit->getMessage());
        self::assertSame('master', $first_commit->getBranchName());
        self::assertSame(1608110510, $second_commit->getCommitDate());
        self::assertSame('The Night King', $second_commit->getAuthorName());
        self::assertSame('the-night-king@example.com', $second_commit->getAuthorEmail());
    }
}
