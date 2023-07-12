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

use Psr\Log\LoggerInterface;
use Tuleap\Gitlab\Reference\Tag\GitlabTagReference;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Reference\CrossReferenceManager;

class TagPushWebhookDeleteAction
{
    public function __construct(
        private readonly TagInfoDao $tag_info_dao,
        private readonly CrossReferenceManager $cross_reference_manager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function deleteTagReferences(
        GitlabRepositoryIntegration $gitlab_repository_integration,
        TagPushWebhookData $tag_push_webhook_data,
    ): void {
        $tag_name = $tag_push_webhook_data->getTagName();

        $this->logger->info(
            "Tag $tag_name has been deleted, all references will be removed from database for the integration #" . $gitlab_repository_integration->getId()
        );

        $this->cross_reference_manager->deleteEntity(
            $gitlab_repository_integration->getName() . '/' . $tag_name,
            GitlabTagReference::NATURE_NAME,
            (int) $gitlab_repository_integration->getProject()->getID()
        );

        $this->tag_info_dao->deleteTagInGitlabRepository(
            $gitlab_repository_integration->getId(),
            $tag_name
        );

        $this->logger->info("Tag data for $tag_name deleted in database for the integration #" . $gitlab_repository_integration->getId());
    }
}
