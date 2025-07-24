<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Test\Stubs;

use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\WrapGitlabClient;

final class GitlabClientWrapperStub implements WrapGitlabClient
{
    private function __construct(private ?array $json)
    {
    }

    public static function buildWithJson(array $json): self
    {
        return new self($json);
    }

    public static function buildWithNullResponse(): self
    {
        return new self(null);
    }

    #[\Override]
    public function getUrl(Credentials $gitlab_credentials, string $url): ?array
    {
        return $this->json;
    }

    #[\Override]
    public function getPaginatedUrl(Credentials $gitlab_credentials, string $url, int $row_per_page = self::DEFAULT_NUMBER_OF_ROW_PER_PAGE): ?array
    {
        return $this->json;
    }
}
