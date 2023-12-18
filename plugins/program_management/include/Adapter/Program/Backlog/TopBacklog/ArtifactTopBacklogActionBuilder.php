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
use Tuleap\Layout\JavascriptAssetGeneric;
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\NotAllowedToPrioritizeException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\VerifyFeaturePlanned;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\VerifyIsInTopBacklog;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogActionArtifactSourceInformation;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\Plan\VerifyIsPlannable;
use Tuleap\ProgramManagement\Domain\Program\Plan\VerifyPrioritizeFeaturesPermission;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\UserCanPrioritize;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\VerifyTrackerSemantics;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalButtonAction;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalButtonLinkPresenter;

final class ArtifactTopBacklogActionBuilder
{
    public function __construct(
        private BuildProgram $build_program,
        private VerifyPrioritizeFeaturesPermission $prioritize_features_permission_verifier,
        private VerifyIsPlannable $verify_is_plannable,
        private VerifyIsInTopBacklog $artifacts_explicit_top_backlog_dao,
        private VerifyFeaturePlanned $planned_feature_dao,
        private JavascriptAssetGeneric $asset,
        private VerifyTrackerSemantics $tracker_factory,
    ) {
    }

    public function buildTopBacklogActionBuilder(
        TopBacklogActionArtifactSourceInformation $source_information,
        PFUser $user,
    ): ?AdditionalButtonAction {
        try {
            $user_identifier = UserProxy::buildFromPFUser($user);
            $program         = ProgramIdentifier::fromId($this->build_program, $source_information->project_id, $user_identifier, null);
            UserCanPrioritize::fromUser(
                $this->prioritize_features_permission_verifier,
                $user_identifier,
                $program,
                null
            );
        } catch (ProgramAccessException | ProjectIsNotAProgramException | NotAllowedToPrioritizeException $e) {
            return null;
        }

        $error_messages = [];

        if (! $this->tracker_factory->hasTitleSemantic($source_information->tracker_id)) {
            $error_messages[] = dgettext(
                'tuleap-program_management',
                'Title semantic is not defined, the artifact cannot be added to the backlog'
            );
        }

        if (! $this->tracker_factory->hasStatusSemantic($source_information->tracker_id)) {
            $error_messages[] = dgettext(
                'tuleap-program_management',
                'Status semantic is not defined, the artifact cannot be added to the backlog'
            );
        }

        $link_label = dgettext('tuleap-program_management', 'Add to backlog');
        $icon       = 'fa-tlp-add-to-backlog';
        $action     = 'add';

        if ($this->artifacts_explicit_top_backlog_dao->isInTheExplicitTopBacklog($source_information->artifact_id)) {
            $link_label = dgettext('tuleap-program_management', 'Remove from backlog');
            $icon       = 'fa-tlp-remove-from-backlog';
            $action     = 'remove';
        } elseif (
            ! $this->verify_is_plannable->isPlannable(
                $source_information->tracker_id
            ) || $this->planned_feature_dao->isFeaturePlannedInAProgramIncrement($source_information->artifact_id)
        ) {
            return null;
        }

        $link = new AdditionalButtonLinkPresenter(
            $link_label,
            '',
            'add-to-top-backlog',
            $icon,
            'artifact-program-management-top-backlog-action',
            [
                [
                    'name'  => 'project-id',
                    'value' => $source_information->project_id,
                ],
                [
                    'name'  => 'artifact-id',
                    'value' => $source_information->artifact_id,
                ],
                [
                    'name'  => 'action',
                    'value' => $action,
                ],
            ],
            $error_messages
        );

        return new AdditionalButtonAction(
            $link,
            $this->asset
        );
    }
}
