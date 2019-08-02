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

namespace Tuleap\Docman\REST\v1\Metadata;

use Docman_Metadata;
use Tuleap\Docman\Metadata\CustomMetadataException;
use Tuleap\Docman\Metadata\ListOfValuesElement\MetadataListOfValuesElementListBuilder;

class CustomMetadataRepresentationRetriever
{
    /**
     * @var \Docman_MetadataFactory
     */
    private $factory;
    /**
     * @var MetadataListOfValuesElementListBuilder
     */
    private $list_values_builder;

    public function __construct(
        \Docman_MetadataFactory $factory,
        MetadataListOfValuesElementListBuilder $list_values_builder
    ) {
        $this->factory             = $factory;
        $this->list_values_builder = $list_values_builder;
    }

    /**
     * @throws CustomMetadataException
     */
    public function checkAndRetrieveFormattedRepresentation(?array $list_metadata): array
    {
        if (empty($list_metadata)) {
            return [];
        }
        $representations = [];
        foreach ($list_metadata as $metadata_representation) {
            $metadata = $this->factory->getMetadataFromLabel($metadata_representation->short_name);
            if (! $metadata) {
                throw CustomMetadataException::metadataNotFound($metadata_representation->short_name);
            }

            if ((int)$metadata->getType() === PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                if ($metadata->isMultipleValuesAllowed() === true) {
                    $this->checkMultipleMetadataListValues($metadata_representation, $metadata);
                    $representations[$metadata->getLabel()] = $metadata_representation->list_value;
                } else {
                    $this->checkSimpleMetadataListValues($metadata_representation, $metadata);
                    $representations[$metadata->getLabel()] = $metadata_representation->value;
                }
            } else {
                $this->checkMetadataValue($metadata_representation);
                $representations[$metadata->getLabel()] = $metadata_representation->value;
            }
        }

        return $representations;
    }

    /**
     * @throws CustomMetadataException
     */
    public function checkAndRetrieveFileFormattedRepresentation(?array $metadata_list): array
    {
        if (empty($metadata_list)) {
            return [];
        }

        $representations = [];
        foreach ($metadata_list as $metadata_representation) {
            $metadata = $this->factory->getFromLabel($metadata_representation->short_name);
            if (! $metadata) {
                throw CustomMetadataException::metadataNotFound($metadata_representation->short_name);
            }

            if ((int)$metadata->getType() === PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                if ($metadata->isMultipleValuesAllowed() === true) {
                    $this->checkMultipleMetadataListValues($metadata_representation, $metadata);
                    $representations[] = [
                        'id'    => (int)$metadata->getId(),
                        'value' => $metadata_representation->list_value,
                    ];
                } else {
                    $this->checkSimpleMetadataListValues($metadata_representation, $metadata);
                    $representations[] = [
                        'id'    => (int)$metadata->getId(),
                        'value' => $metadata_representation->value
                    ];
                }
            } else {
                $this->checkMetadataValue($metadata_representation);
                $representations[] = [
                    'id'    => (int)$metadata->getId(),
                    'value' => $metadata_representation->value
                ];
            }
        }

        return $representations;
    }

    /**
     * @throws CustomMetadataException
     */
    private function checkMultipleMetadataListValues(POSTCustomMetadataRepresentation $metadata_representation, Docman_Metadata $metadata): void
    {
        if ($metadata_representation->value !== null) {
            throw CustomMetadataException::valueProvidedForListMetadata($metadata_representation->short_name);
        }
        if (empty($metadata_representation->list_value)) {
            return;
        }
        $error_unknown = [];
        $possible_values_of_list  = $this->list_values_builder->build((int)$metadata->getId(), true);

        foreach ($metadata_representation->list_value as $representation_value) {
            $value_exists = false;
            foreach ($possible_values_of_list as $project_list_value) {
                if ((int)$project_list_value->getId() === $representation_value) {
                    $value_exists = true;
                }
            }
            if (! $value_exists) {
                $error_unknown[] = $representation_value;
            }
        }
        if (count($error_unknown) > 0) {
            throw CustomMetadataException::unknownValue($error_unknown, $metadata_representation->short_name);
        }
    }

    /**
     * @throws CustomMetadataException
     */
    private function checkSimpleMetadataListValues(POSTCustomMetadataRepresentation $metadata_representation, Docman_Metadata $metadata): void
    {
        if ($metadata_representation->list_value !== null) {
            throw CustomMetadataException::listOnlyAcceptSingleValues($metadata_representation->short_name);
        }

        if (!$metadata_representation->value) {
            return;
        }

        $possible_values_of_list  = $this->list_values_builder->build((int)$metadata->getId(), true);
        foreach ($possible_values_of_list as $project_list_value) {
            if ($project_list_value->getId() === $metadata_representation->value) {
                return;
            }
        }

        throw CustomMetadataException::unknownValue([$metadata_representation->value], $metadata_representation->short_name);
    }

    /**
     * @throws CustomMetadataException
     */
    private function checkMetadataValue(POSTCustomMetadataRepresentation $metadata_representation): void
    {
        if ($metadata_representation->list_value !== null) {
            throw CustomMetadataException::listValueProvidedForMetadata($metadata_representation->short_name);
        }
    }
}
