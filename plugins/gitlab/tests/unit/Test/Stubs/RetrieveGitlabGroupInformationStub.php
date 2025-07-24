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

use Throwable;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\Group\GitlabGroupApiDataRepresentation;
use Tuleap\Gitlab\API\Group\RetrieveGitlabGroupInformation;
use Tuleap\Gitlab\REST\v1\Group\GitlabGroupPOSTRepresentation;

final class RetrieveGitlabGroupInformationStub implements RetrieveGitlabGroupInformation
{
    public function __construct(private ?Throwable $exception, private GitlabGroupApiDataRepresentation $group_api_data)
    {
    }

    /**
     * @throws Throwable
     */
    #[\Override]
    public function getGitlabGroupFromGitlabApi(Credentials $credential, GitlabGroupPOSTRepresentation $representation): GitlabGroupApiDataRepresentation
    {
        if ($this->exception) {
            throw $this->exception;
        }
        return $this->group_api_data;
    }

    public static function buildDefault(): self
    {
        $group_data               = [];
        $group_data['id']         = 10;
        $group_data['name']       = '';
        $group_data['avatar_url'] = '';
        $group_data['full_path']  = '';
        $group_data['web_url']    = '';

        return new self(null, GitlabGroupApiDataRepresentation::buildGitlabGroupFromApi($group_data));
    }

    public static function buildWithGroupApiData(array $group_data): self
    {
        return new self(null, GitlabGroupApiDataRepresentation::buildGitlabGroupFromApi($group_data));
    }
}
