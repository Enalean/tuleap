<?php
/**
 * Copyright (c) Enalean SAS. 2011 - Present. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

namespace Tuleap\Tracker\FormElement\Container\Fieldset;

use Tracker_Artifact;
use Tracker_FormElement_Container_Fieldset;
use Tuleap\Tracker\FormElement\Container\FieldsExtractor;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDetector;

class HiddenFieldsetChecker
{
    /**
     * @var HiddenFieldsetsDetector
     */
    private $hidden_fieldsets_detector;

    /**
     * @var FieldsExtractor
     */
    private $fields_extractor;

    public function __construct(
        HiddenFieldsetsDetector $hidden_fieldsets_detector,
        FieldsExtractor $fields_extractor
    ) {
        $this->hidden_fieldsets_detector = $hidden_fieldsets_detector;
        $this->fields_extractor          = $fields_extractor;
    }

    public function mustFieldsetBeHidden(
        Tracker_FormElement_Container_Fieldset $fieldset,
        Tracker_Artifact $artifact
    ): bool {
        if ($this->hidden_fieldsets_detector->isFieldsetHidden($artifact, $fieldset)) {
            $fields = $this->fields_extractor->extractFieldsInsideContainer($fieldset);
            foreach ($fields as $field) {
                if ($field->isRequired() || $field->isUsedInFieldDependency()) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}
