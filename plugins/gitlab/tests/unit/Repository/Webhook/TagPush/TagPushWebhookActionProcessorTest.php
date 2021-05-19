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

namespace Tuleap\Gitlab\Repository\Webhook\TagPush;

use DateTimeImmutable;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

class TagPushWebhookActionProcessorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var TagPushWebhookActionProcessor
     */
    private $action_processor;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TagPushWebhookDeleteAction
     */
    private $push_webhook_delete_action;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TagPushWebhookCreateAction
     */
    private $push_webhook_create_action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->push_webhook_delete_action = Mockery::mock(TagPushWebhookDeleteAction::class);
        $this->push_webhook_create_action = Mockery::mock(TagPushWebhookCreateAction::class);

        $this->action_processor = new TagPushWebhookActionProcessor(
            $this->push_webhook_create_action,
            $this->push_webhook_delete_action,
            new DBTransactionExecutorPassthrough()
        );
    }

    public function testItAsksForDeletionIfTagIsDeleted(): void
    {
        $gitlab_repository = new GitlabRepository(
            1,
            12587,
            "root/repo01",
            "",
            "https://example.com/root/repo01",
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $tag_webhook_data = new TagPushWebhookData(
            "Tag Push Event",
            12587,
            "https://example.com",
            "refs/tags/v1.0.2",
            "before",
            "0000000000000000000000000000000000000000",
        );

        $this->push_webhook_create_action->shouldNotReceive('createTagReferences');

        $this->push_webhook_delete_action->shouldReceive('deleteTagReferences')
            ->once()
            ->with(
                $gitlab_repository,
                $tag_webhook_data
            );

        $this->action_processor->process(
            $gitlab_repository,
            $tag_webhook_data
        );
    }

    public function testItAsksForCreationIfTagIsCreated(): void
    {
        $gitlab_repository = new GitlabRepository(
            1,
            12587,
            "root/repo01",
            "",
            "https://example.com/root/repo01",
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $tag_webhook_data = new TagPushWebhookData(
            "Tag Push Event",
            12587,
            "https://example.com",
            "refs/tags/v1.0.2",
            "0000000000000000000000000000000000000000",
            "after"
        );

        $this->push_webhook_create_action->shouldReceive('createTagReferences')
            ->once()
            ->with(
                $gitlab_repository,
                $tag_webhook_data
            );

        $this->push_webhook_delete_action->shouldNotReceive('deleteTagReferences');

        $this->action_processor->process(
            $gitlab_repository,
            $tag_webhook_data
        );
    }

    public function testItAsksForDeletionThenCreationIfTagIsMoved(): void
    {
        $gitlab_repository = new GitlabRepository(
            1,
            12587,
            "root/repo01",
            "",
            "https://example.com/root/repo01",
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $tag_webhook_data = new TagPushWebhookData(
            "Tag Push Event",
            12587,
            "https://example.com",
            "refs/tags/v1.0.2",
            "before",
            "after"
        );

        $this->push_webhook_delete_action->shouldReceive('deleteTagReferences')
            ->once()
            ->with(
                $gitlab_repository,
                $tag_webhook_data
            );

        $this->push_webhook_create_action->shouldReceive('createTagReferences')
            ->once()
            ->with(
                $gitlab_repository,
                $tag_webhook_data
            );

        $this->action_processor->process(
            $gitlab_repository,
            $tag_webhook_data
        );
    }
}
