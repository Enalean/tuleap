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
use Tuleap\Gitlab\Repository\Webhook\WebhookData;

/**
 * @psalm-immutable
 */
class PostMergeRequestWebhookData implements WebhookData
{
    /**
     * @var string
     */
    private $event_name;
    /**
     * @var int
     */
    private $gitlab_project_id;
    /**
     * @var string
     */
    private $gitlab_url;
    /**
     * @var string
     */
    private $title;
    /**
     * @var string
     */
    private $description;
    /**
     * @var int
     */
    private $merge_request_id;
    /**
     * @var string
     */
    private $state;
    /**
     * @var DateTimeImmutable
     */
    private $created_at;
    /**
     * @var int
     */
    private $author_id;
    private string $source_branch;

    public function __construct(
        string $event_name,
        int $gitlab_project_id,
        string $gitlab_url,
        int $merge_request_id,
        string $title,
        string $description,
        string $state,
        DateTimeImmutable $created_at,
        int $author_id,
        string $source_branch,
    ) {
        $this->event_name        = $event_name;
        $this->gitlab_project_id = $gitlab_project_id;
        $this->gitlab_url        = $gitlab_url;
        $this->title             = $title;
        $this->description       = $description;
        $this->merge_request_id  = $merge_request_id;
        $this->state             = $state;
        $this->created_at        = $created_at;
        $this->author_id         = $author_id;
        $this->source_branch     = $source_branch;
    }

    #[\Override]
    public function getEventName(): string
    {
        return $this->event_name;
    }

    #[\Override]
    public function getGitlabProjectId(): int
    {
        return $this->gitlab_project_id;
    }

    #[\Override]
    public function getGitlabWebUrl(): string
    {
        return $this->gitlab_url;
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
