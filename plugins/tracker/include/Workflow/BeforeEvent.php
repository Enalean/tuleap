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

use PFUser;
use Tracker_FormElement_Field;
use Tuleap\Event\Dispatchable;
use Tuleap\Tracker\Artifact\Artifact;

class BeforeEvent implements Dispatchable
{
    public const NAME = 'beforeEvent';

    /**
     * @var bool[]
     *
     */
    private $should_bypass_permissions = [];
    /**
     * @var Artifact
     */
    private $artifact;
    /**
     * @var array
     */
    private $fields_data;
    /**
     * @var PFUser
     */
    private $user;

    public function __construct(
        Artifact $artifact,
        array $fields_data,
        PFUser $user
    ) {
        $this->artifact    = $artifact;
        $this->fields_data = $fields_data;
        $this->user        = $user;
    }

    /**
     * @return Artifact
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

    /**
     * @return PFUser
     */
    public function getUser()
    {
        return $this->user;
    }
}
