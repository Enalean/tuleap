<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Project\UGroups\Membership\DynamicUGroups;

use Feedback;
use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;

class ProjectMemberAdderWithoutStatusCheckAndNotifications implements ProjectMemberAdder
{
    /**
     * @var AddProjectMember
     */
    private $add_project_member;

    public function __construct(AddProjectMember $add_project_member)
    {
        $this->add_project_member = $add_project_member;
    }

    public static function build(): self
    {
        return new self(AddProjectMember::build());
    }

    public function addProjectMember(\PFUser $user, \Project $project): void
    {
        try {
            $this->add_project_member->addProjectMember($user, $project);
        } catch (CannotAddRestrictedUserToProjectNotAllowingRestricted $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
        } catch (AlreadyProjectMemberException $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
        }
    }
}
