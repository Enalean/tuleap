<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\API\Tag;

use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;

class GitlabTagRetriever
{
    /**
     * @var ClientWrapper
     */
    private $gitlab_api_client;

    public function __construct(ClientWrapper $gitlab_api_client)
    {
        $this->gitlab_api_client = $gitlab_api_client;
    }

    /**
     * @throws GitlabResponseAPIException
     * @throws GitlabRequestException
     */
    public function getTagFromGitlabAPI(
        Credentials $credentials,
        GitlabRepositoryIntegration $gitlab_repository_integration,
        string $tag_name,
    ): GitlabTag {
        $gitlab_integration_id = $gitlab_repository_integration->getGitlabRepositoryId();

        $gitlab_tag_data = $this->gitlab_api_client->getUrl(
            $credentials,
            "/projects/$gitlab_integration_id/repository/tags/" . urlencode($tag_name)
        );

        if (! $gitlab_tag_data) {
            throw new GitlabResponseAPIException(
                "The query is not in error but the json content is empty. This is not expected."
            );
        }

        return GitlabTag::buildFromAPIResponse($gitlab_tag_data);
    }
}
