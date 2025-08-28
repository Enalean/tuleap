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

use Tracker_FormElementFactory;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\TrackerField;

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
     * @return TrackerField[]
     */
    public function getFields(Artifact $artifact): array
    {
        return $this->getFileFieldsFirstThenTheOthers($artifact);
    }

    /**
     *
     * @return TrackerField[]
     */
    private function getFileFieldsFirstThenTheOthers(Artifact $artifact): array
    {
        $fields = $this->element_factory->getUsedFields($artifact->getTracker());

        usort(
            $fields,
            function (TrackerField $a, TrackerField $b) {
                return $this->element_factory->isFieldAFileField($b) ? 1 : 0;
            }
        );

        return $fields;
    }
}
