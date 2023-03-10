<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\JiraImport\Project\Components;

use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUser;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserBuilder;

/**
 * @psalm-immutable
 */
final class JiraComponent
{
    private function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly ?JiraUser $lead,
    ) {
    }

    /**
     * @throw ComponentAPIResponseNotWellFormedException
     */
    public static function buildFromAPIResponse(array $response): self
    {
        if (! isset($response['name'])) {
            throw new ComponentAPIResponseNotWellFormedException();
        }

        $lead = null;
        if (isset($response['lead'])) {
            $lead = JiraUserBuilder::getUserFromPayload($response['lead']);
        }

        return new self(
            $response['name'],
            $response['description'] ?? '',
            $lead,
        );
    }

    public static function build(string $name, string $description, ?JiraUser $jira_user): self
    {
        return new self(
            $name,
            $description,
            $jira_user,
        );
    }
}
