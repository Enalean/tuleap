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

namespace Tuleap\Docman\Upload\Document;

use Docman_ListMetadata;
use Docman_Metadata;
use Docman_MetadataDao;
use LogicException;
use Tuleap\Docman\Metadata\MetadataValueCreator;

class DocumentMetadataCreator
{
    /**
     * @var Docman_MetadataDao
     */
    private $metadata_dao;
    /**
     * @var MetadataValueCreator
     */
    private $value_creator;

    public function __construct(
        MetadataValueCreator $value_creator,
        Docman_MetadataDao $metadata_dao
    ) {
        $this->value_creator       = $value_creator;
        $this->metadata_dao        = $metadata_dao;
    }

    public function storeItemCustomMetadata(int $item_id, array $metadata_list): void
    {
        foreach ($metadata_list as $metadata_representation) {
            $row_metadata = $this->metadata_dao->searchById($metadata_representation['id']);
            if (! $row_metadata) {
                throw new LogicException(sprintf("Save of metadata %d is not supported", $metadata_representation['id']));
            }

            $metadata = $this->initMetadataFromRow($row_metadata->getRow());
            $this->value_creator->createMetadataObject($metadata, $item_id, $metadata_representation['value']);
        }
    }

    private function initMetadataFromRow(array $metadata_row): Docman_Metadata
    {
        if ((int) $metadata_row['data_type'] === PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
            $metadata = new Docman_ListMetadata();
        } else {
            $metadata = new Docman_Metadata();
        }
        $metadata->initFromRow($metadata_row);

        return $metadata;
    }
}
