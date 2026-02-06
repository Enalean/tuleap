<?php
/**
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Test\Builder;

use Override;
use Tuleap\Gitlab\Repository\Webhook\WebhookData;

final class WebhookDataBuilder
{
    private int $repository_id             = 1;
    private string $repository_url         = 'https://example.com/repository';
    private string $repository_name        = 'my_repo';
    private string $repository_description = '';

    private function __construct(private readonly string $event_name)
    {
    }

    public static function aWebhook(string $event_name): self
    {
        return new self($event_name);
    }

    public function withRepositoryId(int $id): self
    {
        $this->repository_id = $id;
        return $this;
    }

    public function withRepositoryUrl(string $url): self
    {
        $this->repository_url = $url;
        return $this;
    }

    public function withRepositoryName(string $name): self
    {
        $this->repository_name = $name;
        return $this;
    }

    public function withRepositoryDescription(string $description): self
    {
        $this->repository_description = $description;
        return $this;
    }

    public function build(): WebhookData
    {
        return new readonly class (
            $this->event_name,
            $this->repository_id,
            $this->repository_url,
            $this->repository_name,
            $this->repository_description,
        ) implements WebhookData {
            public function __construct(
                private string $event_name,
                private int $repository_id,
                private string $repository_url,
                private string $repository_name,
                private string $repository_description,
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
                return $this->repository_id;
            }

            #[Override]
            public function getGitlabWebUrl(): string
            {
                return $this->repository_url;
            }

            #[Override]
            public function getGitlabProjectName(): string
            {
                return $this->repository_name;
            }

            #[Override]
            public function getGitlabProjectDescription(): string
            {
                return $this->repository_description;
            }
        };
    }
}
