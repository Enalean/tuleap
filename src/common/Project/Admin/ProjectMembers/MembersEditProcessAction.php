<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Project\Admin\ProjectMembers;

use Codendi_Request;
use CSRFSynchronizerToken;
use Project;
use Tuleap\Event\Dispatchable;

class MembersEditProcessAction implements Dispatchable
{
    public const NAME = 'membersEditProcessAction';

    /**
     * @var bool
     */
    private $has_been_handled;
    /**
     * @var Codendi_Request
     */
    private $request;
    /**
     * @var Project
     */
    private $project;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf;

    public function __construct(
        Codendi_Request $request,
        Project $project,
        CSRFSynchronizerToken $csrf
    ) {
        $this->request          = $request;
        $this->project          = $project;
        $this->csrf             = $csrf;
        $this->has_been_handled = false;
    }

    /**
     * @return Codendi_Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return CSRFSynchronizerToken
     */
    public function getCSRF()
    {
        return $this->csrf;
    }

    /**
     * @return bool
     */
    public function hasBeenHandled()
    {
        return $this->has_been_handled;
    }

    public function setHasBeenHandledToTrue()
    {
        $this->has_been_handled = true;
    }
}
