<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Structure;

use Psr\Log\LoggerInterface;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfiguration;
use Tuleap\Tracker\Creation\JiraImporter\Import\AlwaysThereFieldsExporter;
use Tuleap\Tracker\Creation\JiraImporter\IssueType;

final class StoryPointFieldExporter
{
    /**
     * @var FieldXmlExporter
     */
    private $field_xml_exporter;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(FieldXmlExporter $field_xml_exporter, LoggerInterface $logger)
    {
        $this->field_xml_exporter = $field_xml_exporter;
        $this->logger             = $logger;
    }

    public function exportFields(
        PlatformConfiguration $configuration,
        ContainersXMLCollection $containers_collection,
        FieldMappingCollection $field_mapping_collection,
        IssueType $issue_type,
    ): void {
        if ($issue_type->isSubtask()) {
            $this->logger->debug('-- is sub task, abort');
            return;
        }

        if (! $configuration->hasStoryPointsField()) {
            $this->logger->debug('-- there is no Story Point Field, abort');
            return;
        }

        $this->field_xml_exporter->exportField(
            $containers_collection->getContainerByName(ContainersXMLCollectionBuilder::RIGHT_COLUMN_NAME),
            Tracker_FormElementFactory::FIELD_FLOAT_TYPE,
            AlwaysThereFieldsExporter::JIRA_STORY_POINTS_NAME,
            'Story points (initial effort)',
            $configuration->getStoryPointsField(),
            AlwaysThereFieldsExporter::JIRA_STORY_POINTS_RANK,
            false,
            [],
            [],
            $field_mapping_collection,
            null,
        );
    }
}
