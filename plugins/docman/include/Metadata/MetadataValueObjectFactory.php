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

declare(strict_types=1);

namespace Tuleap\Docman\Metadata;

use Docman_MetadataListOfValuesElement;
use Docman_MetadataValue;

class MetadataValueObjectFactory
{
    /**
     * @var DocmanMetadataTypeValueFactory
     */
    private $metadata_type_value_factory;

    public function __construct(DocmanMetadataTypeValueFactory $metadata_type_value_factory)
    {
        $this->metadata_type_value_factory = $metadata_type_value_factory;
    }

    /**
     * Create and set-up a MetadataValue object.
     * @var $value string | int | array
     */
    public function createMetadataValueObjectWithCorrectValue(int $item_id, int $field_id, int $type, $value): Docman_MetadataValue
    {
        $docman_metadata_value = $this->metadata_type_value_factory->createFromType($type);

        $docman_metadata_value->setFieldId($field_id);
        $docman_metadata_value->setItemId($item_id);
        $docman_metadata_value->setType($type);
        if ($type === PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
            $ea = [];
            if (is_array($value)) {
                foreach ($value as $val) {
                    $e = new Docman_MetadataListOfValuesElement();
                    $e->setId($val);
                    $ea[] = $e;
                }
            } else {
                $e = new Docman_MetadataListOfValuesElement();
                $e->setId($value);
                $ea[] = $e;
            }
            $docman_metadata_value->setValue($ea);
        } else {
            $docman_metadata_value->setValue($value);
        }

        return $docman_metadata_value;
    }
}
