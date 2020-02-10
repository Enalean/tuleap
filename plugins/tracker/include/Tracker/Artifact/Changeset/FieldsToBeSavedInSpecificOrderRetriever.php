<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset;

use Tracker_Artifact;
use Tracker_FormElement_Field;
use Tracker_FormElementFactory;

class FieldsToBeSavedInSpecificOrderRetriever
{
    /**
     * @var Tracker_FormElementFactory
     */
    private $element_factory;

    public function __construct(Tracker_FormElementFactory $element_factory)
    {
        $this->element_factory = $element_factory;
    }

    /**
     *
     * @return Tracker_FormElement_Field[]
     */
    public function getFields(Tracker_Artifact $artifact): array
    {
        return $this->getFileFieldsFirstThenTheOthers($artifact);
    }

    /**
     *
     * @return Tracker_FormElement_Field[]
     */
    private function getFileFieldsFirstThenTheOthers(Tracker_Artifact $artifact): array
    {
        $fields = $this->element_factory->getUsedFields($artifact->getTracker());

        usort(
            $fields,
            function (Tracker_FormElement_Field $a, Tracker_FormElement_Field $b) {
                return $this->element_factory->isFieldAFileField($b) ? 1 : 0;
            }
        );

        return $fields;
    }
}
