<?php
/**
 * Copyright (c) Enalean, 2018 - 2019. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\REST\v1\Workflow;

use Luracast\Restler\RestException;
use PFUser;
use Tracker;
use Transition;
use Tuleap\Tracker\REST\v1\TrackerPermissionsChecker;
use Tuleap\Tracker\Workflow\Transition\OrphanTransitionException;

/**
 * Check permissions for a given user to manipulate transitions.
 */
class TransitionsPermissionsChecker
{

    /**
     * @var TrackerPermissionsChecker
     */
    private $permissions_checker;

    public function __construct(TrackerPermissionsChecker $permissions_checker)
    {
        $this->permissions_checker = $permissions_checker;
    }

    /**
     * Checks if given use has permissions to create transition on given tracker.
     *
     * @throws \Luracast\Restler\RestException
     */
    public function checkCreate(PFUser $user, Tracker $tracker)
    {
        $this->permissions_checker->checkUpdateWorkflow($user, $tracker);
    }

    /**
     * Checks if given user has permissions to delete given transition.
     *
     * @throws \Luracast\Restler\RestException
     * @throws OrphanTransitionException
     */
    public function checkDelete(PFUser $user, Transition $transition)
    {
        $this->permissions_checker->checkUpdateWorkflow($user, $transition->getWorkflow()->getTracker());
    }

    /**
     * Checks if given user has permissions to read given transition.
     *
     * @throws OrphanTransitionException
     * @throws RestException 403
     * @throws RestException 404
     */
    public function checkRead(PFUser $user, Transition $transition)
    {
        $this->permissions_checker->checkUpdateWorkflow($user, $transition->getWorkflow()->getTracker());
    }

    /**
     * Checks if given user has permissions to update given transition.
     *
     * @throws OrphanTransitionException
     * @throws RestException 403
     * @throws RestException 404
     */
    public function checkUpdate(PFUser $user, Transition $transition)
    {
        $this->permissions_checker->checkUpdateWorkflow($user, $transition->getWorkflow()->getTracker());
    }
}
