<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1\Search;

final class SearchColumnCollectionBuilder
{
    public function getCollection(\Docman_MetadataFactory $metadata_factory): SearchColumnCollection
    {
        $columns = new SearchColumnCollection();
        $this->addMandatoryFirstColumns($columns);

        $all_metadata   = $metadata_factory->getMetadataForGroup(true);
        $custom_columns = [];
        foreach ($all_metadata as $metadata) {
            assert($metadata instanceof \Docman_Metadata);
            if ($metadata->getLabel() === \Docman_MetadataFactory::HARDCODED_METADATA_TITLE_LABEL) {
                continue;
            }

            if ($metadata->isSpecial()) {
                $columns->add(SearchColumn::buildForHardcodedProperty($metadata->getLabel(), $metadata->getName()));
            } elseif ($metadata->isMultipleValuesAllowed()) {
                $custom_columns[] = SearchColumn::buildForMultipleValuesCustomProperty($metadata->getLabel(), $metadata->getName());
            } else {
                $custom_columns[] = SearchColumn::buildForSingleValueCustomProperty($metadata->getLabel(), $metadata->getName());
            }
        }

        $columns->add(SearchColumn::buildForHardcodedProperty("location", dgettext('tuleap-document', 'Location')));
        $columns->add(SearchColumn::buildForHardcodedProperty("filename", dgettext('tuleap-document', 'Filename')));

        foreach ($custom_columns as $column) {
            $columns->add($column);
        }

        return $columns;
    }

    private function addMandatoryFirstColumns(SearchColumnCollection $columns): void
    {
        $columns->add(SearchColumn::buildForHardcodedProperty("id", dgettext('tuleap-document', 'Id')));
        $columns->add(SearchColumn::buildForHardcodedProperty("title", dgettext('tuleap-document', 'Title')));
    }
}
