<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Event;

use PFUser;
use Tracker_Artifact;
use Tuleap\Event\Dispatchable;

class ArtifactUpdated implements Dispatchable
{
    public const NAME = 'trackerArtifactUpdated';

    /**
     * @var Tracker_Artifact
     * @psalm-readonly
     */
    private $artifact;

    /**
     * @var PFUser
     */
    private $user;

    public function __construct(Tracker_Artifact $artifact, PFUser $user)
    {
        $this->artifact = $artifact;
        $this->user     = $user;
    }

    /**
     * @psalm-mutation-free
     */
    public function getArtifact(): Tracker_Artifact
    {
        return $this->artifact;
    }

    /**
     * @psalm-mutation-free
     */
    public function getUser(): PFUser
    {
        return $this->user;
    }
}
