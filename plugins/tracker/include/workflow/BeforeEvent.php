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

namespace Tuleap\Tracker\Workflow;

use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_FormElement_Field;
use Tuleap\Event\Dispatchable;

class BeforeEvent implements Dispatchable
{
    const NAME = 'beforeEvent';

    /**
     * @var bool[]
     *
     */
    private $should_bypass_permissions = [];
    /**
     * @var Tracker_Artifact
     */
    private $artifact;
    /**
     * @var array
     */
    private $fields_data;

    public function __construct(
        Tracker_Artifact $artifact,
        array $fields_data
    ) {
        $this->artifact    = $artifact;
        $this->fields_data = $fields_data;
    }

    /**
     * @return Tracker_Artifact
     */
    public function getArtifact()
    {
        return $this->artifact;
    }

    /**
     * @return array
     */
    public function getFieldsData()
    {
        return $this->fields_data;
    }

    public function forceFieldData($field_id, $value)
    {
        $this->fields_data[$field_id]               = $value;
        $this->should_bypass_permissions[$field_id] = true;
    }

    public function shouldBypassPermissions(Tracker_FormElement_Field $field)
    {
        return isset($this->should_bypass_permissions[$field->getId()]);
    }
}
