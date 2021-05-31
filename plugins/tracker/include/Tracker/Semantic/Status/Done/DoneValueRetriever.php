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

class DoneValueRetriever
{
    /**
     * @var SemanticDoneFactory
     */
    private $semantic_done_factory;

    public function __construct(SemanticDoneFactory $semantic_done_factory)
    {
        $this->semantic_done_factory = $semantic_done_factory;
    }

    /**
     * @throws SemanticDoneNotDefinedException
     * @throws SemanticDoneValueNotFoundException
     */
    public function getFirstDoneValueUserCanRead(Tracker $tracker, PFUser $user): Tracker_FormElement_Field_List_BindValue
    {
        $done_semantic_defined = $this->getDoneSemanticDefined($tracker, $user);
        $done_values           = $done_semantic_defined->getDoneValues();
        $status_field          = $done_semantic_defined->getField();
        foreach ($status_field->getAllValues() as $value_id => $value) {
            if (! $value->isHidden() && in_array($value_id, $done_values)) {
                return $value;
            }
        }

        throw new SemanticDoneValueNotFoundException();
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
