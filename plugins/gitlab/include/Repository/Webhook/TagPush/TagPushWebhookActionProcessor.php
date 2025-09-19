<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Repository\Webhook\TagPush;

use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;

class TagPushWebhookActionProcessor
{
    private const string NO_REFERENCE = '0000000000000000000000000000000000000000';

    /**
     * @var TagPushWebhookDeleteAction
     */
    private $push_webhook_delete_action;
    /**
     * @var TagPushWebhookCreateAction
     */
    private $push_webhook_create_action;
    /**
     * @var DBTransactionExecutor
     */
    private $db_transaction_executor;

    public function __construct(
        TagPushWebhookCreateAction $push_webhook_create_action,
        TagPushWebhookDeleteAction $push_webhook_delete_action,
        DBTransactionExecutor $db_transaction_executor,
    ) {
        $this->push_webhook_create_action = $push_webhook_create_action;
        $this->push_webhook_delete_action = $push_webhook_delete_action;
        $this->db_transaction_executor    = $db_transaction_executor;
    }

    public function process(GitlabRepositoryIntegration $gitlab_repository_integration, TagPushWebhookData $tag_push_webhook_data): void
    {
        $this->db_transaction_executor->execute(function () use ($gitlab_repository_integration, $tag_push_webhook_data) {
            if ($tag_push_webhook_data->getAfter() === self::NO_REFERENCE) {
                $this->push_webhook_delete_action->deleteTagReferences(
                    $gitlab_repository_integration,
                    $tag_push_webhook_data
                );
                return;
            } elseif ($tag_push_webhook_data->getBefore() === self::NO_REFERENCE) {
                $this->push_webhook_create_action->createTagReferences(
                    $gitlab_repository_integration,
                    $tag_push_webhook_data
                );
                return;
            } else {
                $this->push_webhook_delete_action->deleteTagReferences(
                    $gitlab_repository_integration,
                    $tag_push_webhook_data
                );
                $this->push_webhook_create_action->createTagReferences(
                    $gitlab_repository_integration,
                    $tag_push_webhook_data
                );
                return;
            }
        });
    }
}
