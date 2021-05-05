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
use Tuleap\ProgramManagement\Adapter\Program\Plan\PrioritizeFeaturesPermissionVerifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\NotAllowedToPrioritizeException;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanStore;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\UserCanPrioritize;

class MassChangeTopBacklogActionBuilder
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
     * @var TemplateRenderer
     */
    private $template_renderer;

    public function __construct(
        BuildProgram $build_program,
        PrioritizeFeaturesPermissionVerifier $prioritize_features_permission_verifier,
        PlanStore $plan_store,
        TemplateRenderer $template_renderer
    ) {
        $this->build_program                           = $build_program;
        $this->prioritize_features_permission_verifier = $prioritize_features_permission_verifier;
        $this->plan_store                              = $plan_store;
        $this->template_renderer                       = $template_renderer;
    }

    public function buildMassChangeAction(TopBacklogActionMassChangeSourceInformation $source_information, PFUser $user): ?string
    {
        try {
            $program = ProgramIdentifier::fromId($this->build_program, $source_information->project_id, $user);
            UserCanPrioritize::fromUser($this->prioritize_features_permission_verifier, $user, $program);
        } catch (ProgramAccessException | ProjectIsNotAProgramException | NotAllowedToPrioritizeException $e) {
            return null;
        }

        if (! $this->plan_store->isPlannable($source_information->tracker_id)) {
            return null;
        }

        return $this->template_renderer->renderToString('mass-change-top-backlog-actions', []);
    }
}
