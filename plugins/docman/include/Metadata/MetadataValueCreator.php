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

use Docman_MetadataValueDao;
use ReferenceManager;

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
     * @var Docman_MetadataValueDao
     */
    private $metadata_value_dao;
    /**
     * @var ReferenceManager
     */
    private $reference_manager;


    public function __construct(
        DocmanMetadataInputValidator $validator,
        MetadataValueObjectFactory $metadata_value_object_factory,
        Docman_MetadataValueDao $metadata_value_dao,
        ReferenceManager $reference_manager
    ) {
        $this->validator                     = $validator;
        $this->metadata_value_object_factory = $metadata_value_object_factory;
        $this->metadata_value_dao            = $metadata_value_dao;
        $this->reference_manager             = $reference_manager;
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
            (int)$metadata_to_create->getId(),
            (int)$metadata_to_create->getType(),
            $validated_value
        );

        $this->storeMetadata($docman_metadata_value, (int)$metadata_to_create->getGroupId());
    }

    public function storeMetadata(\Docman_MetadataValue $metadata_value, int $project_id): void
    {
        switch ((int)$metadata_value->getType()) {
            case PLUGIN_DOCMAN_METADATA_TYPE_LIST:
                $eIter = $metadata_value->getValue();
                $eIter->rewind();

                while ($eIter->valid()) {
                    $e = $eIter->current();
                    $this->metadata_value_dao->create(
                        $metadata_value->getItemId(),
                        $metadata_value->getFieldId(),
                        $metadata_value->getType(),
                        $e->getId()
                    );
                    $eIter->next();
                }
                break;

            case PLUGIN_DOCMAN_METADATA_TYPE_TEXT:
            case PLUGIN_DOCMAN_METADATA_TYPE_STRING:
            case PLUGIN_DOCMAN_METADATA_TYPE_DATE:
                $this->metadata_value_dao->create(
                    $metadata_value->getItemId(),
                    $metadata_value->getFieldId(),
                    $metadata_value->getType(),
                    $metadata_value->getValue()
                );
                // extract cross references
                $this->reference_manager->extractCrossRef($metadata_value->getValue(), $metadata_value->getItemId(), ReferenceManager::REFERENCE_NATURE_DOCUMENT, $project_id);
                break;
            default:
                throw new MetadataDoesNotExistException();
        }
    }
}
