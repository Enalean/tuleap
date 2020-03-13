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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types = 1);

namespace Tuleap\Docman\REST\v1\Metadata;

use Codendi_HTMLPurifier;
use Docman_MetadataFactory;
use Docman_MetadataListOfValuesElement;
use Tuleap\REST\JsonCast;
use UserHelper;

class MetadataRepresentationBuilder
{
    /**
     * @var Docman_MetadataFactory
     */
    private $factory;
    /**
     * @var Codendi_HTMLPurifier
     */
    private $html_purifier;
    /**
     * @var UserHelper
     */
    private $user_helper;

    public function __construct(
        Docman_MetadataFactory $factory,
        Codendi_HTMLPurifier $html_purifier,
        UserHelper $user_helper
    ) {
        $this->factory       = $factory;
        $this->html_purifier = $html_purifier;
        $this->user_helper   = $user_helper;
    }

    /**
     * @return ItemMetadataRepresentation[]
     * @throws UnknownMetadataException
     */
    public function build(\Docman_Item $item) : array
    {
        $this->factory->appendItemMetadataList($item);

        $metadata_representations = [];

        $item_metadata = $item->getMetadata();

        foreach ($item_metadata as $metadata) {
            $transformed_values = $this->getMetadataValues($metadata);

            $metadata_representations[] = new ItemMetadataRepresentation(
                $metadata->getName(),
                $this->getMetadataType((int) $metadata->getType()),
                $metadata->isMultipleValuesAllowed(),
                $transformed_values['value'],
                $transformed_values['post_processed_value'],
                $transformed_values['list_value'],
                $metadata->isEmptyAllowed(),
                $metadata->getLabel()
            );
        }

        return $metadata_representations;
    }

    /**
     * @return array{value:string|null,post_processed_value:string|null,list_value:MetadataListValueRepresentation[]|null}
     */
    private function getMetadataValues(\Docman_Metadata $metadata) : array
    {
        $metadata_type = (int) $metadata->getType();
        $value = $metadata->getValue();
        if ($metadata_type === PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
            $list_value = [];
            foreach ($value as $metadata_value) {
                assert($metadata_value instanceof Docman_MetadataListOfValuesElement);
                $list_value[] = new MetadataListValueRepresentation(
                    (int) $metadata_value->getId(),
                    $metadata_value->getMetadataValue()
                );
            }

            return ['value' => null, 'post_processed_value' => null, 'list_value' => $list_value];
        }

        if ($metadata_type === PLUGIN_DOCMAN_METADATA_TYPE_DATE) {
            if ($value !== 0 && $value !== '0') {
                $date = JsonCast::toDate($value);
                return ['value' => $date, 'post_processed_value' => $date, 'list_value' => null];
            }
            return ['value' => null, 'post_processed_value' => null, 'list_value' => null];
        }

        if ($metadata->getLabel() === Docman_MetadataFactory::HARDCODED_METADATA_OWNER_LABEL) {
            return [
                'value'                => $this->user_helper->getDisplayNameFromUserId($value),
                'post_processed_value' => $this->user_helper->getLinkOnUserFromUserId($value),
                'list_value'           => null
            ];
        }

        return [
            'value'                => (string) $value,
            'post_processed_value' => $this->html_purifier->purifyTextWithReferences($value, $metadata->getGroupId()),
            'list_value'           => null
        ];
    }

    /**
     *
     * @throws UnknownMetadataException
     */
    private function getMetadataType(int $type): string
    {
        switch ($type) {
            case PLUGIN_DOCMAN_METADATA_TYPE_TEXT:
                return 'text';
                break;
            case PLUGIN_DOCMAN_METADATA_TYPE_STRING:
                return 'string';
                break;
            case PLUGIN_DOCMAN_METADATA_TYPE_DATE:
                return 'date';
                break;
            case PLUGIN_DOCMAN_METADATA_TYPE_LIST:
                return 'list';
                break;
            default:
                throw new UnknownMetadataException("Metadata type: " . $type . " unknown");
                break;
        }
    }
}
