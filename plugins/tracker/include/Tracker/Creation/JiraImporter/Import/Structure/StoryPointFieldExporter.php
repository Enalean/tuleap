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
use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfiguration;
use Tuleap\Tracker\Creation\JiraImporter\Import\AlwaysThereFieldsExporter;
use Tuleap\Tracker\Creation\JiraImporter\IssueType;
use Tuleap\Tracker\FormElement\Field\FloatingPointNumber\XML\XMLFloatField;
use Tuleap\Tracker\XML\IDGenerator;
use Tuleap\Tracker\XML\XMLTracker;

final class StoryPointFieldExporter
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function exportFields(
        PlatformConfiguration $configuration,
        XMLTracker $xml_tracker,
        FieldMappingCollection $field_mapping_collection,
        IssueType $issue_type,
        IDGenerator $id_generator,
    ): XMLTracker {
        if ($issue_type->isSubtask()) {
            $this->logger->debug('-- is sub task, abort');
            return $xml_tracker;
        }

        if (! $configuration->hasStoryPointsField()) {
            $this->logger->debug('-- there is no Story Point Field, abort');
            return $xml_tracker;
        }

        $field = (new XMLFloatField($id_generator, AlwaysThereFieldsExporter::JIRA_STORY_POINTS_NAME))
            ->withLabel('Story points (initial effort)')
            ->withRank(AlwaysThereFieldsExporter::JIRA_STORY_POINTS_RANK)
            ->withPermissions(... AlwaysThereFieldsExporter::getDefaultPermissions());

        $field_mapping_collection->addMappingBetweenTuleapAndJiraField(
            new JiraFieldAPIRepresentation(
                $configuration->getStoryPointsField(),
                $field->label,
                false,
                null,
                [],
                true,
            ),
            $field,
        );

        return $xml_tracker->appendFormElement(
            AlwaysThereFieldsExporter::RIGHT_COLUMN_NAME,
            $field,
        );
    }
}
