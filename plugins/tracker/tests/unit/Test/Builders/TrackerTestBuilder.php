<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Builders;

use Project;
use Tuleap\Color\ItemColor;
use Tuleap\Tracker\Permission\VerifySubmissionPermissions;
use Tuleap\Tracker\Test\Stub\VerifySubmissionPermissionStub;
use Tuleap\Tracker\Tracker;

final class TrackerTestBuilder
{
    private ?ItemColor $color     = null;
    private string $name          = 'Irrelevant';
    private string $description   = 'Irrelevant';
    private string $short_name    = 'irrelevant';
    private ?Project $project     = null;
    private int $tracker_id       = 0;
    private ?int $deletion_date   = null;
    private ?\Workflow $workflow  = null;
    private bool $user_can_submit = true;
    private ?Tracker $parent      = Tracker::NO_PARENT;
    private ?bool $user_can_view  = null;
    private ?bool $user_is_admin  = null;

    public static function aTracker(): self
    {
        return new self();
    }

    public function withId(int $id): self
    {
        $this->tracker_id = $id;

        return $this;
    }

    public function withProject(Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function withName(string $name): self
    {
        $this->name       = $name;
        $this->short_name = strtolower($name);

        return $this;
    }

    public function withDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function withShortName(string $name): self
    {
        $this->short_name = $name;

        return $this;
    }

    public function withDeletionDate(int $deletion_date): self
    {
        $this->deletion_date = $deletion_date;

        return $this;
    }

    public function withColor(ItemColor $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function withWorkflow(\Workflow $workflow): self
    {
        $this->workflow = $workflow;

        return $this;
    }

    public function withUserCanSubmit(bool $user_can_submit): self
    {
        $this->user_can_submit = $user_can_submit;

        return $this;
    }

    public function withUserCanView(bool $user_can_view): self
    {
        $this->user_can_view = $user_can_view;

        return $this;
    }

    public function withUserIsAdmin(bool $user_is_admin): self
    {
        $this->user_is_admin = $user_is_admin;

        return $this;
    }

    public function withParent(?Tracker $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    private function getProjectId(): int
    {
        if (! $this->project) {
            return 0;
        }

        return (int) $this->project->getId();
    }

    private function getColor(): ItemColor
    {
        if (! $this->color) {
            return ItemColor::default();
        }

        return $this->color;
    }

    public function build(): Tracker
    {
        $tracker = new class (
            $this->tracker_id,
            $this->getProjectId(),
            $this->name,
            $this->description,
            $this->short_name,
            false,
            null,
            null,
            null,
            $this->deletion_date,
            true,
            false,
            Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            $this->getColor(),
            false,
            $this->user_can_submit,
            $this->user_can_view,
            $this->user_is_admin,
        ) extends Tracker {
            public function __construct(
                int $id,
                int $group_id,
                string $name,
                string $description,
                string $item_name,
                bool $allow_copy,
                null $submit_instructions,
                null $browse_instructions,
                null $status,
                ?int $deletion_date,
                bool $instantiate_for_new_projects,
                bool $log_priority_changes,
                int $notifications_level,
                ItemColor $color,
                bool $enable_emailgateway,
                private readonly bool $user_can_submit,
                private readonly ?bool $user_can_view,
                private readonly ?bool $user_is_admin,
            ) {
                parent::__construct($id, $group_id, $name, $description, $item_name, $allow_copy, $submit_instructions, $browse_instructions, $status, $deletion_date, $instantiate_for_new_projects, $log_priority_changes, $notifications_level, $color, $enable_emailgateway);
            }

            protected function getTrackerArtifactSubmissionPermission(): VerifySubmissionPermissions
            {
                return $this->user_can_submit
                    ? VerifySubmissionPermissionStub::withSubmitPermission()
                    : VerifySubmissionPermissionStub::withoutSubmitPermission();
            }

            public function userCanView($user = 0): bool
            {
                return $this->user_can_view ?? parent::userCanView($user);
            }

            public function userIsAdmin($user = 0): bool
            {
                return $this->user_is_admin ?? parent::userIsAdmin($user);
            }
        };

        if ($this->project) {
            $tracker->setProject($this->project);
        }

        if ($this->workflow) {
            $tracker->setWorkflow($this->workflow);
        }

        $tracker->setParent($this->parent);

        return $tracker;
    }
}
