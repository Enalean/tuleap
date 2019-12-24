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

use PFUser;
use Tracker;
use Tuleap\Event\Dispatchable;

class TrackerMasschangeGetExternalActionsEvent implements Dispatchable
{
    public const NAME = 'trackerMasschangeGetExternalActionsEvent';

    /**
     * @var Tracker
     */
    private $tracker;

    /**
     * @var string[]
     */
    private $external_actions = [];

    /**
     * @var PFUser
     */
    private $user;

    public function __construct(Tracker $tracker, PFUser $user)
    {
        $this->tracker = $tracker;
        $this->user    = $user;
    }

    public function getTracker(): Tracker
    {
        return $this->tracker;
    }

    /**
     * @return string[]
     */
    public function getExternalActions(): array
    {
        return $this->external_actions;
    }

    public function addExternalActions(string $external_action): void
    {
        $this->external_actions[] = $external_action;
    }

    public function getUser(): PFUser
    {
        return $this->user;
    }
}
