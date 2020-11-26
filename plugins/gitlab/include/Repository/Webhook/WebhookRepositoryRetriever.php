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

namespace Tuleap\Gitlab\Repository\Webhook;

use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\GitlabRepositoryFactory;

class WebhookRepositoryRetriever
{
    /**
     * @var GitlabRepositoryFactory
     */
    private $gitlab_repository_factory;

    public function __construct(
        GitlabRepositoryFactory $gitlab_repository_factory
    ) {
        $this->gitlab_repository_factory = $gitlab_repository_factory;
    }

    /**
     * @throws RepositoryNotFoundException
     */
    public function retrieveRepositoryFromWebhookData(
        WebhookData $webhook_data
    ): GitlabRepository {
        $gitlab_repository = $this->getRepositoryObject($webhook_data);
        if ($gitlab_repository === null) {
            throw new RepositoryNotFoundException(
                $webhook_data->getGitlabProjectId(),
                $webhook_data->getGitlabWebUrl()
            );
        }

        return $gitlab_repository;
    }

    private function getRepositoryObject(WebhookData $webhook_data): ?GitlabRepository
    {
        return $this->gitlab_repository_factory->getGitlabRepositoryByInternalIdAndPath(
            $webhook_data->getGitlabProjectId(),
            $webhook_data->getGitlabWebUrl()
        );
    }
}
