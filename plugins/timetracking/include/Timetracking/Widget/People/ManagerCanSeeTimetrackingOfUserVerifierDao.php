<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\Widget\People;

use PFUser;
use Tuleap\Option\Option;

final class ManagerCanSeeTimetrackingOfUserVerifierDao extends ManagerPermissionsDao implements VerifyManagerCanSeeTimetrackingOfUser
{
    #[\Override]
    public function isManagerAllowedToSeeTimetrackingOfUser(PFUser $manager, PFUser $user): bool
    {
        return $this->isManagerProjectAdmin($manager, $user)
            || $this->isManagerTrackerAdmin($manager, $user)
            || $this->isManagerTimetrackingReader($manager, $user);
    }

    private function isManagerTimetrackingReader(PFUser $manager, PFUser $user): bool
    {
        $manager_is_timetracking_reader = $this->getWritersSqlQueryWhenManagerIsTimetrackingAdmin(
            $manager,
            Option::fromValue($user),
        );

        return $this->isManagerTimetrackingReaderOfAProjectMemberWriter($manager, $user)
            || $this->isManagerProjectMemberAndTimetrackingReader($manager, $user)
            || $this->getDB()->exists(
                $manager_is_timetracking_reader->sql,
                ...$manager_is_timetracking_reader->parameters,
            );
    }

    private function isManagerTimetrackingReaderOfAProjectMemberWriter(PFUser $manager, PFUser $user): bool
    {
        $manager_is_timetracking_reader_of_a_project_member_writer = $this->getWritersSqlQueryWhenManagerIsTimetrackingReaderOfProjectMemberWriter(
            $manager,
            Option::fromValue($user),
        );

        return $this->getDB()->exists(
            $manager_is_timetracking_reader_of_a_project_member_writer->sql,
            ...$manager_is_timetracking_reader_of_a_project_member_writer->parameters,
        );
    }

    private function isManagerProjectMemberAndTimetrackingReader(PFUser $manager, PFUser $user): bool
    {
        $manager_is_project_member_and_timetracking_reader = $this->getWritersSqlQueryWhenManagerIsProjectMemberAndTimetrackingReader(
            $manager,
            Option::fromValue($user),
        );

        return $this->getDB()->exists(
            $manager_is_project_member_and_timetracking_reader->sql,
            ...$manager_is_project_member_and_timetracking_reader->parameters,
        );
    }

    private function isManagerTrackerAdmin(PFUser $manager, PFUser $user): bool
    {
        $manager_is_tracker_admin = $this->getWritersSqlQueryWhenManagerIsTrackerAdmin(
            $manager,
            Option::fromValue($user),
        );

        return $this->isManagerProjectMemberAndTrackerAdmin($manager, $user)
            || $this->getDB()->exists(
                $manager_is_tracker_admin->sql,
                ...$manager_is_tracker_admin->parameters,
            );
    }

    private function isManagerProjectMemberAndTrackerAdmin(PFUser $manager, PFUser $user): bool
    {
        $manager_is_project_member_and_tracker_admin = $this->getWritersSqlQueryWhenManagerIsProjectMemberAndTrackerAdmin(
            $manager,
            Option::fromValue($user),
        );

        return $this->getDB()->exists(
            $manager_is_project_member_and_tracker_admin->sql,
            ...$manager_is_project_member_and_tracker_admin->parameters,
        );
    }

    private function isManagerProjectAdmin(PFUser $manager, PFUser $user): bool
    {
        $manager_is_project_admin = $this->getWritersSqlQueryWhenManagerIsProjectAdmin(
            $manager,
            Option::fromValue($user),
        );

        return $this->isManagerProjectAdminOfAProjectMemberWriter($manager, $user)
            || $this->getDB()->exists(
                $manager_is_project_admin->sql,
                ...$manager_is_project_admin->parameters,
            );
    }

    private function isManagerProjectAdminOfAProjectMemberWriter(PFUser $manager, PFUser $user): bool
    {
        $manager_is_project_admin_of_a_project_member_writer = $this->getWritersSqlQueryWhenManagerIsProjectAdminOfAProjectMemberWriter(
            $manager,
            Option::fromValue($user),
        );

        return $this->getDB()->exists(
            $manager_is_project_admin_of_a_project_member_writer->sql,
            ...$manager_is_project_admin_of_a_project_member_writer->parameters,
        );
    }
}
