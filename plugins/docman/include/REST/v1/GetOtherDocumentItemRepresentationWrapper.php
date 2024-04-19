<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1;

use Tuleap\Docman\REST\v1\Others\OtherTypePropertiesRepresentation;
use Tuleap\Event\Dispatchable;

final class GetOtherDocumentItemRepresentationWrapper implements Dispatchable
{
    private ?string $type = null;

    private ?OtherTypePropertiesRepresentation $other_type_properties = null;

    public function __construct(
        private readonly ItemRepresentationBuilder $item_representation_builder,
        public readonly \Docman_Item $item,
        private readonly \PFUser $current_user,
    ) {
    }

    public function buildItemRepresentation(): ItemRepresentation
    {
        return $this->item_representation_builder->buildItemRepresentation(
            $this->item,
            $this->current_user,
            $this->type,
            null,
            null,
            null,
            null,
            null,
            $this->other_type_properties,
        );
    }

    public function setItemRepresentationArguments(
        string $type,
        OtherTypePropertiesRepresentation $other_type_properties,
    ): void {
        $this->type                  = $type;
        $this->other_type_properties = $other_type_properties;
    }
}
