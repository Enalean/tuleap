<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1\Event;

use Tuleap\Event\Dispatchable;
use Tuleap\Tracker\Artifact\Artifact;

class ArtifactPartialUpdate implements Dispatchable
{
    public const NAME = 'artifactPartialUpdate';

    /**
     * @var Artifact
     */
    private $artifact;

    /**
     * @var bool
     */
    private $is_updatable = true;

    /**
     * @var string
     */
    private $not_updatable_message = '';

    public function __construct(Artifact $artifact)
    {
        $this->artifact = $artifact;
    }

    /**
     * @return Artifact
     */
    public function getArtifact()
    {
        return $this->artifact;
    }

    public function setNotUpdatable($not_updatable_message)
    {
        $this->is_updatable          = false;
        $this->not_updatable_message = $not_updatable_message;
    }

    public function isArtifactUpdatable()
    {
        return $this->is_updatable;
    }

    /**
     * @return string
     */
    public function getNotUpdatableMessage()
    {
        return $this->not_updatable_message;
    }
}
