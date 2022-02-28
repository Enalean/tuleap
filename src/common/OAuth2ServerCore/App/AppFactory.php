<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\OAuth2ServerCore\App;

use ProjectManager;

class AppFactory
{
    public function __construct(private RetrieveAppMatchingClientID $app_retriever, private ProjectManager $project_manager)
    {
    }

    /**
     * @throws OAuth2AppNotFoundException
     */
    public function getAppMatchingClientId(ClientIdentifier $client_identifier): OAuth2App
    {
        $row = $this->app_retriever->searchByClientId($client_identifier);
        if (! $row) {
            throw new OAuth2AppNotFoundException($client_identifier);
        }
        $project = null;
        if ($row['project_id'] !== null) {
            try {
                $project = $this->project_manager->getValidProject($row['project_id']);
            } catch (\Project_NotFoundException $e) {
                throw new OAuth2AppNotFoundException($client_identifier);
            }
        }
        return new OAuth2App($row['id'], $row['name'], $row['redirect_endpoint'], (bool) $row['use_pkce'], $project);
    }
}
