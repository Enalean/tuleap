<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\ExplicitBacklog;

use Psr\Log\LoggerInterface;
use PFUser;
use Project;
use SimpleXMLElement;
use Tracker_XML_Importer_ArtifactImportedMapping;
use Tuleap\AgileDashboard\Milestone\Backlog\NoRootPlanningException;
use Tuleap\AgileDashboard\Milestone\Backlog\ProvidedAddedIdIsNotInPartOfTopBacklogException;
use Tuleap\AgileDashboard\Milestone\Backlog\TopBacklogElementsToAddChecker;
use Tuleap\XML\PHPCast;

class XMLImporter
{
    /**
     * @var ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    /**
     * @var TopBacklogElementsToAddChecker
     */
    private $top_backlog_elements_to_add_checker;

    /**
     * @var UnplannedArtifactsAdder
     */
    private $unplanned_artifacts_adder;

    public function __construct(
        ExplicitBacklogDao $explicit_backlog_dao,
        TopBacklogElementsToAddChecker $top_backlog_elements_to_add_checker,
        UnplannedArtifactsAdder $unplanned_artifacts_adder,
    ) {
        $this->explicit_backlog_dao                = $explicit_backlog_dao;
        $this->top_backlog_elements_to_add_checker = $top_backlog_elements_to_add_checker;
        $this->unplanned_artifacts_adder           = $unplanned_artifacts_adder;
    }

    public function importConfiguration(SimpleXMLElement $xml, Project $project): void
    {
        if (
            ! isset($xml->admin) ||
            PHPCast::toBoolean($xml->admin->scrum->explicit_backlog['is_used']) === true
        ) {
            $this->explicit_backlog_dao->setProjectIsUsingExplicitBacklog((int) $project->getID());
        }
    }

    public function importContent(
        SimpleXMLElement $xml,
        Project $project,
        PFUser $user,
        Tracker_XML_Importer_ArtifactImportedMapping $artifact_id_mapping,
        LoggerInterface $logger,
    ): void {
        if (! isset($xml->top_backlog)) {
            return;
        }

        $project_id = (int) $project->getID();

        if ($this->explicit_backlog_dao->isProjectUsingExplicitBacklog($project_id) === false) {
            $logger->warning('The imported project does not use explicit backlog management. Skipping.');
            return;
        }

        $added_artifact_ids = [];
        foreach ($xml->top_backlog->artifact as $xml_backlog_item) {
            $base_artifact_id = (string) $xml_backlog_item['artifact_id'];
            if (! $artifact_id_mapping->containsSource($base_artifact_id)) {
                $logger->warning("Artifact #$base_artifact_id not found in XML. Skipping.");
                continue;
            }

            $new_artifact_id      = (int) $artifact_id_mapping->get($base_artifact_id);
            $added_artifact_ids[] = $new_artifact_id;
        }

        try {
            $this->top_backlog_elements_to_add_checker->checkAddedIdsBelongToTheProjectTopBacklogTrackers(
                $project,
                $user,
                $added_artifact_ids
            );
        } catch (NoRootPlanningException $no_root_planning_exception) {
            $logger->error($no_root_planning_exception->getMessage());
            return;
        } catch (ProvidedAddedIdIsNotInPartOfTopBacklogException $exception) {
            $logger->warning($exception->getMessage() . "They are not added in the backlog.");

            $added_artifact_ids = array_diff($added_artifact_ids, $exception->getArtifactIds());
        }

        foreach ($added_artifact_ids as $added_artifact_id) {
            try {
                $this->unplanned_artifacts_adder->addArtifactToTopBacklogFromIds(
                    $added_artifact_id,
                    $project_id
                );
            } catch (ArtifactAlreadyPlannedException $exception) {
                //Do nothing
            }
        }
    }
}
