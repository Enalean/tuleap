<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST;

use Tracker_FormElementFactory;
use Tuleap\Tracker\Tracker;

readonly class StructureRepresentationBuilder
{
    public function __construct(private Tracker_FormElementFactory $formelement_factory)
    {
    }

    public function getStructureRepresentation(Tracker $tracker): array
    {
        $structure_element_representations = [];
        $form_elements                     = $this->formelement_factory->getUsedFormElementForTracker($tracker);

        if ($form_elements) {
            foreach ($form_elements as $form_element) {
                $structure_element_representation = new StructureElementRepresentation();
                $structure_element_representation->build($form_element);

                $structure_element_representations[] = $structure_element_representation;
            }
        }

        return $structure_element_representations;
    }
}
