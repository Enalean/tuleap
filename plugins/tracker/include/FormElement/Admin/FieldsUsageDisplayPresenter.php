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

namespace Tuleap\Tracker\FormElement\Admin;

use Tuleap\Tracker\REST\FormElementRepresentationsBuilder;
use Tuleap\Tracker\REST\StructureRepresentationBuilder;
use Tuleap\Tracker\Tracker;
use function Psl\Json\encode;

/**
 * @psalm-immutable
 */
final readonly class FieldsUsageDisplayPresenter
{
    private function __construct(
        public string $base_uri,
        public int $project_id,
        public int $id,
        public string $shortname,
        public string $color_value,
        public string $json_encoded_fields,
        public string $json_encoded_structure,
    ) {
    }

    public static function build(
        Tracker $tracker,
        \PFUser $user,
        FormElementRepresentationsBuilder $form_element_representations_builder,
        StructureRepresentationBuilder $structure_representation_builder,
    ): self {
        return new self(
            FieldsUsageDisplayController::getUrl($tracker),
            (int) $tracker->getProject()->getID(),
            $tracker->getId(),
            $tracker->getItemName(),
            $tracker->getColor()->value,
            encode($form_element_representations_builder->buildRepresentationsInTrackerContextIgnoringReadPermission($tracker, $user)),
            encode($structure_representation_builder->getStructureRepresentation($tracker)),
        );
    }
}
