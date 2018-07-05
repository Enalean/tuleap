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

namespace Tuleap\Tracker\Events;

use SimpleXMLElement;
use Tracker;
use Tracker_FormElement_Field;
use Tuleap\Event\Dispatchable;

class MoveArtifactParseFieldChangeNodes implements Dispatchable
{
    const NAME = "moveArtifactParseFieldChangeNodes";

    /**
     * @var Tracker
     */
    private $source_tracker;

    /**
     * @var Tracker
     */
    private $target_tracker;

    /**
     * @var SimpleXMLElement
     */
    private $changeset_xml;

    /**
     * @var bool
     */
    private $modified_by_plugin = false;

    /**
     * @var int
     */
    private $index;

    public function __construct(
        Tracker $source_tracker,
        Tracker $target_tracker,
        SimpleXMLElement $changeset_xml,
        $index
    ) {
        $this->source_tracker = $source_tracker;
        $this->target_tracker = $target_tracker;
        $this->changeset_xml  = $changeset_xml;
        $this->index          = $index;
    }

    /**
     * @return Tracker
     */
    public function getSourceTracker()
    {
        return $this->source_tracker;
    }

    /**
     * @return Tracker
     */
    public function getTargetTracker()
    {
        return $this->target_tracker;
    }

    /**
     * @return bool
     */
    public function isModifiedByPlugin()
    {
        return $this->modified_by_plugin;
    }

    public function setModifiedByPlugin()
    {
        $this->modified_by_plugin = true;
    }

    /**
     * @return SimpleXMLElement
     */
    public function getChangesetXml()
    {
        return $this->changeset_xml;
    }

    /**
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }
}
