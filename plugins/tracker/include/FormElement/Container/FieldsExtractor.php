<?php
/**
 * Copyright (c) Enalean SAS. 2019 - Present. All rights reserved
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

namespace Tuleap\Tracker\FormElement\Container;

use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\FormElement\TrackerFormElement;

class FieldsExtractor
{
    /**
     * This methids returns all the fields in a given container
     * Fields that are directly in the container and fields inside a container in this container ...
     *
     * @return TrackerField[]
     */
    public function extractFieldsInsideContainer(TrackerFormElementContainer $container): array
    {
        $fields = [];
        foreach ($container->getFormElements() as $form_element) {
            $this->parseFormElement($form_element, $fields);
        }

        return $fields;
    }

    private function parseFormElement(TrackerFormElement $form_element, array &$fields)
    {
        if (is_a($form_element, TrackerField::class)) {
            $fields[] = $form_element;
        } elseif (is_a($form_element, TrackerFormElementContainer::class)) {
            foreach ($form_element->getFormElements() as $sub_form_element) {
                $this->parseFormElement($sub_form_element, $fields);
            }
        }
    }
}
