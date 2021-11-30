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
use TemplateRenderer;
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\NotAllowedToPrioritizeException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogActionMassChangeSourceInformation;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\Plan\VerifyIsPlannable;
use Tuleap\ProgramManagement\Domain\Program\Plan\VerifyPrioritizeFeaturesPermission;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\UserCanPrioritize;

final class MassChangeTopBacklogActionBuilder
{
    public function __construct(
        private BuildProgram $build_program,
        private VerifyPrioritizeFeaturesPermission $prioritize_features_permission_verifier,
        private VerifyIsPlannable $verify_is_plannable,
        private TemplateRenderer $template_renderer,
    ) {
    }

    public function buildMassChangeAction(TopBacklogActionMassChangeSourceInformation $source_information, PFUser $user): ?string
    {
        $user_identifier = UserProxy::buildFromPFUser($user);
        try {
            $program = ProgramIdentifier::fromId($this->build_program, $source_information->project_id, $user_identifier, null);
            UserCanPrioritize::fromUser(
                $this->prioritize_features_permission_verifier,
                $user_identifier,
                $program,
                null
            );
        } catch (ProgramAccessException | ProjectIsNotAProgramException | NotAllowedToPrioritizeException $e) {
            return null;
        }

        if (! $this->verify_is_plannable->isPlannable($source_information->tracker_id)) {
            return null;
        }

        return $this->template_renderer->renderToString('mass-change-top-backlog-actions', []);
    }
}
