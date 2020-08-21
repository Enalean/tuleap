<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1;

use Tuleap\Docman\REST\ResourcesInjector;
use Tuleap\Docman\REST\v1\Files\CreatedItemFilePropertiesRepresentation;
use Tuleap\REST\JsonCast;

/**
 * @psalm-immutable
 */
final class CreatedItemRepresentation
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $uri;

    /**
     * @var CreatedItemFilePropertiesRepresentation | null {@type \Tuleap\Docman\REST\v1\CreatedItemFilePropertiesRepresentation} {@required false}
     */
    public $file_properties;

    private function __construct(int $item_id, ?CreatedItemFilePropertiesRepresentation $file_properties)
    {
        $this->id              = $item_id;
        $this->uri             = ResourcesInjector::NAME . '/' . $item_id;
        $this->file_properties = $file_properties;
    }

    /**
     * @param int $item_id The id of the item.
     */
    public static function build($item_id, ?CreatedItemFilePropertiesRepresentation $file_properties = null): self
    {
        return new self(
            JsonCast::toInt($item_id),
            $file_properties
        );
    }
}
