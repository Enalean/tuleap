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

namespace Tuleap\Tracker\Semantic\Status\Done;

use PFUser;
use Tracker;
use Tracker_FormElement_Field_List_BindValue;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Status\StatusValuesCollection;
use Tuleap\Tracker\Workflow\FirstPossibleValueInListRetriever;
use Tuleap\Tracker\Workflow\NoPossibleValueException;

class DoneValueRetriever
{
    /**
     * @var SemanticDoneFactory
     */
    private $semantic_done_factory;

    public function __construct(SemanticDoneFactory $semantic_done_factory, private FirstPossibleValueInListRetriever $first_possible_value_retriever)
    {
        $this->semantic_done_factory = $semantic_done_factory;
    }

    /**
     * @throws SemanticDoneNotDefinedException
     * @throws SemanticDoneValueNotFoundException
     * @throws NoPossibleValueException
     */
    public function getFirstDoneValueUserCanRead(Artifact $artifact, PFUser $user): Tracker_FormElement_Field_List_BindValue
    {
        $done_semantic_defined = $this->getDoneSemanticDefined($artifact->getTracker(), $user);
        $done_values           = $done_semantic_defined->getDoneValues();
        $status_field          = $done_semantic_defined->getField();
        $values                = [];
        foreach ($status_field->getAllValues() as $value_id => $value) {
            if (! $value->isHidden() && in_array($value_id, $done_values)) {
                $values[$value->getId()] = $value;
            }
        }
        if (empty($values)) {
            throw new SemanticDoneValueNotFoundException();
        }

        $collection = new StatusValuesCollection(array_keys($values));

        $bind_value_id = $this->first_possible_value_retriever->getFirstPossibleValue($artifact, $status_field, $collection, $user);

        return $values[$bind_value_id];
    }

    /**
     * @throws SemanticDoneNotDefinedException
     */
    private function getDoneSemanticDefined(Tracker $tracker, PFUser $user): DoneSemanticDefined
    {
        $semantic_done       = $this->semantic_done_factory->getInstanceByTracker($tracker);
        $semantic_done_field = $semantic_done->getSemanticStatus()->getField();
        if (
            $semantic_done_field === null ||
            ! $semantic_done_field->userCanRead($user)
        ) {
            throw new SemanticDoneNotDefinedException();
        }

        return new DoneSemanticDefined(
            $semantic_done_field,
            $semantic_done->getDoneValuesIds()
        );
    }
}
