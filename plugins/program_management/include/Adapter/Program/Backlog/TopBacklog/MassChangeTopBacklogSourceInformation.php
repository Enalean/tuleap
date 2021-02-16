<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog;

use Tuleap\Tracker\Masschange\TrackerMasschangeProcessExternalActionsEvent;

/**
 * @psalm-immutable
 */
final class MassChangeTopBacklogSourceInformation
{
    private const MASS_CHANGE_ACTION_NAME = 'masschange-action-program-management-top-backlog';

    /**
     * @var int
     */
    public $project_id;
    /**
     * @var int[]
     */
    public $masschange_aids;
    /**
     * @var \PFUser
     */
    public $user;
    /**
     * @var string|null
     */
    public $action;

    /**
     * @param int[] $masschange_aids
     */
    public function __construct(int $project_id, array $masschange_aids, \PFUser $user, ?string $action)
    {
        $this->project_id      = $project_id;
        $this->masschange_aids = $masschange_aids;
        $this->user            = $user;
        $this->action          = $action;
    }

    public static function fromProcessExternalActionEvent(TrackerMasschangeProcessExternalActionsEvent $event): self
    {
        return new self(
            (int) $event->getTracker()->getGroupId(),
            $event->getMasschangeAids(),
            $event->getUser(),
            $event->getRequest()->get(self::MASS_CHANGE_ACTION_NAME) ?: null
        );
    }
}
