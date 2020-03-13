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

use DataAccessQueryException;
use Feedback;
use ProjectHistoryDao;
use ProjectUGroup;
use Tuleap\Project\Admin\MembershipDelegationDao;

class DelegationController
{
    /**
     * @var MembershipDelegationDao
     */
    private $membership_delegation_dao;
    /**
     * @var ProjectHistoryDao
     */
    private $history_dao;

    public function __construct(MembershipDelegationDao $membership_delegation_dao, ProjectHistoryDao $history_dao)
    {
        $this->membership_delegation_dao = $membership_delegation_dao;
        $this->history_dao               = $history_dao;
    }

    public function updateDelegation(ProjectUGroup $ugroup, $new_permissions_delegation)
    {
        if ($new_permissions_delegation === false) {
            return;
        }

        if (! is_array($new_permissions_delegation)) {
            return;
        }

        $has_membership_management = $new_permissions_delegation['membership-management'] ?: false;
        try {
            if ($this->membership_delegation_dao->updateMembershipManagement($ugroup->getId(), $has_membership_management)) {
                $this->history_dao->groupAddHistory(
                    'ugroup_membership_management_updated',
                    (int) $has_membership_management,
                    $ugroup->getProjectId(),
                    array($ugroup->getName())
                );
                $GLOBALS['Response']->addFeedback(
                    Feedback::INFO,
                    _('Permissions delegations have been successfully updated.')
                );
            }
        } catch (DataAccessQueryException $e) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                _('An error occurred while trying to update permissions delegations.')
            );
        }
    }
}
