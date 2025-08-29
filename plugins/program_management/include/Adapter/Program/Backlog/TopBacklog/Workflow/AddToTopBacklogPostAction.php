<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\Workflow;

use SimpleXMLElement;
use Tracker_Artifact_Changeset;
use Transition;
use Transition_PostAction;
use Tuleap\ProgramManagement\Adapter\Permissions\WorkflowUserPermissionBypass;
use Tuleap\ProgramManagement\Adapter\Workspace\ProgramServiceIsEnabledCertifier;
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogChange;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogChangeProcessor;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Workflow\PostAction\Visitor;

final class AddToTopBacklogPostAction extends Transition_PostAction
{
    public const SHORT_NAME   = 'program_management_add_to_top_backlog';
    public const XML_TAG_NAME = 'postaction_add_to_program_top_backlog';

    public function __construct(
        Transition $transition,
        int $id,
        private readonly ProgramServiceIsEnabledCertifier $program_certifier,
        private TopBacklogChangeProcessor $top_backlog_change_processor,
    ) {
        parent::__construct($transition, $id);
    }

    public function getShortName(): string
    {
        return self::SHORT_NAME;
    }

    public static function getLabel(): string
    {
        // Not implemented. We do not support the legacy UI for this new post action
        return '';
    }

    public function isDefined(): bool
    {
        // Since we do not support the legacy UI, it is always well defined
        return true;
    }

    public function exportToXml(SimpleXMLElement $root, $xmlMapping): void
    {
        $root->addChild(self::XML_TAG_NAME);
    }

    public function bypassPermissions(TrackerField $field): bool
    {
        return false;
    }

    public function accept(Visitor $visitor): void
    {
        $visitor->visitExternalActions($this);
    }

    public function after(Tracker_Artifact_Changeset $changeset): void
    {
        $artifact = $changeset->getArtifact();

        $this->program_certifier->certifyProgramServiceEnabled($artifact->getTracker()->getProject())
            ->map(ProgramIdentifier::fromServiceEnabled(...))
            ->apply(function (ProgramIdentifier $program) use ($artifact) {
                $user_identifier    = UserProxy::buildFromPFUser(new AddToBacklogPostActionAllPowerfulUser());
                $top_backlog_change = new TopBacklogChange([$artifact->getId()], [], false, null);
                $this->top_backlog_change_processor->processTopBacklogChangeForAProgram(
                    $program,
                    $top_backlog_change,
                    $user_identifier,
                    new WorkflowUserPermissionBypass()
                );
            });
    }
}
