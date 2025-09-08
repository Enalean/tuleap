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

namespace Tuleap\Gitlab\API\Group;

use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\API\WrapGitlabClient;
use Tuleap\Gitlab\REST\v1\Group\GitlabGroupPOSTRepresentation;

final class GitlabGroupInformationRetriever implements RetrieveGitlabGroupInformation
{
    public function __construct(private WrapGitlabClient $gitlab_client)
    {
    }

    /**
     * @throws GitlabResponseAPIException
     * @throws GitlabRequestException
     */
    #[\Override]
    public function getGitlabGroupFromGitlabApi(Credentials $credential, GitlabGroupPOSTRepresentation $representation): GitlabGroupApiDataRepresentation
    {
        $gitlab_group_data = $this->gitlab_client->getUrl($credential, '/groups/' . urlencode((string) $representation->gitlab_group_id));

        if (! $gitlab_group_data) {
            throw new GitlabResponseAPIException(
                'The query is not in error but the json content is empty. This is not expected.'
            );
        }

        return GitlabGroupApiDataRepresentation::buildGitlabGroupFromApi($gitlab_group_data);
    }
}
