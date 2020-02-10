<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Docman\Metadata;

use Tuleap\Docman\REST\v1\Metadata\MetadataToUpdate;
use Tuleap\Docman\REST\v1\Metadata\PUTMetadataFolderRepresentation;

class ItemImpactedByMetadataChangeCollection
{
    /**
     * @var array<string,string>
     */
    private $items_to_update;

    private function __construct(array $items_to_update)
    {
        $this->items_to_update = $items_to_update;
    }

    public static function buildFromLegacy(array $fields_to_update, array $metadata_array): self
    {
        $items = [];
        foreach ($fields_to_update as $recursive_elements) {
            if (isset($metadata_array[$recursive_elements])) {
                $items[$recursive_elements] = $metadata_array[$recursive_elements];
            } else {
                $items[$recursive_elements] = '';
            }
        }

        return new self($items);
    }

    /**
     * @param MetadataToUpdate[]              $metadata_to_update
     *
     * @return ItemImpactedByMetadataChangeCollection
     */
    public static function buildFromRest(
        PUTMetadataFolderRepresentation $representation,
        array $metadata_to_update,
        string $recursion_option
    ): self {
        $items = [];

        if ($representation->status->recursion === $recursion_option) {
            $items['status'] = $representation->status->value;
        }
        foreach ($metadata_to_update as $metadata) {
            if ($metadata->getRecursion() === $recursion_option) {
                $items[$metadata->getMetadata()->getLabel()] = $metadata->getValue();
            }
        }

        return new self($items);
    }

    /**
     * @return string[]
     */
    public function getFieldsToUpdate(): array
    {
        return array_keys($this->items_to_update);
    }

    public function getTotalElements(): int
    {
        return count($this->items_to_update);
    }

    /**
     * @return       string[]
     * @psalm-return array<string,string>
     */
    public function getValuesToExtractCrossReferences(): array
    {
        return $this->items_to_update;
    }
}
