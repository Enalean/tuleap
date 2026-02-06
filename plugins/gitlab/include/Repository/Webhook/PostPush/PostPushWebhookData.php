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

use Override;
use Tuleap\Gitlab\Repository\Webhook\WebhookData;

/**
 * @psalm-immutable
 */
final readonly class PostPushWebhookData implements WebhookData
{
    /**
     * @param PostPushCommitWebhookData[] $commits
     */
    public function __construct(
        private string $event_name,
        private int $gitlab_project_id,
        private string $gitlab_web_url,
        private ?string $checkout_sha,
        private string $reference,
        private array $commits,
        private string $gitlab_project_name,
        private string $gitlab_project_description,
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
        return $this->gitlab_web_url;
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

    /**
     * @return PostPushCommitWebhookData[]
     */
    public function getCommits(): array
    {
        return $this->commits;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getCheckoutSha(): ?string
    {
        return $this->checkout_sha;
    }
}
