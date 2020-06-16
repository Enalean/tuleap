<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact;

use SimpleXMLElement;
use Tuleap\Tracker\Creation\JiraImporter\Import\AlwaysThereFieldsExporter;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeStringBuilder;

class LastDataChangesetXMLUpdater
{
    /**
     * @var FieldChangeStringBuilder
     */
    private $field_change_string_builder;

    public function __construct(FieldChangeStringBuilder $field_change_string_builder)
    {
        $this->field_change_string_builder = $field_change_string_builder;
    }

    public function updateLastXMLChangeset(
        array $issue,
        string $jira_base_url,
        SimpleXMLElement $changeset_node
    ): void {
        $this->addTuleapRelatedInformationOnLastXMLSnapshot($issue, $jira_base_url, $changeset_node);
    }

    private function addTuleapRelatedInformationOnLastXMLSnapshot(
        array $issue,
        string $jira_base_url,
        SimpleXMLElement $changeset_node
    ): void {
        $jira_link = rtrim($jira_base_url, "/") . "/browse/" . urlencode($issue['key']);
        $this->field_change_string_builder->build(
            $changeset_node,
            AlwaysThereFieldsExporter::JIRA_LINK_FIELD_NAME,
            $jira_link
        );
    }
}
