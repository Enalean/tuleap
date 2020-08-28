<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact;

use PFUser;
use Tracker;
use Tuleap\Event\Dispatchable;

final class CanSubmitNewArtifact implements Dispatchable
{
    public const NAME = 'canSubmitNewArtifact';

    /**
     * @var PFUser
     * @psalm-readonly
     */
    private $user;
    /**
     * @var Tracker
     * @psalm-readonly
     */
    private $tracker;
    /**
     * @var bool
     */
    private $can_submit_new_artifact = true;

    public function __construct(PFUser $user, Tracker $tracker)
    {
        $this->user    = $user;
        $this->tracker = $tracker;
    }

    public function getUser(): PFUser
    {
        return $this->user;
    }

    public function getTracker(): Tracker
    {
        return $this->tracker;
    }

    public function canSubmitNewArtifact(): bool
    {
        return $this->can_submit_new_artifact;
    }

    public function disableArtifactSubmission(): void
    {
        $this->can_submit_new_artifact = false;
    }
}
