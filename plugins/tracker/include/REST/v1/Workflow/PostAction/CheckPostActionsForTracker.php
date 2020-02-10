<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1\Workflow\PostAction;

use Tracker;
use Tuleap\Event\Dispatchable;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;

class CheckPostActionsForTracker implements Dispatchable
{
    public const NAME = 'checkPostActionsForTracker';

    /**
     * @var PostActionCollection
     */
    private $post_actions;

    /**
     * @var Tracker
     */
    private $tracker;

    /**
     * @var bool
     */
    private $are_post_actions_eligible = true;

    /**
     * @var string
     */
    private $error_message = '';

    public function __construct(Tracker $tracker, PostActionCollection $post_actions)
    {
        $this->tracker      = $tracker;
        $this->post_actions = $post_actions;
    }

    public function getTracker(): Tracker
    {
        return $this->tracker;
    }

    public function getPostActions(): PostActionCollection
    {
        return $this->post_actions;
    }

    public function arePostActionsEligible(): bool
    {
        return $this->are_post_actions_eligible;
    }

    /**
     * @param bool $are_post_actions_eligible
     */
    public function setPostActionsNonEligible(): void
    {
        $this->are_post_actions_eligible = false;
    }

    public function getErrorMessage(): string
    {
        return $this->error_message;
    }

    public function setErrorMessage(string $error_message): void
    {
        $this->error_message = $error_message;
    }
}
