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

use Docman_MetadataValueDao;

class MetadataValueUpdator
{
    /**
     * @var Docman_MetadataValueDao
     */
    private $metadata_value_dao;
    /**
     * @var MetadataValueObjectFactory
     */
    private $metadata_value_object_factory;
    /**
     * @var DocmanMetadataInputValidator
     */
    private $validator;
    /**
     * @var MetadataValueStore
     */
    private $metadata_value_store;

    public function __construct(
        DocmanMetadataInputValidator $validator,
        MetadataValueObjectFactory $metadata_value_object_factory,
        Docman_MetadataValueDao $metadata_value_dao,
        MetadataValueStore $metadata_value_store
    ) {
        $this->validator                     = $validator;
        $this->metadata_value_object_factory = $metadata_value_object_factory;
        $this->metadata_value_dao            = $metadata_value_dao;
        $this->metadata_value_store          = $metadata_value_store;
    }

    /**
     * @param $value string | int | array $value
     *
     * @throws MetadataDoesNotExistException
     */
    public function updateMetadata(\Docman_Metadata $metadata_to_update, int $id, $value): void
    {
        $validated_value = $this->validator->validateInput($metadata_to_update, $value);

        $docman_metadata_value = $this->metadata_value_object_factory->createMetadataValueObjectWithCorrectValue(
            $id,
            (int) $metadata_to_update->getId(),
            (int) $metadata_to_update->getType(),
            $validated_value
        );

        $existing_metadata_value = $this->metadata_value_dao->searchById(
            $docman_metadata_value->getFieldId(),
            $docman_metadata_value->getItemId()
        );

        if ($existing_metadata_value->count() > 0) {
            $this->metadata_value_store->updateMetadata(
                $docman_metadata_value,
                (int) $metadata_to_update->getGroupId()
            );
        } else {
            $this->metadata_value_store->storeMetadata($docman_metadata_value, (int) $metadata_to_update->getGroupId());
        }
    }
}
