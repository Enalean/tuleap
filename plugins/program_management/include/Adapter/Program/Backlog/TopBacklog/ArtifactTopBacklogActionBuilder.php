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
use Tuleap\ProgramManagement\Adapter\Workspace\UserPermissionsProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\NotAllowedToPrioritizeException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogActionArtifactSourceInformation;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanStore;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\Plan\VerifyPrioritizeFeaturesPermission;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\UserCanPrioritize;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalButtonAction;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalButtonLinkPresenter;

final class ArtifactTopBacklogActionBuilder
{
    private BuildProgram $build_program;
    private VerifyPrioritizeFeaturesPermission $prioritize_features_permission_verifier;
    private PlanStore $plan_store;
    private ArtifactsExplicitTopBacklogDAO $artifacts_explicit_top_backlog_dao;
    private PlannedFeatureDAO $planned_feature_dao;
    private JavascriptAsset $asset;

    public function __construct(
        BuildProgram $build_program,
        VerifyPrioritizeFeaturesPermission $prioritize_features_permission_verifier,
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

    public function buildTopBacklogActionBuilder(TopBacklogActionArtifactSourceInformation $source_information, PFUser $user): ?AdditionalButtonAction
    {
        try {
            $user_identifier = UserIdentifier::fromPFUser($user);
            $program         = ProgramIdentifier::fromId($this->build_program, $source_information->project_id, $user_identifier);
            UserCanPrioritize::fromUser(
                $this->prioritize_features_permission_verifier,
                UserPermissionsProxy::buildFromPFUser($user, $program),
                $user_identifier,
                $program
            );
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
