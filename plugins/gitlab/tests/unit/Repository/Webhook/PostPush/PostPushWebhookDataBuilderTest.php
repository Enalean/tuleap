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

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook\PostPush;

use Psr\Log\NullLogger;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class PostPushWebhookDataBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var PostPushWebhookDataBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new PostPushWebhookDataBuilder(
            new PostPushCommitWebhookDataExtractor(
                new NullLogger()
            )
        );
    }

    public function testItRetrievesPostPushWebhookData(): void
    {
        $webhook_data = [
            'project' => ['id' => 123456, 'web_url' => 'https://example.com/path/repo01'],
            'ref' => 'refs/heads/master',
            'checkout_sha' => '08596fb6360bcc951a06471c616f8bc77800d4f4',
            'before' => 'feff4ced04b237abb8b4a50b4160099313152c3d',
            'after' => '08596fb6360bcc951a06471c616f8bc77800d4f4',
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
                    'timestamp' => '2020-12-16T10:21:52+01:00',
                    'author' => [
                        'name' => 'John Snow',
                        'email' => 'john-snow@example.com',
                    ],
                ],
            ],
        ];

        $post_push_webhook_data = $this->builder->build(
            'Push Hook',
            123456,
            'https://example.com/path/repo01',
            $webhook_data
        );

        self::assertSame('Push Hook', $post_push_webhook_data->getEventName());
        self::assertSame(123456, $post_push_webhook_data->getGitlabProjectId());
        self::assertSame('https://example.com/path/repo01', $post_push_webhook_data->getGitlabWebUrl());
        self::assertInstanceOf(PostPushWebhookData::class, $post_push_webhook_data);
        self::assertCount(2, $post_push_webhook_data->getCommits());
        self::assertSame('08596fb6360bcc951a06471c616f8bc77800d4f4', $post_push_webhook_data->getCheckoutSha());
    }

    public function testItRetrievesPostPushWebhookDataAtBranchDeletion(): void
    {
        $webhook_data = [
            'project' => ['id' => 123456, 'web_url' => 'https://example.com/path/repo01'],
            'ref' => 'refs/heads/master',
            'checkout_sha' => null,
            'before' => '08596fb6360bcc951a06471c616f8bc77800d4f4',
            'after' => '0000000000000000000000000000000000000000',
            'commits' => [],
        ];

        $post_push_webhook_data = $this->builder->build(
            'Push Hook',
            123456,
            'https://example.com/path/repo01',
            $webhook_data
        );

        self::assertSame('Push Hook', $post_push_webhook_data->getEventName());
        self::assertSame(123456, $post_push_webhook_data->getGitlabProjectId());
        self::assertSame('https://example.com/path/repo01', $post_push_webhook_data->getGitlabWebUrl());
        self::assertInstanceOf(PostPushWebhookData::class, $post_push_webhook_data);
        self::assertEmpty($post_push_webhook_data->getCommits());
        self::assertNull($post_push_webhook_data->getCheckoutSha());
    }
}
