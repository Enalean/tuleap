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

namespace Tuleap\Tracker\FormElement\Field\ListFields\Bind;

use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_BindDecorator;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Exception\NoChangesetException;
use Tuleap\Tracker\Artifact\Exception\NoChangesetValueException;

class BindDecoratorRetriever
{
    /**
     * @throws NoBindDecoratorException
     * @throws NoChangesetException
     * @throws NoChangesetValueException
     */
    public function getDecoratorForFirstValue(Tracker_FormElement_Field_List $field, Artifact $artifact): Tracker_FormElement_Field_List_BindDecorator
    {
        $changeset = $artifact->getLastChangeset();
        if (! $changeset) {
            throw new NoChangesetException();
        }

        $decorators = $field->getDecorators();

        $list_bind = $field->getBind();

        if (! $list_bind) {
            throw new \LogicException("Field "  . $field->getId() . " has no associate bind");
        }

        $values                               = $list_bind->getChangesetValues($changeset->getId());
        if (! $values) {
            if (! isset($decorators[Tracker_FormElement_Field_List::NONE_VALUE])) {
                throw new NoChangesetValueException();
            }
            return $decorators[Tracker_FormElement_Field_List::NONE_VALUE];
        }

        $first_value_id = $this->getFirstBindValueId($values);

        if (! isset($decorators[$first_value_id])) {
            throw new NoBindDecoratorException();
        }
        return $decorators[$first_value_id];
    }

    private function getFirstBindValueId(array $values)
    {
        return $values[0]['id'];
    }
}
