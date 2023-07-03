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

namespace Tuleap\AgileDashboard\REST\v1\Milestone;

use MilestoneParentLinker;
use PFUser;
use Planning_Milestone;
use Project;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\REST\v1\MilestoneResourceValidator;
use Tuleap\AgileDashboard\REST\v1\ResourcesPatcher;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdater;

class MilestoneElementMover
{
    /**
     * @var ResourcesPatcher
     */
    private $resources_patcher;
    /**
     * @var MilestoneResourceValidator
     */
    private $milestone_validator;
    /**
     * @var ArtifactLinkUpdater
     */
    private $artifact_link_updater;
    /**
     * @var DBTransactionExecutor
     */
    private $db_transaction_executor;
    /**
     * @var Tracker_ArtifactFactory
     */
    private $tracker_artifact_factory;
    /**
     * @var MilestoneParentLinker
     */
    private $milestone_parent_linker;
    /**
     * @var ArtifactsInExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    public function __construct(
        ResourcesPatcher $resources_patcher,
        MilestoneResourceValidator $milestone_validator,
        ArtifactLinkUpdater $artifact_link_updater,
        DBTransactionExecutor $db_transaction_executor,
        Tracker_ArtifactFactory $tracker_artifact_factory,
        MilestoneParentLinker $milestone_parent_linker,
        ArtifactsInExplicitBacklogDao $explicit_backlog_dao,
    ) {
        $this->resources_patcher        = $resources_patcher;
        $this->milestone_validator      = $milestone_validator;
        $this->artifact_link_updater    = $artifact_link_updater;
        $this->db_transaction_executor  = $db_transaction_executor;
        $this->tracker_artifact_factory = $tracker_artifact_factory;
        $this->milestone_parent_linker  = $milestone_parent_linker;
        $this->explicit_backlog_dao     = $explicit_backlog_dao;
    }

    /**
     * @throws \Luracast\Restler\RestException
     * @throws \Tracker_NoArtifactLinkFieldException
     * @throws \Tuleap\AgileDashboard\REST\v1\ArtifactCannotBeInBacklogOfException
     * @throws \Tuleap\Tracker\REST\Helpers\IdsFromBodyAreNotUniqueException
     */
    public function moveElement(PFUser $user, array $add, Planning_Milestone $milestone): array
    {
        $this->resources_patcher->startTransaction();

        $to_add = $this->resources_patcher->removeArtifactFromSource($user, $add);
        if (count($to_add)) {
            $valid_to_add = $this->milestone_validator->validateArtifactIdsCanBeAddedToBacklog(
                $to_add,
                $milestone,
                $user
            );
            $this->addMissingElementsToBacklog($milestone, $user, $valid_to_add);
        }
        $this->resources_patcher->commit();

        return $to_add;
    }

    /**
     * @throws \Tracker_NoArtifactLinkFieldException
     */
    private function addMissingElementsToBacklog(Planning_Milestone $milestone, PFUser $user, array $to_add): void
    {
        if (count($to_add) === 0) {
            return;
        }

        $this->artifact_link_updater->updateArtifactLinks(
            $user,
            $milestone->getArtifact(),
            $to_add,
            [],
            Tracker_FormElement_Field_ArtifactLink::NO_TYPE
        );
    }

    public function moveElementToMilestoneContent(
        Planning_Milestone $milestone,
        PFUser $user,
        array $add,
    ): void {
        $this->db_transaction_executor->execute(
            function () use ($milestone, $user, $add) {
                $to_add = $this->resources_patcher->removeArtifactFromSource($user, $add);
                if (count($to_add)) {
                    $this->artifact_link_updater->updateArtifactLinks(
                        $user,
                        $milestone->getArtifact(),
                        $this->getFilteredArtifactIdsToAdd($milestone, $to_add),
                        [],
                        Tracker_FormElement_Field_ArtifactLink::NO_TYPE
                    );
                    $this->linkToMilestoneParent($milestone, $user, $to_add);
                    $this->removeItemsFromExplicitBacklog($milestone->getProject(), $to_add);
                }
            }
        );
    }

    /**
     * @return array
     */
    private function getFilteredArtifactIdsToAdd(Planning_Milestone $milestone, array $to_add): array
    {
        $backlog_tracker_ids   = $milestone->getPlanning()->getBacklogTrackersIds();
        $filtered_artifact_ids = [];

        foreach ($to_add as $artifact_id) {
            $artifact = $this->tracker_artifact_factory->getArtifactById($artifact_id);
            if (! $artifact) {
                continue;
            }

            if (in_array($artifact->getTrackerId(), $backlog_tracker_ids)) {
                $filtered_artifact_ids[] = $artifact_id;
            }
        }

        return array_unique($filtered_artifact_ids);
    }

    private function linkToMilestoneParent(Planning_Milestone $milestone, PFUser $user, array $to_add): void
    {
        foreach ($to_add as $artifact_id_to_add) {
            $artifact_added = $this->tracker_artifact_factory->getArtifactById($artifact_id_to_add);
            if (! $artifact_added) {
                continue;
            }

            $this->milestone_parent_linker->linkToMilestoneParent($milestone, $user, $artifact_added);
        }
    }

    private function removeItemsFromExplicitBacklog(Project $project, array $to_add): void
    {
        $this->explicit_backlog_dao->removeItemsFromExplicitBacklogOfProject(
            (int) $project->getID(),
            $to_add
        );
    }
}
