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

use Docman_MetadataValue;
use Docman_MetadataValueList;
use Docman_MetadataValueScalar;

class DocmanMetadataTypeValueFactory
{
    public function createFromType(int $type): Docman_MetadataValue
    {
        $metadata_value = null;
        switch ($type) {
            case PLUGIN_DOCMAN_METADATA_TYPE_LIST:
                $metadata_value = new Docman_MetadataValueList();
                break;

            case PLUGIN_DOCMAN_METADATA_TYPE_TEXT:
            case PLUGIN_DOCMAN_METADATA_TYPE_STRING:
            case PLUGIN_DOCMAN_METADATA_TYPE_DATE:
                $metadata_value = new Docman_MetadataValueScalar();
                break;
            default:
                throw new \LogicException(sprintf("Metadata type %d does not exist!", $type));
        }
        $metadata_value->setType($type);

        return $metadata_value;
    }
}
