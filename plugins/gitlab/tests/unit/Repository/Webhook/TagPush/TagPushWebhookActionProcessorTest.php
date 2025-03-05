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
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TagPushWebhookActionProcessorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TagPushWebhookDeleteAction
     */
    private $push_webhook_delete_action;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TagPushWebhookCreateAction
     */
    private $push_webhook_create_action;

    private TagPushWebhookActionProcessor $action_processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->push_webhook_delete_action = $this->createMock(TagPushWebhookDeleteAction::class);
        $this->push_webhook_create_action = $this->createMock(TagPushWebhookCreateAction::class);

        $this->action_processor = new TagPushWebhookActionProcessor(
            $this->push_webhook_create_action,
            $this->push_webhook_delete_action,
            new DBTransactionExecutorPassthrough()
        );
    }

    public function testItAsksForDeletionIfTagIsDeleted(): void
    {
        $integration = new GitlabRepositoryIntegration(
            1,
            12587,
            'root/repo01',
            '',
            'https://example.com/root/repo01',
            new DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $tag_webhook_data = new TagPushWebhookData(
            'Tag Push Event',
            12587,
            'https://example.com',
            'refs/tags/v1.0.2',
            'before',
            '0000000000000000000000000000000000000000',
        );

        $this->push_webhook_create_action->expects(self::never())->method('createTagReferences');

        $this->push_webhook_delete_action
            ->expects(self::once())
            ->method('deleteTagReferences')
            ->with(
                $integration,
                $tag_webhook_data
            );

        $this->action_processor->process(
            $integration,
            $tag_webhook_data
        );
    }

    public function testItAsksForCreationIfTagIsCreated(): void
    {
        $integration = new GitlabRepositoryIntegration(
            1,
            12587,
            'root/repo01',
            '',
            'https://example.com/root/repo01',
            new DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $tag_webhook_data = new TagPushWebhookData(
            'Tag Push Event',
            12587,
            'https://example.com',
            'refs/tags/v1.0.2',
            '0000000000000000000000000000000000000000',
            'after'
        );

        $this->push_webhook_create_action
            ->expects(self::once())
            ->method('createTagReferences')
            ->with(
                $integration,
                $tag_webhook_data
            );

        $this->push_webhook_delete_action->expects(self::never())->method('deleteTagReferences');

        $this->action_processor->process(
            $integration,
            $tag_webhook_data
        );
    }

    public function testItAsksForDeletionThenCreationIfTagIsMoved(): void
    {
        $integration = new GitlabRepositoryIntegration(
            1,
            12587,
            'root/repo01',
            '',
            'https://example.com/root/repo01',
            new DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $tag_webhook_data = new TagPushWebhookData(
            'Tag Push Event',
            12587,
            'https://example.com',
            'refs/tags/v1.0.2',
            'before',
            'after'
        );

        $this->push_webhook_delete_action
            ->expects(self::once())
            ->method('deleteTagReferences')
            ->with(
                $integration,
                $tag_webhook_data
            );

        $this->push_webhook_create_action
            ->expects(self::once())
            ->method('createTagReferences')
            ->with(
                $integration,
                $tag_webhook_data
            );

        $this->action_processor->process(
            $integration,
            $tag_webhook_data
        );
    }
}
