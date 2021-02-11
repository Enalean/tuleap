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

use Tuleap\DB\DBTransactionExecutor;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PrioritizeFeaturesPermissionVerifier;
use Tuleap\ProgramManagement\Program\Backlog\TopBacklog\CannotManipulateTopBacklog;
use Tuleap\ProgramManagement\Program\Backlog\TopBacklog\TopBacklogChange;
use Tuleap\ProgramManagement\Program\Backlog\TopBacklog\TopBacklogChangeProcessor;
use Tuleap\ProgramManagement\Program\ProgramForManagement;

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

    public function __construct(
        \Tracker_ArtifactFactory $artifact_factory,
        PrioritizeFeaturesPermissionVerifier $prioritize_features_permission_verifier,
        ArtifactsExplicitTopBacklogDAO $explicit_top_backlog_dao,
        DBTransactionExecutor $db_transaction_executor
    ) {
        $this->artifact_factory                        = $artifact_factory;
        $this->prioritize_features_permission_verifier = $prioritize_features_permission_verifier;
        $this->explicit_top_backlog_dao                = $explicit_top_backlog_dao;
        $this->db_transaction_executor                 = $db_transaction_executor;
    }

    public function processTopBacklogChangeForAProgram(
        ProgramForManagement $program,
        TopBacklogChange $top_backlog_change,
        \PFUser $user
    ): void {
        $this->db_transaction_executor->execute(function () use ($program, $top_backlog_change, $user) {
            if (! $this->prioritize_features_permission_verifier->canUserPrioritizeFeatures($program, $user)) {
                throw new CannotManipulateTopBacklog($program, $user);
            }

            $feature_ids_to_remove = [];

            foreach ($top_backlog_change->potential_features_id_to_remove as $feature_id) {
                $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $feature_id);
                if ($artifact !== null && (int) $artifact->getTracker()->getGroupId() === $program->id) {
                    $feature_ids_to_remove[] = $feature_id;
                }
            }

            if (count($feature_ids_to_remove) > 0) {
                $this->explicit_top_backlog_dao->removeArtifactsFromExplicitTopBacklog($feature_ids_to_remove);
            }
        });
    }
}
