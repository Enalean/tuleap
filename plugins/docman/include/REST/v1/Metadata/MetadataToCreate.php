<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\REST\v1\Metadata;

class MetadataToCreate
{
    /**
     * @var array
     */
    private $metadata_list_values;
    /**
     * @var bool
     */
    private $should_inherit_from_parent;

    private function __construct(array $metadata_list_values, bool $should_inherit_from_parent)
    {
        $this->metadata_list_values = $metadata_list_values;
        $this->should_inherit_from_parent = $should_inherit_from_parent;
    }

    public static function buildMetadataRepresentation(array $metadata_list_values, bool $should_inherit_from_parent): self
    {
        return new self($metadata_list_values, $should_inherit_from_parent);
    }

    /**
     * @return array
     */
    public function getMetadataListValues(): array
    {
        return $this->metadata_list_values;
    }

    public function isInheritedFromParent(): bool
    {
        return $this->should_inherit_from_parent;
    }
}
