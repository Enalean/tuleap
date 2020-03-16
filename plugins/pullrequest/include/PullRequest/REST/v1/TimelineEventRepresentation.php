<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1;

use Tuleap\User\REST\MinimalUserRepresentation;
use Tuleap\REST\JsonCast;
use Tuleap\PullRequest\Timeline\TimelineGlobalEvent;

class TimelineEventRepresentation
{

    public const UPDATE  = 'update';
    public const REBASE  = 'rebase';
    public const MERGE   = 'merge';
    public const ABANDON = 'abandon';

    /**
     * @var MinimalUserRepresentation {@type MinimalUserRepresentation}
     */
    public $user;

    /**
     * @var string {@type string}
     */
    public $post_date;

    /**
     * @var string {@type string}
     */
    public $event_type;

    /**
     * @var string {@type string}
     */
    public $type;


    public function __construct($user, $post_date, $event_type)
    {
        $this->user           = $user;
        $this->post_date      = JsonCast::toDate($post_date);
        $this->event_type     = $this->expandType($event_type);
        $this->type           = 'timeline-event';
    }

    private function expandType($type_acronym)
    {
        $status_name = array(
            TimelineGlobalEvent::UPDATE  => self::UPDATE,
            TimelineGlobalEvent::REBASE  => self::REBASE,
            TimelineGlobalEvent::MERGE   => self::MERGE,
            TimelineGlobalEvent::ABANDON => self::ABANDON,
        );

        return $status_name[$type_acronym];
    }
}
