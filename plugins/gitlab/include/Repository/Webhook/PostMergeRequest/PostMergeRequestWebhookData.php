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

namespace Tuleap\Gitlab\Repository\Webhook\PostMergeRequest;

use DateTimeImmutable;
use Override;
use Tuleap\Gitlab\Repository\Webhook\WebhookData;

/**
 * @psalm-immutable
 */
class PostMergeRequestWebhookData implements WebhookData
{
    public function __construct(
        private readonly string $event_name,
        private readonly int $gitlab_project_id,
        private readonly string $gitlab_url,
        private readonly int $merge_request_id,
        private readonly string $title,
        private readonly string $description,
        private readonly string $state,
        private readonly DateTimeImmutable $created_at,
        private readonly int $author_id,
        private readonly string $source_branch,
        private readonly string $gitlab_project_name,
        private readonly string $gitlab_project_description,
    ) {
    }

    #[Override]
    public function getEventName(): string
    {
        return $this->event_name;
    }

    #[Override]
    public function getGitlabProjectId(): int
    {
        return $this->gitlab_project_id;
    }

    #[Override]
    public function getGitlabWebUrl(): string
    {
        return $this->gitlab_url;
    }

    #[Override]
    public function getGitlabProjectName(): string
    {
        return $this->gitlab_project_name;
    }

    #[Override]
    public function getGitlabProjectDescription(): string
    {
        return $this->gitlab_project_description;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getMergeRequestId(): int
    {
        return $this->merge_request_id;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getCreatedAtDate(): DateTimeImmutable
    {
        return $this->created_at;
    }

    public function getAuthorId(): int
    {
        return $this->author_id;
    }

    public function getSourceBranch(): string
    {
        return $this->source_branch;
    }
}
