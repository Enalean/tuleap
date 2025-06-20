<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Status;

use PFUser;
use Tracker_FormElement_Field_List_BindValue;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Workflow\FirstPossibleValueInListRetriever;
use Tuleap\Tracker\Workflow\NoPossibleValueException;

class StatusValueRetriever
{
    /**
     * @var TrackerSemanticStatusFactory
     */
    private $semantic_status_factory;

    public function __construct(TrackerSemanticStatusFactory $semantic_status_factory, private FirstPossibleValueInListRetriever $first_possible_value_retriever)
    {
        $this->semantic_status_factory = $semantic_status_factory;
    }

    /**
     * @throws SemanticStatusNotDefinedException
     * @throws SemanticStatusClosedValueNotFoundException
     * @throws NoPossibleValueException
     */
    public function getFirstClosedValueUserCanRead(PFUser $user, Artifact $artifact): Tracker_FormElement_Field_List_BindValue
    {
        $status_semantic_defined = $this->getStatusSemanticDefined($artifact->getTracker(), $user);
        $open_values             = $status_semantic_defined->getOpenValues();
        $status_field            = $status_semantic_defined->getField();
        $values                  = [];

        foreach ($status_field->getAllValues() as $value_id => $value) {
            if (! $value->isHidden() && ! in_array($value_id, $open_values)) {
                $values[$value->getId()] = $value;
            }
        }
        if (empty($values)) {
            throw new SemanticStatusClosedValueNotFoundException();
        }

        $collection = new StatusValuesCollection(array_keys($values));

        $bind_value_id = $this->first_possible_value_retriever->getFirstPossibleValue($artifact, $status_field, $collection, $user);

        return $values[$bind_value_id];
    }

    /**
     * @throws SemanticStatusNotDefinedException
     * @throws SemanticStatusClosedValueNotFoundException
     * @throws NoPossibleValueException
     */
    public function getFirstOpenValueUserCanRead(PFUser $user, Artifact $artifact): Tracker_FormElement_Field_List_BindValue
    {
        $status_semantic_defined = $this->getStatusSemanticDefined($artifact->getTracker(), $user);
        $open_values             = $status_semantic_defined->getOpenValues();
        $status_field            = $status_semantic_defined->getField();
        $values                  = [];
        foreach ($status_field->getAllValues() as $value_id => $value) {
            if (! $value->isHidden() && in_array($value_id, $open_values)) {
                $values[$value->getId()] = $value;
            }
        }

        if (empty($values)) {
            throw new SemanticStatusOpenValueNotFoundException();
        }

        $collection = new StatusValuesCollection(array_keys($values));

        $bind_value_id = $this->first_possible_value_retriever->getFirstPossibleValue($artifact, $status_field, $collection, $user);

        return $values[$bind_value_id];
    }

    /**
     * @throws SemanticStatusNotDefinedException
     */
    private function getStatusSemanticDefined(Tracker $tracker, PFUser $user): StatusSemanticDefined
    {
        $semantic_status       = $this->semantic_status_factory->getByTracker($tracker);
        $semantic_status_field = $semantic_status->getField();
        if (
            $semantic_status_field === null ||
            ! $semantic_status_field->userCanRead($user)
        ) {
            throw new SemanticStatusNotDefinedException();
        }

        return new StatusSemanticDefined(
            $semantic_status_field,
            $semantic_status->getOpenValues()
        );
    }
}
