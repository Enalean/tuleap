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
use ReferenceManager;

class MetadataValueStore
{
    /**
     * @var Docman_MetadataValueDao
     */
    private $metadata_value_dao;
    /**
     * @var ReferenceManager
     */
    private $reference_manager;

    public function __construct(
        Docman_MetadataValueDao $metadata_value_dao,
        ReferenceManager $reference_manager,
    ) {
        $this->metadata_value_dao = $metadata_value_dao;
        $this->reference_manager  = $reference_manager;
    }

    public function storeMetadata(\Docman_MetadataValue $metadata_value, int $project_id): void
    {
        switch ((int) $metadata_value->getType()) {
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
                $this->extractCrossReferences($metadata_value, $project_id);
                break;
            default:
                throw new MetadataDoesNotExistException();
        }
    }

    /**
     *
     * @throws MetadataDoesNotExistException
     */
    public function updateMetadata(\Docman_MetadataValue $metadata_value, int $project_id): void
    {
        switch ($metadata_value->getType()) {
            case PLUGIN_DOCMAN_METADATA_TYPE_LIST:
                $this->metadata_value_dao->delete($metadata_value->getFieldId(), $metadata_value->getItemId());

                $this->storeMetadata($metadata_value, $project_id);
                break;

            case PLUGIN_DOCMAN_METADATA_TYPE_TEXT:
            case PLUGIN_DOCMAN_METADATA_TYPE_STRING:
            case PLUGIN_DOCMAN_METADATA_TYPE_DATE:
                $this->metadata_value_dao->updateValue(
                    $metadata_value->getItemId(),
                    $metadata_value->getFieldId(),
                    $metadata_value->getType(),
                    $metadata_value->getValue()
                );
                $this->extractCrossReferences($metadata_value, $project_id);

                break;

            default:
                throw new MetadataDoesNotExistException();
        }
    }

    private function extractCrossReferences(\Docman_MetadataValue $metadata_value, int $project_id): void
    {
        $this->reference_manager->extractCrossRef(
            $metadata_value->getValue(),
            (string) $metadata_value->getItemId(),
            ReferenceManager::REFERENCE_NATURE_DOCUMENT,
            $project_id
        );
    }
}
