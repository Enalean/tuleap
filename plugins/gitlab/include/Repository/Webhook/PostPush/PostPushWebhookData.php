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

use Tuleap\Gitlab\Repository\Webhook\WebhookData;

/**
 * @psalm-immutable
 */
class PostPushWebhookData implements WebhookData
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
    private $gitlab_web_url;

    /**
     * @var PostPushCommitWebhookData[]
     */
    private $commits;

    private string $reference;
    private ?string $checkout_sha;

    public function __construct(
        string $event_name,
        int $gitlab_project_id,
        string $gitlab_web_url,
        ?string $checkout_sha,
        string $reference,
        array $commits,
    ) {
        $this->event_name        = $event_name;
        $this->gitlab_project_id = $gitlab_project_id;
        $this->gitlab_web_url    = $gitlab_web_url;
        $this->commits           = $commits;
        $this->reference         = $reference;
        $this->checkout_sha      = $checkout_sha;
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
        return $this->gitlab_web_url;
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
