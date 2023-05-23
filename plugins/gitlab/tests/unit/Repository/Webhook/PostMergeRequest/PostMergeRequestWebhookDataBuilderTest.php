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

namespace Tuleap\Gitlab\Repository\Webhook\PostMergeRequest;

use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\Gitlab\Repository\Webhook\MissingKeyException;

final class PostMergeRequestWebhookDataBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TestLogger $logger;
    private PostMergeRequestWebhookDataBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = new TestLogger();

        $this->builder = new PostMergeRequestWebhookDataBuilder(
            $this->logger
        );
    }

    public function testItThrowsAnExceptionIfObjectAttributesKeyIsMissing(): void
    {
        $this->expectException(MissingKeyException::class);
        $this->expectExceptionMessage("key object_attributes is missing");

        $webhook_content = [];
        $this->builder->build("Merge Request Hook", 123, "https://example.com", $webhook_content);
    }

    public function testItThrowsAnExceptionIfMergeRequestIdKeyIsMissing(): void
    {
        $this->expectException(MissingKeyException::class);
        $this->expectExceptionMessage("key iid in object_attributes is missing");

        $webhook_content = ['object_attributes' => []];
        $this->builder->build("Merge Request Hook", 123, "https://example.com", $webhook_content);
    }

    public function testItThrowsAnExceptionIfMergeRequestTitleKeyIsMissing(): void
    {
        $this->expectException(MissingKeyException::class);
        $this->expectExceptionMessage("key title in object_attributes is missing");

        $webhook_content = ['object_attributes' => ["iid" => 1]];
        $this->builder->build("Merge Request Hook", 123, "https://example.com", $webhook_content);
    }

    public function testItThrowsAnExceptionIfMergeRequestDescriptionKeyIsMissing(): void
    {
        $this->expectException(MissingKeyException::class);
        $this->expectExceptionMessage("key description in object_attributes is missing");

        $webhook_content = ['object_attributes' => ["iid" => 1, "title" => "My Title"]];
        $this->builder->build("Merge Request Hook", 123, "https://example.com", $webhook_content);
    }

    public function testItThrowsAnExceptionIfMergeRequestCreatedAtKeyIsMissing(): void
    {
        $this->expectException(MissingKeyException::class);
        $this->expectExceptionMessage("key created_at in object_attributes is missing");

        $webhook_content = [
            'object_attributes' => [
                'iid'         => 1,
                'title'       => 'My Title',
                'description' => 'My description',
                'state'       => 'closed',
            ],
        ];
        $this->builder->build("Merge Request Hook", 123, "https://example.com", $webhook_content);
    }

    public function testItThrowsAnExceptionIfMergeRequestAuthorIdKeyIsMissing(): void
    {
        $this->expectException(MissingKeyException::class);
        $this->expectExceptionMessage("key author_id in object_attributes is missing");

        $webhook_content = [
            'object_attributes' => [
                'iid'         => 1,
                'title'       => 'My Title',
                'description' => 'My description',
                'state'       => 'closed',
                'created_at'  => '2021-01-12 13:49:35 UTC',
            ],
        ];
        $this->builder->build("Merge Request Hook", 123, "https://example.com", $webhook_content);
    }

    public function testThrowsAnExceptionIfMergeRequestSourceBranchKeyIsMissing(): void
    {
        $this->expectException(MissingKeyException::class);
        $this->expectExceptionMessage("key source_branch in object_attributes is missing");

        $webhook_content = [
            'object_attributes' => [
                'iid'         => 1,
                'title'       => 'My Title',
                'description' => 'My description',
                'state'       => 'closed',
                'created_at'  => '2021-01-12 13:49:35 UTC',
                'author_id'   => 10,
            ],
        ];
        $this->builder->build("Merge Request Hook", 123, "https://example.com", $webhook_content);
    }

    public function testItReturnsPostMergeRequestWebhookData(): void
    {
        $webhook_content = [
            'object_attributes' => [
                'iid'         => 1,
                'title'       => 'My Title',
                'description' => 'My description',
                'state'       => 'closed',
                'created_at'  => '2021-01-12 13:49:35 UTC',
                'author_id'   => 10,
                'source_branch' => 'some_feature',
            ],
        ];

        $this->builder->build("Merge Request Hook", 123, "https://example.com", $webhook_content);

        self::assertTrue(
            $this->logger->hasDebugThatContains(
                "Webhook merge request with id 1 retrieved.\nTitle: My Title\nSource branch: some_feature\nDescription: My description\n"
            )
        );
    }

    public function testItReturnsPostMergeRequestWebhookDataWithNullValues(): void
    {
        $webhook_content = [
            'object_attributes' => [
                'iid'         => 1,
                'title'       => 'My Title',
                'description' => null,
                'state'       => 'closed',
                'created_at'  => '2021-01-12 13:49:35 UTC',
                'author_id'   => 10,
                'source_branch' => 'some_feature',
            ],
        ];

        $this->builder->build("Merge Request Hook", 123, "https://example.com", $webhook_content);

        self::assertTrue(
            $this->logger->hasDebugThatContains(
                "Webhook merge request with id 1 retrieved.\nTitle: My Title\nSource branch: some_feature\nDescription: \n"
            )
        );
    }
}
