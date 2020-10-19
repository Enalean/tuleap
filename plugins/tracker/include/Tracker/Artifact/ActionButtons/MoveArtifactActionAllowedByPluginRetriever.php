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

namespace Tuleap\Tracker\Artifact\ActionButtons;

use Tracker;
use Tuleap\Event\Dispatchable;

class MoveArtifactActionAllowedByPluginRetriever implements Dispatchable
{
    public const NAME = 'moveArtifactActionAllowedByPluginRetriever';
    /**
     * @var \Tuleap\Tracker\Artifact\Artifact
     */
    private $artifact;
    /**
     * @var string
     */
    private $error;
    /**
     * @var Tracker
     */
    private $tracker;
    /**
     * @var bool
     */
    private $has_external_semantic_defined = false;

    /**
     * @var bool
     */
    private $can_be_moved = true;

    public function __construct(\Tuleap\Tracker\Artifact\Artifact $artifact)
    {
        $this->tracker = $artifact->getTracker();
        $this->artifact = $artifact;
    }

    /**
     * @return Tracker
     */
    public function getTracker()
    {
        return $this->tracker;
    }

    public function setCanNotBeMoveDueToExternalPlugin($error)
    {
        $this->can_be_moved = false;
        $this->error        = $error;
    }

    public function hasExternalSemanticDefined()
    {
        return $this->has_external_semantic_defined === true;
    }

    public function doesAnExternalPluginForbiddenTheMove()
    {
        return $this->can_be_moved === false;
    }

    public function getErrorMessage()
    {
        return $this->error;
    }

    /**
     * @return \Tuleap\Tracker\Artifact\Artifact
     */
    public function getArtifact()
    {
        return $this->artifact;
    }
}
