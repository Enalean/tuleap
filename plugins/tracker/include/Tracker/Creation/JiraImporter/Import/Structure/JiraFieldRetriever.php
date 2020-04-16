<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Structure;

use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;

class JiraFieldRetriever
{
    /**
     * @var ClientWrapper
     */
    private $wrapper;

    public function __construct(ClientWrapper $wrapper)
    {
        $this->wrapper = $wrapper;
    }

    public function getAllJiraFields(): array
    {
        $jira_fields = $this->wrapper->getUrl('/field');

        $fields_by_id = [];
        if (! $jira_fields) {
            return $fields_by_id;
        }

        foreach ($jira_fields as $jira_field) {
            if (! $jira_field['id']) {
                throw new \LogicException('Jira field does not have an id');
            }
            $fields_by_id[$jira_field['id']] = $jira_field;
        }

        return $fields_by_id;
    }
}
