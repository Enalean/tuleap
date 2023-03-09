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

use PFUser;
use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Artifact\Changeset\XML\XMLChangeset;
use Tuleap\Tracker\Artifact\XML\XMLArtifact;
use Tuleap\Tracker\FormElement\Field\StringField\XML\XMLStringChangesetValue;
use Tuleap\Tracker\XML\IDGenerator;
use Tuleap\Tracker\XML\XMLUser;

final class ComponentsImporter
{
    public function __construct(
        private readonly ComponentsRetriever $components_retriever,
        private readonly ComponentsTrackerBuilder $components_tracker_builder,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function importProjectComponents(
        \SimpleXMLElement $trackers_xml,
        string $jira_project_key,
        IDGenerator $id_generator,
        PFUser $import_user,
    ): void {
        $project_components = $this->components_retriever->getProjectComponents($jira_project_key);
        if (count($project_components) === 0) {
            $this->logger->info("No components found in project.");
            return;
        }

        $this->logger->info(count($project_components)  . " Components found in project.");
        $this->logger->info("Creating Components tracker");

        $components_tracker = $this->components_tracker_builder->get($id_generator);

        $this->logger->info("Importing Components");
        foreach ($project_components as $component) {
            $changeset = (new XMLChangeset(XMLUser::buildUsername($import_user->getUserName()), new \DateTimeImmutable()))
                ->withFieldChange(new XMLStringChangesetValue(ComponentsTrackerBuilder::NAME_FIELD_NAME, $component->name));

            if ($component->description !== '') {
                $changeset = $changeset->withFieldChange(
                    new XMLStringChangesetValue(ComponentsTrackerBuilder::DESCRIPTION_FIELD_NAME, $component->description)
                );
            }

            $components_tracker = $components_tracker->withArtifact(
                (new XMLArtifact($id_generator->getNextId()))
                    ->withChangeset($changeset)
            );
        }

        $components_tracker->export($trackers_xml);
    }
}
