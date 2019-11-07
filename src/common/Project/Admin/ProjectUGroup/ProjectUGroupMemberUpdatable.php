<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Project\Admin\ProjectUGroup;

use ProjectUGroup;
use Tuleap\Event\Dispatchable;

class ProjectUGroupMemberUpdatable implements Dispatchable
{
    public const NAME = 'projectUGroupMemberUpdatable';

    /**
     * @var \ProjectUGroup
     */
    private $group;
    /**
     * @var \PFUser[]
     */
    private $members_indexed_by_id;
    /**
     * @var bool
     */
    private $is_last_project_admin;
    /**
     * @var string[][]
     */
    private $updatable_error_messages = [];

    public function __construct(\ProjectUGroup $group)
    {
        $this->group   = $group;
        $this->members_indexed_by_id = [];
        foreach ($group->getMembersIncludingSuspendedAndDeleted() as $member) {
            $this->members_indexed_by_id[$member->getId()] = $member;
        }
        $this->is_last_project_admin = (int) $group->getId() === ProjectUGroup::PROJECT_ADMIN &&
            count($this->members_indexed_by_id) === 1;
    }

    /**
     * @return \ProjectUGroup
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @return \PFUser[]
     */
    public function getMembers()
    {
        return $this->members_indexed_by_id;
    }

    /**
     * @return string[]
     */
    public function getUserUpdatableErrorMessages(\PFUser $member)
    {
        if (! isset($this->members_indexed_by_id[$member->getId()])) {
            throw new ImpossibleToMarkNotUGroupMemberAsNotUpdatableException($this->group, $member);
        }

        $messages = [];

        if ($this->is_last_project_admin) {
            $messages[] = _('The last project administrator cannot be removed.');
        }

        if (isset($this->updatable_error_messages[$member->getId()])) {
            $messages = array_merge($messages, $this->updatable_error_messages[$member->getId()]);
        }

        return $messages;
    }

    public function markUserHasNotUpdatable(\PFUser $member, $message)
    {
        if (! isset($this->members_indexed_by_id[$member->getId()])) {
            throw new ImpossibleToMarkNotUGroupMemberAsNotUpdatableException($this->group, $member);
        }

        if (! isset($this->updatable_error_messages[$member->getId()])) {
            $this->updatable_error_messages[$member->getId()] = [];
        }

        $this->updatable_error_messages[$member->getId()][] = $message;
    }
}
