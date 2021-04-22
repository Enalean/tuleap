<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog;

use PFUser;
use Tracker_NoArtifactLinkFieldException;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ProgramIncrementsDAO;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\Rank\FeaturesRankOrderer;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\UserStoryLinkedToFeatureChecker;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PrioritizeFeaturesPermissionVerifier;
use Tuleap\ProgramManagement\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Program\Backlog\Feature\VerifyIsVisibleFeature;
use Tuleap\ProgramManagement\Program\Backlog\TopBacklog\CannotManipulateTopBacklog;
use Tuleap\ProgramManagement\Program\Backlog\TopBacklog\FeatureHasPlannedUserStoryException;
use Tuleap\ProgramManagement\Program\Backlog\TopBacklog\TopBacklogChange;
use Tuleap\ProgramManagement\Program\Backlog\TopBacklog\TopBacklogChangeProcessor;
use Tuleap\ProgramManagement\Program\Program;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdater;

final class ProcessTopBacklogChange implements TopBacklogChangeProcessor
{
    /**
     * @var \Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var ArtifactsExplicitTopBacklogDAO
     */
    private $explicit_top_backlog_dao;
    /**
     * @var PrioritizeFeaturesPermissionVerifier
     */
    private $prioritize_features_permission_verifier;
    /**
     * @var DBTransactionExecutor
     */
    private $db_transaction_executor;
    /**
     * @var ArtifactLinkUpdater
     */
    private $artifact_link_updater;
    /**
     * @var ProgramIncrementsDAO
     */
    private $program_increments_dao;
    /**
     * @var FeaturesRankOrderer
     */
    private $features_rank_orderer;
    /**
     * @var UserStoryLinkedToFeatureChecker
     */
    private $user_story_linked_to_feature_checker;
    /**
     * @var VerifyIsVisibleFeature
     */
    private $visible_feature_verifier;

    public function __construct(
        \Tracker_ArtifactFactory $artifact_factory,
        PrioritizeFeaturesPermissionVerifier $prioritize_features_permission_verifier,
        ArtifactsExplicitTopBacklogDAO $explicit_top_backlog_dao,
        DBTransactionExecutor $db_transaction_executor,
        ArtifactLinkUpdater $artifact_link_updater,
        ProgramIncrementsDAO $program_increments_dao,
        FeaturesRankOrderer $features_rank_orderer,
        UserStoryLinkedToFeatureChecker $user_story_linked_to_feature_checker,
        VerifyIsVisibleFeature $visible_feature_verifier
    ) {
        $this->artifact_factory                        = $artifact_factory;
        $this->prioritize_features_permission_verifier = $prioritize_features_permission_verifier;
        $this->explicit_top_backlog_dao                = $explicit_top_backlog_dao;
        $this->db_transaction_executor                 = $db_transaction_executor;
        $this->artifact_link_updater                   = $artifact_link_updater;
        $this->program_increments_dao                  = $program_increments_dao;
        $this->features_rank_orderer                   = $features_rank_orderer;
        $this->user_story_linked_to_feature_checker    = $user_story_linked_to_feature_checker;
        $this->visible_feature_verifier                = $visible_feature_verifier;
    }

    /**
     * @throws CannotManipulateTopBacklog
     * @throws Tracker_NoArtifactLinkFieldException
     * @throws FeatureHasPlannedUserStoryException
     */
    public function processTopBacklogChangeForAProgram(
        Program $program,
        TopBacklogChange $top_backlog_change,
        \PFUser $user
    ): void {
        $this->db_transaction_executor->execute(function () use ($program, $top_backlog_change, $user) {
            if (! $this->prioritize_features_permission_verifier->canUserPrioritizeFeatures($program, $user)) {
                throw new CannotManipulateTopBacklog($program, $user);
            }

            $feature_ids_to_add = $this->filterFeaturesThatCanBeManipulated(
                $top_backlog_change->potential_features_id_to_add,
                $user,
                $program
            );

            if (count($feature_ids_to_add) > 0) {
                if ($top_backlog_change->remove_program_increments_link_to_feature_to_add) {
                    $this->removeFeaturesFromProgramIncrement($user, $feature_ids_to_add);
                }
                $this->explicit_top_backlog_dao->addArtifactsToTheExplicitTopBacklog($feature_ids_to_add);
            }

            $feature_ids_to_remove = $this->filterFeaturesThatCanBeManipulated(
                $top_backlog_change->potential_features_id_to_remove,
                $user,
                $program
            );

            if (count($feature_ids_to_remove) > 0) {
                $this->explicit_top_backlog_dao->removeArtifactsFromExplicitTopBacklog($feature_ids_to_remove);
            }

            if ($top_backlog_change->elements_to_order) {
                $this->features_rank_orderer->reorder(
                    $top_backlog_change->elements_to_order,
                    (string) $program->getId(),
                    $program
                );
            }
        });
    }

    /**
     * @param int[] $feature_ids_to_add
     * @throws Tracker_NoArtifactLinkFieldException
     * @throws \Tracker_Exception
     */
    private function removeFeaturesFromProgramIncrement(PFUser $user, array $feature_ids_to_add): void
    {
        foreach ($feature_ids_to_add as $feature_id_to_add) {
            $program_ids = $this->program_increments_dao->getProgramIncrementsLinkToFeatureId($feature_id_to_add);
            foreach ($program_ids as $program_id) {
                $program_increment_artifact = $this->artifact_factory->getArtifactById($program_id['id']);
                if (! $program_increment_artifact) {
                    continue;
                }
                $this->artifact_link_updater->updateArtifactLinks(
                    $user,
                    $program_increment_artifact,
                    [],
                    [$feature_id_to_add],
                    \Tracker_FormElement_Field_ArtifactLink::NO_NATURE
                );
            }
        }
    }

    /**
     * @param int[] $features_id
     * @return int[]
     * @throws FeatureHasPlannedUserStoryException
     */
    private function filterFeaturesThatCanBeManipulated(array $features_id, \PFUser $user, Program $program): array
    {
        $filtered_features = [];

        foreach ($features_id as $feature_id) {
            if (! $this->visible_feature_verifier->isVisibleFeature(new FeatureIdentifier($feature_id), $user, $program)) {
                continue;
            }
            if ($this->user_story_linked_to_feature_checker->hasAPlannedUserStoryLinkedToFeature($user, $feature_id)) {
                throw new FeatureHasPlannedUserStoryException($feature_id);
            }
            $filtered_features[] = $feature_id;
        }

        return $filtered_features;
    }
}
