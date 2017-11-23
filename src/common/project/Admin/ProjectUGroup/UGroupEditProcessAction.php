<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use Codendi_Request;
use CSRFSynchronizerToken;
use ProjectUGroup;
use Tuleap\Event\Dispatchable;

class UGroupEditProcessAction implements Dispatchable
{
    const NAME = 'ugroupEditProcessAction';

    /**
     * @var bool
     */
    private $has_been_handled;
    /**
     * @var Codendi_Request
     */
    private $request;
    /**
     * @var ProjectUGroup
     */
    private $ugroup;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf;

    public function __construct(Codendi_Request $request, ProjectUGroup $ugroup, CSRFSynchronizerToken $csrf)
    {
        $this->request          = $request;
        $this->ugroup           = $ugroup;
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
     * @return ProjectUGroup
     */
    public function getUGroup()
    {
        return $this->ugroup;
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
