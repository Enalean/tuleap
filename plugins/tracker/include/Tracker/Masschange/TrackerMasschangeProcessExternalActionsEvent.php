<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Masschange;

use Codendi_Request;
use PFUser;
use Tracker;
use Tuleap\Event\Dispatchable;

class TrackerMasschangeProcessExternalActionsEvent implements Dispatchable
{
    public const NAME = 'trackerMasschangeProcessExternalActionsEvent';

    /**
     * @var Codendi_Request
     */
    private $request;

    /**
     * @var int[]
     */
    private $masschange_aids;

    /**
     * @var PFUser
     */
    private $user;

    /**
     * @var Tracker
     */
    private $tracker;

    public function __construct(
        PFUser $user,
        Tracker $tracker,
        Codendi_Request $request,
        array $masschange_aids
    ) {
        $this->request         = $request;
        $this->masschange_aids = $masschange_aids;
        $this->user            = $user;
        $this->tracker         = $tracker;
    }

    public function getRequest(): Codendi_Request
    {
        return $this->request;
    }

    /**
     * @return int[]
     */
    public function getMasschangeAids(): array
    {
        return $this->masschange_aids;
    }

    public function getUser(): PFUser
    {
        return $this->user;
    }

    public function getTracker(): Tracker
    {
        return $this->tracker;
    }
}
