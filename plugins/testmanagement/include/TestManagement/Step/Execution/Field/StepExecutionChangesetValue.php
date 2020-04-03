<?php
/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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

namespace Tuleap\TestManagement\Step\Execution\Field;

use PFUser;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValueVisitor;
use Tuleap\TestManagement\Step\Execution\StepResult;

class StepExecutionChangesetValue extends \Tracker_Artifact_ChangesetValue
{
    /**
     * @var StepResult[]
     */
    private $steps;

    /**
     *
     *
     * @param int                        $id
     * @param bool                       $has_changed
     * @param StepResult[]               $step_results
     */
    public function __construct(
        $id,
        Tracker_Artifact_Changeset $changeset,
        StepExecution $field,
        $has_changed,
        array $step_results
    ) {
        parent::__construct($id, $changeset, $field, $has_changed);
        $this->steps = $step_results;
    }

    public function diff($changeset_value, $format = 'html', ?PFUser $user = null, $ignore_perms = false)
    {
        return '';
    }

    /**
     * @param mixed $format
     * @return void
     */
    public function nodiff($format = 'html')
    {
    }

    public function getRESTValue(PFUser $user)
    {
    }

    public function getFullRESTValue(PFUser $user)
    {
    }

    /**
     * @return mixed
     */
    public function accept(Tracker_Artifact_ChangesetValueVisitor $visitor)
    {
        return $visitor->visitExternalField($this);
    }

    /**
     * Returns the value of this changeset value
     *
     * @return array The value of this artifact changeset value
     */
    public function getValue()
    {
        return $this->steps;
    }
}
