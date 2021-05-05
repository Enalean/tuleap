<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog;

use PFUser;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PrioritizeFeaturesPermissionVerifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\NotAllowedToPrioritizeException;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanStore;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\UserCanPrioritize;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalButtonAction;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalButtonLinkPresenter;

class ArtifactTopBacklogActionBuilder
{
    /**
     * @var BuildProgram
     */
    private $build_program;
    /**
     * @var PrioritizeFeaturesPermissionVerifier
     */
    private $prioritize_features_permission_verifier;
    /**
     * @var PlanStore
     */
    private $plan_store;
    /**
     * @var ArtifactsExplicitTopBacklogDAO
     */
    private $artifacts_explicit_top_backlog_dao;
    /**
     * @var PlannedFeatureDAO
     */
    private $planned_feature_dao;
    /**
     * @var JavascriptAsset
     */
    private $asset;

    public function __construct(
        BuildProgram $build_program,
        PrioritizeFeaturesPermissionVerifier $prioritize_features_permission_verifier,
        PlanStore $plan_store,
        ArtifactsExplicitTopBacklogDAO $artifacts_explicit_top_backlog_dao,
        PlannedFeatureDAO $planned_feature_dao,
        JavascriptAsset $asset
    ) {
        $this->build_program                           = $build_program;
        $this->prioritize_features_permission_verifier = $prioritize_features_permission_verifier;
        $this->plan_store                              = $plan_store;
        $this->artifacts_explicit_top_backlog_dao      = $artifacts_explicit_top_backlog_dao;
        $this->planned_feature_dao                     = $planned_feature_dao;
        $this->asset                                   = $asset;
    }

    public function buildTopBacklogActionBuilder(TopBacklogActionActifactSourceInformation $source_information, PFUser $user): ?AdditionalButtonAction
    {
        try {
            $program = ProgramIdentifier::fromId($this->build_program, $source_information->project_id, $user);
            UserCanPrioritize::fromUser($this->prioritize_features_permission_verifier, $user, $program);
        } catch (ProgramAccessException | ProjectIsNotAProgramException | NotAllowedToPrioritizeException $e) {
            return null;
        }

        $link_label = dgettext('tuleap-program_management', 'Add to top backlog');
        $icon       = 'fa-tlp-add-to-backlog';
        $action     = 'add';

        if ($this->artifacts_explicit_top_backlog_dao->isInTheExplicitTopBacklog($source_information->artifact_id)) {
            $link_label = dgettext('tuleap-program_management', 'Remove from top backlog');
            $icon       = 'fa-tlp-remove-from-backlog';
            $action     = 'remove';
        } elseif (! $this->plan_store->isPlannable($source_information->tracker_id) || $this->planned_feature_dao->isFeaturePlannedInAProgramIncrement($source_information->artifact_id)) {
            return null;
        }

        $link = new AdditionalButtonLinkPresenter(
            $link_label,
            '',
            $icon,
            'artifact-program-management-top-backlog-action',
            [
                [
                    'name'  => 'project-id',
                    'value' => $source_information->project_id
                ],
                [
                    'name'  => 'artifact-id',
                    'value' => $source_information->artifact_id
                ],
                [
                    'name'  => 'action',
                    'value' => $action
                ]
            ]
        );

        return new AdditionalButtonAction(
            $link,
            $this->asset->getFileURL()
        );
    }
}
