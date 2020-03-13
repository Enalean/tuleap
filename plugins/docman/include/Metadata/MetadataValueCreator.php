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

class MetadataValueCreator
{
    /**
     * @var DocmanMetadataInputValidator
     */
    private $validator;
    /**
     * @var MetadataValueObjectFactory
     */
    private $metadata_value_object_factory;
    /**
     * @var MetadataValueStore
     */
    private $metadata_value_store;


    public function __construct(
        DocmanMetadataInputValidator $validator,
        MetadataValueObjectFactory $metadata_value_object_factory,
        MetadataValueStore $metadata_value_store
    ) {
        $this->validator                     = $validator;
        $this->metadata_value_object_factory = $metadata_value_object_factory;
        $this->metadata_value_store          = $metadata_value_store;
    }

    /**
     * @param $value string | int | array $value
     * @throws MetadataDoesNotExistException
     */
    public function createMetadataObject(\Docman_Metadata $metadata_to_create, int $id, $value): void
    {
        $validated_value = $this->validator->validateInput($metadata_to_create, $value);

        $docman_metadata_value = $this->metadata_value_object_factory->createMetadataValueObjectWithCorrectValue(
            $id,
            (int) $metadata_to_create->getId(),
            (int) $metadata_to_create->getType(),
            $validated_value
        );

        $this->metadata_value_store->storeMetadata($docman_metadata_value, (int) $metadata_to_create->getGroupId());
    }
}
